<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use \Minishlink\WebPush\VAPID;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\NotificationSender;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\LogDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\CredentialService;

class WebmasterController extends AbstractController
{
    public function __construct(
        Application $application,
        private LogDataHelper $logDataHelper,
        private ArticleDataHelper $articleDataHelper,
        private NotificationSender $notificationSender,
        private CredentialService $credentials
    ) {
        parent::__construct($application);
    }

    public function helpAdmin(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAdministrator())) {
            $this->render('Common/views/info.latte', $this->getAllParams([
                'content'          => $this->languagesDataHelper->translate('Help_Admin'),
                'hasAuthorization' => $this->application->getConnectedUser()->isAdministrator(),
                'currentVersion'   => Application::VERSION,
                'timer'            => 0,
                'previousPage'     => true,
                'page'             => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function helpWebmaster(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $lang = TranslationManager::getCurrentLanguage();
            $this->render('Common/views/info.latte', [
                'content'          => $this->dataHelper->get('Languages', ['Name' => 'Help_Webmaster'], $lang)->$lang ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->isWebmaster() ?? false,
                'currentVersion'   => Application::VERSION,
                'timer'            => 0,
                'previousPage'     => true,
                'page'             => $this->application->getConnectedUser()->getPage()
            ]);
        }
    }

    public function homeAdmin(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if (!($connectedUser->isAdministrator() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($connectedUser->hasOnlyOneAutorization()) {
            if ($connectedUser->isEventDesigner())     $this->redirect('/designer');
            if ($connectedUser->isHomeDesigner())      $this->redirect('/designer');
            if ($connectedUser->isMenuDesigner())      $this->redirect('/designer');
            elseif ($connectedUser->isEventManager())  $this->redirect('/eventManager');
            elseif ($connectedUser->isPersonManager()) $this->redirect('/personManager');
            elseif ($connectedUser->isRedactor()) {
                $_SESSION['navbar'] = 'redactor';
                $this->redirect('/articles');
            } elseif ($connectedUser->isVisitorInsights()) $this->redirect('/visitorInsights');
            elseif ($connectedUser->isWebmaster())         $this->redirect('/webmaster');
        } else {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->raiseMethodNotAllowed(__FILE__, __LINE__);
                return;
            }
            $content = $this->languagesDataHelper->translate('Admin');
            $params  = [
                'isEventManager'    => $connectedUser->isEventManager(),
                'isDesigner'        => $connectedUser->isDesigner(),
                'isRedactor'        => $connectedUser->isRedactor(),
                'isPersonManager'   => $connectedUser->isPersonManager(),
                'isVisitorInsights' => $connectedUser->isVisitorInsights(),
                'isWebmaster'       => $connectedUser->isWebmaster(),
            ];
            $this->render('Webmaster/views/admin.latte', $this->getAllParams([
                'page'    => $connectedUser->getPage(),
                'content' => WebApp::getcompiledContent($content, $params),
            ]));
        }
    }

    public function homeWebmaster(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $_SESSION['navbar'] = 'webmaster';
            $content = $this->languagesDataHelper->translate('Webmaster');
            $params  = ['isMyclubWebSite' => WebApp::isMyClubWebSite()];

            $this->render('Webmaster/views/webmaster.latte', $this->getAllParams([
                'newVersion'     => $this->getLastVersion(),
                'page'           => $this->application->getConnectedUser()->getPage(),
                'content'        => WebApp::getcompiledContent($content, $params),
            ]));
        }
    }

    public function notifications(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $publicKey  = $this->credentials->get('vapid', 'publicKey');
            $privateKey = $this->credentials->get('vapid', 'privateKey');

            if (!$publicKey || !$privateKey) {
                $newKeys = VAPID::createVapidKeys();
                $this->credentials->set('vapid', 'publicKey',  $newKeys['publicKey']);
                $this->credentials->set('vapid', 'privateKey', $newKeys['privateKey']);
                $message   = "✅ Les clés VAPID ont été générées et enregistrées avec succès.";
                $publicKey = $newKeys['publicKey'];
            } else {
                $message = "ℹ️ Les clés VAPID existent déjà dans la base de données.";
            }

            $notification = '';
            if (isset($_GET['test'])) {
                $this->notificationSender->sendToRecipients(
                    [1],
                    [
                        'title' => 'Notification de test',
                        'body'  => 'Ceci est une notification push de test.',
                        'url'   => '/',
                    ]
                );
                $notification = "🚀 Notification de test envoyée à l'utilisateur #1.";
            }

            $this->render('Webmaster/views/notifications.latte', [
                'message'         => $message,
                'notification'    => $notification,
                'publicKey'       => $publicKey,
                'phpExtensions'   => [
                    'gmp'      => extension_loaded('gmp'),
                    'mbstring' => extension_loaded('mbstring'),
                ],
                'navItems'        => $this->getNavItems($this->application->getConnectedUser()->person),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page'            => $this->application->getConnectedUser()->getPage(),
                'currentVersion'  => Application::VERSION,
            ]);
        }
    }

    public function sendEmailCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $this->render('Webmaster/views/emailCredentials.latte', $this->getAllParams([
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
                'page'     => $this->application->getConnectedUser()->getPage(),
                'sendMethod'          => $this->credentials->get('email', 'method'),
                'sendEmailAddress'    => $this->credentials->get('smtp', 'username'),
                'sendEmailHost'       => $this->credentials->get('smtp', 'host'),
                'sendEmailPort'       => $this->credentials->get('smtp', 'port'),
                'sendEmailEncryption' => $this->credentials->get('smtp', 'encryption'),
                'mailjetApiKey'       => $this->credentials->get('mailjet', 'api_key'),
                'mailjetSender'       => $this->credentials->get('mailjet', 'sender'),
                'dailyLimit'          => $this->credentials->get('email', 'daily_limit'),
                'monthlyLimit'        => $this->credentials->get('email', 'monthly_limit')
            ]));
        }
    }

    public function sendEmailCredentialsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster())) {

            $schema = [
                'sendMethod'           => FilterInputRule::Text->value,
                'sendEmailAddress'     => FilterInputRule::Email->value,
                'sendEmailPassword'    => FilterInputRule::Password->value,
                'sendEmailHost'        => FilterInputRule::Uri->value,
                'sendEmailPort'        => FilterInputRule::Integer->value,
                'sendEmailEncryption'  => FilterInputRule::Text->value,
                'mailjetApiKey'        => FilterInputRule::Text->value,
                'mailjetApiSecret'     => FilterInputRule::Text->value,
                'mailjetSender'        => FilterInputRule::Email->value,
                'smtpDailyLimit'       => FilterInputRule::Integer->value,
                'smtpMonthlyLimit'     => FilterInputRule::Integer->value,
                'mailjetDailyLimit'    => FilterInputRule::Integer->value,
                'mailjetMonthlyLimit'  => FilterInputRule::Integer->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            error_log("\n\n" . json_encode($input, JSON_PRETTY_PRINT) . "\n");
            $method = $input['sendMethod'] ?? 'smtp';

            $this->credentials->set('email', 'method', $method);
            match ($method) {
                'smtp' => (function () use ($input) {
                    $this->credentials->set('smtp', 'username',   $input['sendEmailAddress']    ?? '');
                    if (!empty($input['sendEmailPassword'])) {
                        $this->credentials->set('smtp', 'password', $input['sendEmailPassword']);
                    }
                    $this->credentials->set('smtp', 'host',       $input['sendEmailHost']       ?? '');
                    $this->credentials->set('smtp', 'port',       $input['sendEmailPort']       ?? '587');
                    $this->credentials->set('smtp', 'encryption', $input['sendEmailEncryption'] ?? 'tls');
                    $this->credentials->set('email', 'daily_limit', (string)($input['smtpDailyLimit'] ?? "0"));
                    $this->credentials->set('email', 'monthly_limit', (string)($input['smtpMonthlyLimit'] ?? "0"));
                })(),

                'mailjet' => (function () use ($input) {
                    $this->credentials->set('mailjet', 'api_key', $input['mailjetApiKey']    ?? '');
                    if (!empty($input['mailjetApiSecret'])) {
                        $this->credentials->set('mailjet', 'api_secret', $input['mailjetApiSecret']);
                    }
                    $this->credentials->set('mailjet', 'sender',  $input['mailjetSender']   ?? '');
                    $this->credentials->set('email', 'daily_limit', (string)($input['mailjetDailyLimit'] ?? "0"));
                    $this->credentials->set('email', 'monthly_limit', (string)($input['mailjetMonthlyLimit'] ?? "0"));
                })(),

                default => null,
            };

            $this->redirect('/webmaster');
        }
    }

    public function showInstallations(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $installations = $this->logDataHelper->getInstallationsData();

            $this->render('Webmaster/views/installations.latte', $this->getAllParams([
                'installations'      => $installations,
                'totalInstallations' => count($installations),
                'navItems'           => $this->getNavItems($this->application->getConnectedUser()->person),
                'page'               => $this->application->getConnectedUser()->getPage(),
            ]));
        }
    }

    public function sitemapGenerator(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $base_url = WebApp::getBaseUrl();
        header("Content-Type: application/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . $base_url . '</loc>' . PHP_EOL;
        echo '    <lastmod>' . $this->articleDataHelper->getLastUpdateArticles() . '</lastmod>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>1.0</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        foreach ($this->articleDataHelper->getArticlesForAll() as $article) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . $base_url . '/article/' . $article->Id . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $article->LastUpdate . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
            echo '    <priority>0.5</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }
        echo '</urlset>';
    }

    public function turnstileCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $this->render('Webmaster/views/turnstileCredentials.latte', $this->getAllParams([
                'navItems'           => $this->getNavItems($this->application->getConnectedUser()->person),
                'page'               => $this->application->getConnectedUser()->getPage(),
                'turnstileSiteKey'   => $this->credentials->get('turnstile', 'site_key'),
                'turnstileConfigured' => !empty($this->credentials->get('turnstile', 'secret_key')),
            ]));
        }
    }

    public function turnstileCredentialsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster())) {
            $schema = [
                'turnstileSiteKey'   => FilterInputRule::Text->value,
                'turnstileSecretKey' => FilterInputRule::Text->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $this->credentials->set('turnstile', 'site_key', $input['turnstileSiteKey'] ?? '');
            if (!empty($input['turnstileSecretKey'])) {
                $this->credentials->set('turnstile', 'secret_key', $input['turnstileSecretKey']);
            }

            $this->redirect('/webmaster');
        }
    }

    #region Private methods
    private function getLastVersion(): ?string
    {
        $url = WebApp::MYCLUB_WEBAPP . 'api/lastVersion?cv=' . Application::VERSION . '&url=' . urlencode(WebApp::getBaseUrl());
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => "PHP/" . PHP_VERSION,
            CURLOPT_HTTPHEADER     => ["Accept: application/json"],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            unset($ch);
            return "Test for MyClub new version error : Erreur cURL ($error)";
        }
        unset($ch);
        $data = json_decode($response, true);
        if ($data === null || ($data['success'] ?? false) !== true || !isset($data['data']['lastVersion'])) {
            return "Test for MyClub new version error : Réponse API invalide.";
        }
        $lastVersion = $data['data']['lastVersion'];
        return $lastVersion === Application::VERSION ? null : "A new version is available (V{$lastVersion})";
    }
}
