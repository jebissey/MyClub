<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use \Minishlink\WebPush\VAPID;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\NotificationSender;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\LogDataHelper;
use app\modules\Common\AbstractController;

class WebmasterController extends AbstractController
{
    public function __construct(
        Application $application,
        private LogDataHelper $logDataHelper,
        private ArticleDataHelper $articleDataHelper,
        private NotificationSender $notificationSender
    ) {
        parent::__construct($application);
    }

    public function helpAdmin(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAdministrator())) {
            $this->render('Common/views/info.latte', $this->getAllParams([
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

    public function homeAdmin(): void
    {
        if (!($this->application->getConnectedUser()->isAdministrator() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($this->application->getConnectedUser()->hasOnlyOneAutorization()) {
            if ($this->application->getConnectedUser()->isEventDesigner())     $this->redirect('/designer');
            if ($this->application->getConnectedUser()->isHomeDesigner())      $this->redirect('/designer');
            if ($this->application->getConnectedUser()->isNavbarDesigner())    $this->redirect('/designer');
            elseif ($this->application->getConnectedUser()->isEventManager())  $this->redirect('/eventManager');
            elseif ($this->application->getConnectedUser()->isPersonManager()) $this->redirect('/personManager');
            elseif ($this->application->getConnectedUser()->isRedactor()) {
                $_SESSION['navbar'] = 'redactor';
                $this->redirect('/articles');
            } elseif ($this->application->getConnectedUser()->isVisitorInsights())  $this->redirect('/visitorInsights');
            elseif ($this->application->getConnectedUser()->isWebmaster()) $this->redirect('/webmaster');
        } else {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->raiseMethodNotAllowed(__FILE__, __LINE__);
                return;
            }
            $this->render('Webmaster/views/admin.latte', $this->getAllParams([
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

            $this->render('Webmaster/views/webmaster.latte', $this->getAllParams([
                'newVersion' => $newVersion,
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function notifications(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAdministrator())) {
            $metadata = $this->dataHelper->get('Metadata', ['Id' => 1], 'VapidPublicKey, VapidPrivateKey ');
            $message = '';
            $notification = '';
            $publicKey = null;
            if (empty($metadata->VapidPublicKey) || empty($metadata->VapidPrivateKey)) {
                $newKeys = VAPID::createVapidKeys();
                $metadata = $this->dataHelper->set('Metadata', [
                    'VapidPublicKey' => $newKeys['publicKey'],
                    'VapidPrivateKey' => $newKeys['privateKey']
                ], ['Id' => 1]);
                $message = "✅ Les clés VAPID ont été générées et enregistrées avec succès.";
                $publicKey = $newKeys['publicKey'];
            } else {
                $message = "ℹ️ Les clés VAPID existent déjà dans la base de données.";
                $publicKey = $metadata->VapidPublicKey;
            }
            if (isset($_GET['test'])) {
                $this->notificationSender->sendToRecipients(
                    [1],
                    [
                        'title' => 'Notification de test',
                        'body'  => 'Ceci est une notification push de test.',
                        'url'   => '/'
                    ]
                );
                $notification = "🚀 Notification de test envoyée à l’utilisateur #1.";
            }
            $phpExtensions = [
                'gmp'      => extension_loaded('gmp'),
                'mbstring' => extension_loaded('mbstring'),
            ];

            $this->render('Webmaster/views/notifications.latte', [
                'message' => $message,
                'notification' => $notification,
                'publicKey' => $publicKey,
                'phpExtensions' => $phpExtensions,
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page' => $this->application->getConnectedUser()->getPage(),
                'currentVersion' => Application::VERSION,
            ]);
        }
    }

    public function sendEmailCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAdministrator())) {
            $this->render('Webmaster/views/emailCredentials.latte', $this->getAllParams([
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function sendEmailCredentialsSave(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'sendEmailAddress' => FilterInputRule::Email->value,
            'sendEmailPassword' => FilterInputRule::Password->value,
            'sendEmailHost' => FilterInputRule::Uri->value
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $this->dataHelper->set('Metadata', [
            'SendEmailAddress' => $input['sendEmailAddress'] ?? '???',
            'SendEmailPassword' => $input['sendEmailPassword'] ?? '???',
            'SendEmailHost' => $input['sendEmailHost'] ?? '???',
        ], ['Id' => 1]);
        $this->redirect('/webmaster');
    }

    public function showInstallations(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $installations = $this->logDataHelper->getInstallationsData();

            $this->render('Webmaster/views/installations.latte', $this->getAllParams([
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
