<?php

namespace app\modules\Webmaster;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\ArwardsDataHelper;
use app\models\LogDataHelper;
use app\modules\Common\AbstractController;

class WebmasterController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function helpWebmaster(): void
    {
        if ($this->connectedUser->get()->isAdministrator() ?? false) {
            $this->render('Common/views/info.latte', [
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_webmaster'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->connectedUser->get()->isEventManager() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true
            ]);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function helpVisitorInsights(): void
    {
        $this->render('Common/views/info.latte', [
            'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_visitorInsights'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->connectedUser->get()->isEventManager() ?? false,
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true
        ]);
    }

    public function helpAdmin()
    {
        if ($this->connectedUser->get()->isAdministrator() ?? false) {
            $this->render('Common/views/info.latte', Params::getAll([
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_admin'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->connectedUser->isEventManager(),
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function homeWebmaster(): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'webmaster';

                $newVersion = null;
                $result = $this->getLastVersion();
                if (!$result['success']) $newVersion = "Test for MyClub new version error : " . $result['error'];
                elseif ($result['version'] != Application::VERSION) $newVersion = "A new version is available (V" . $result['version'] . ")";

                $this->render('Webmaster/views/webmaster.latte', Params::getAll([
                    'newVersion' => $newVersion,
                    'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function homeAdmin()
    {
        if ($this->connectedUser->get()->isAdministrator() ?? false) {
            if ($this->connectedUser->hasOnlyOneAutorization()) {
                if ($this->connectedUser->isEventManager())       $this->redirect('/eventManager');
                else if ($this->connectedUser->isPersonManager()) $this->redirect('/personManager');
                else if ($this->connectedUser->isRedactor()) {
                    $_SESSION['navbar'] = 'redactor';
                    $this->redirect('/articles');
                } else if ($this->connectedUser->isWebmaster())   $this->redirect('/webmaster');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') $this->render('Webmaster/views/admin.latte', Params::getAll([]));
            else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function arwards(): void
    {
        $person = $this->connectedUser->get()->person ?? false;
        if ($person && $this->connectedUser->isWebmaster()) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $arwardsDataHelper = new ArwardsDataHelper($this->application);
                $this->render('Webmaster/views/arwards.latte', Params::getAll([
                    'counterNames' => $counterNames = $arwardsDataHelper->getCounterNames(),
                    'data' => $arwardsDataHelper->getData($counterNames),
                    'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                    'layout' => $this->getLayout(),
                    'navItems' => $this->getNavItems($person),
                    'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                ]));
            } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'customName' => FilterInputRule::PersonName->value,
                    'name' => FilterInputRule::PersonName->value,
                    'detail' => FilterInputRule::HtmlSafeText->value,
                    'value' => FilterInputRule::Int->value,
                    'idPerson' => FilterInputRule::Int->value,
                    'idGroup' => FilterInputRule::Int->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $name = $input['customName'] ?? $input['name'];
                $value = $input['value'];
                $idPerson = $input['idPerson'];
                $idGroup = $input['idGroup'];
                if (
                    $name === null
                    || $value === null || $value < 0
                    || $idPerson === null || $idPerson <= 0
                    || $idGroup === null || $idGroup <= 0
                ) $this->redirect('/arwards?error=invalid_data');
                else {
                    $this->dataHelper->set('Counter', [
                        'Name' => $name,
                        'Detail' => $input['detail'],
                        'Value' => $value,
                        'IdPerson' => $idPerson,
                        'IdGroup' => $idGroup
                    ]);
                    $this->redirect('/arwards?success=true');
                }
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function sitemapGenerator()
    {
        $articleDataHelper = new ArticleDataHelper($this->application);
        $base_url = WebApp::getBaseUrl();
        header("Content-Type: application/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . $base_url . '</loc>' . PHP_EOL;
        echo '    <lastmod>' . $articleDataHelper->getLastUpdateArticles() . '</lastmod>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>1.0</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        $articles = $articleDataHelper->getArticlesForAll();
        foreach ($articles as $article) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . $base_url . '/article/' . $article->Id . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $article->LastUpdate . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
            echo '    <priority>0.5</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }
        echo '</urlset>';
    }

    public function visitorInsights(): void
    {
        if ($this->connectedUser->get()->isVisitorInsights() ?? false) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'visitorInsights';

                $this->render('Webmaster/views/visitorInsights.latte', Params::getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showInstallations()
    {
        $person = $this->connectedUser->get()->person ?? false;
        if ($person && $this->connectedUser->isWebmaster()) {
            $installations = (new LogDataHelper($this->application))->getInstallationsData();

            $this->render('Webmaster/views/installations.latte', Params::getAll([
                'installations' => $installations,
                'totalInstallations' => count($installations),
                'navItems' => $this->getNavItems($person),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    #region Private methods

    private function getLastVersion()
    {
        $url = WebApp::MYCLUB_WEBAPP . '/api/lastVersion?cv=' . Application::VERSION;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "PHP/" . PHP_VERSION);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'version' => null,
                'error'   => "Erreur cURL : $error"
            ];
        }
        curl_close($ch);
        $data = json_decode($response, true);
        if ($data === null || !isset($data["lastVersion"])) {
            return [
                'success' => false,
                'version' => null,
                'error'   => "Réponse JSON invalide ou champ 'lastVersion' absent."
            ];
        }

        return [
            'success' => true,
            'version' => $data["lastVersion"],
            'error'   => null
        ];
    }
}
