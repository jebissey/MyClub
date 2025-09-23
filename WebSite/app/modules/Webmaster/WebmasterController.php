<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\LogDataHelper;
use app\modules\Common\AbstractController;

class WebmasterController extends AbstractController
{
    public function __construct(
        Application $application,
        private LogDataHelper $logDataHelper,
        private ArticleDataHelper $articleDataHelper
    ) {
        parent::__construct($application);
    }

    public function helpAdmin()
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAdministrator())) {
            $this->render('Common/views/info.latte', Params::getAll([
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_admin'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->isEventManager(),
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true,
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function helpWebmaster(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $this->render('Common/views/info.latte', [
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_webmaster'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->isWebmaster() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true,
                'page' => $this->application->getConnectedUser()->getPage()
            ]);
        }
    }

    public function homeAdmin()
    {
        if (!($this->application->getConnectedUser()->isAdministrator() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($this->application->getConnectedUser()->hasOnlyOneAutorization()) {
            if ($this->application->getConnectedUser()->isDesigner())          $this->redirect('/designer');
            elseif ($this->application->getConnectedUser()->isEventManager())  $this->redirect('/eventManager');
            elseif ($this->application->getConnectedUser()->isPersonManager()) $this->redirect('/personManager');
            elseif ($this->application->getConnectedUser()->isRedactor()) {
                $_SESSION['navbar'] = 'redactor';
                $this->redirect('/articles');
            } elseif ($this->application->getConnectedUser()->isWebmaster()) $this->redirect('/webmaster');
        } else {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->raiseMethodNotAllowed(__FILE__, __LINE__);
                return;
            }
            $this->render('Webmaster/views/admin.latte', Params::getAll([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        }
    }

    public function homeWebmaster(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $_SESSION['navbar'] = 'webmaster';
            $newVersion = null;
            $result = $this->getLastVersion();
            if (!$result['success']) $newVersion = "Test for MyClub new version error : " . $result['error'];
            elseif ($result['version'] != Application::VERSION) $newVersion = "A new version is available (V" . $result['version'] . ")";

            $this->render('Webmaster/views/webmaster.latte', Params::getAll([
                'newVersion' => $newVersion,
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function showInstallations(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $installations = $this->logDataHelper->getInstallationsData();

            $this->render('Webmaster/views/installations.latte', Params::getAll([
                'installations' => $installations,
                'totalInstallations' => count($installations),
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function sitemapGenerator(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $articleDataHelper = $this->articleDataHelper;
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

    #region Private methods
    private function getLastVersion(): array
    {
        $url = WebApp::MYCLUB_WEBAPP . 'api/lastVersion?cv=' . Application::VERSION;

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
