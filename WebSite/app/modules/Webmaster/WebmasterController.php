<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use DateTime;
use Minishlink\WebPush\VAPID;
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

    public function clubCustomizationEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $settings = [
                'clubName'      => $this->dataHelper->getSetting('PWA_Name', 'MyClub'),
                'clubShortName' => $this->dataHelper->getSetting('PWA_ShortName', 'MyClub'),
                'themeColor'    => $this->dataHelper->getSetting('PWA_ThemeColor', '#0d6efd'),
                'background'    => $this->dataHelper->getSetting('PWA_BackgroundColor', '#ffffff'),
            ];

            $this->render('Webmaster/views/clubCustomization.latte', $this->getAllParams([
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
                'page'     => 'clubCustomization',
                'settings' => $settings,
                'btn_HistoryBack' => true,
                'btn_Parent' => '/webmaster',
            ]));
        }
    }

    public function clubCustomizationSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $schema = [
                'clubName'      => FilterInputRule::Text->value,
                'clubShortName' => FilterInputRule::Text->value,
                'themeColor'    => FilterInputRule::Text->value,
                'background'    => FilterInputRule::Text->value,
            ];

            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $this->dataHelper->setSetting('PWA_Name', self::toStr($input['clubName'] ?? null, 'MyClub'));
            $this->dataHelper->setSetting('PWA_ShortName', self::toStr($input['clubShortName'] ?? null, 'MyClub'));
            $this->dataHelper->setSetting('PWA_ThemeColor', self::toStr($input['themeColor'] ?? null, '#0d6efd'));
            $this->dataHelper->setSetting('PWA_BackgroundColor', self::toStr($input['background'] ?? null, '#ffffff'));
            $this->redirect('/webmaster');
        }
    }

    public function helpAdmin(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAdministrator(), __FILE__, __LINE__)) {
            $lang = TranslationManager::getCurrentLanguage();
            $this->render('Common/views/info.latte', $this->getAllParams([
                'content' => $this->dataHelper->get('Languages', ['Name' => 'Help_Admin'], $lang)->$lang ?? '',
                'timer' => 0,
                'btn_HistoryBack' => true,
            ]));
        }
    }

    public function helpWebmaster(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $lang = TranslationManager::getCurrentLanguage();
            $this->render('Common/views/info.latte', $this->getAllParams([
                'content'          => $this->dataHelper->get('Languages', ['Name' => 'Help_Webmaster'], $lang)->$lang ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->isWebmaster(),
                'currentVersion'   => Application::VERSION,
                'timer'            => 0,
                'previousPage'     => true,
                'page'             => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function homeAdmin(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if (!$connectedUser->isAdministrator()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($connectedUser->hasOnlyOneAutorization()) {
            if (
                $connectedUser->isEventDesigner()
                || $connectedUser->isExerciseDesigner()
                || $connectedUser->isHomeDesigner()
                || $connectedUser->isKanbanDesigner()
                || $connectedUser->isMenuDesigner()
            ) {
                $this->redirect('/designer');
            } elseif ($connectedUser->isEventManager()) {
                $this->redirect('/eventManager');
            } elseif ($connectedUser->isPersonManager()) {
                $this->redirect('/personManager');
            } elseif ($connectedUser->isRedactor()) {
                $_SESSION['navbar'] = 'redactor';
                $this->redirect('/articles');
            } elseif ($connectedUser->isVisitorInsights()) {
                $this->redirect('/visitorInsights');
            } elseif ($connectedUser->isWebmaster()) {
                $this->redirect('/webmaster');
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->raiseMethodNotAllowed(__FILE__, __LINE__);
                return;
            }
            $content = ($this->t)('Admin');
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
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $_SESSION['navbar'] = 'webmaster';
            $content = ($this->t)('Webmaster');
            $params  = ['isMyclubWebSite' => WebApp::isMyClubWebSite()];

            $this->render('Webmaster/views/webmaster.latte', $this->getAllParams([
                'newVersion'   => $this->getLastVersion(),
                'page'         => $this->application->getConnectedUser()->getPage(),
                'content'      => WebApp::getcompiledContent($content, $params),
                'previousPage' => true,
                'btn_HistoryBack' => true,
                'btn_Parent' => '/admin',
            ]));
        }
    }

    public function notifications(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $publicKey  = $this->credentials->get('vapid', 'publicKey');
            $privateKey = $this->credentials->get('vapid', 'privateKey');

            if (!$publicKey || !$privateKey) {
                $newKeys = VAPID::createVapidKeys();
                $this->credentials->set('vapid', 'publicKey', $newKeys['publicKey']);
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

            $this->render('Webmaster/views/notifications.latte', $this->getAllParams([
                'message'         => $message,
                'notification'    => $notification,
                'publicKey'       => $publicKey,
                'phpExtensions'   => [
                    'gmp'      => extension_loaded('gmp'),
                    'mbstring' => extension_loaded('mbstring'),
                ],
                'navItems'        => $this->getNavItems($this->application->getConnectedUser()->person),
                'page'            => $this->application->getConnectedUser()->getPage(),
                'currentVersion'  => Application::VERSION,
                'btn_HistoryBack' => true,
                'btn_Parent' => '/webmaster',
            ]));
        }
    }

    public function sendEmailCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->render('Webmaster/views/emailCredentials.latte', $this->getAllParams([
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
                'page'     => $this->application->getConnectedUser()->getPage(),
                'sendMethod'          => $this->credentials->get('email', 'method'),
                'smtpAccount'         => $this->credentials->get('smtp', 'username'),
                'smtpFrom'            => $this->credentials->get('smtp', 'from'),
                'smtpHost'            => $this->credentials->get('smtp', 'host'),
                'smtpPort'            => $this->credentials->get('smtp', 'port'),
                'smtpEncryption'      => $this->credentials->get('smtp', 'encryption'),
                'mailjetApiKey'       => $this->credentials->get('mailjet', 'api_key'),
                'mailjetSender'       => $this->credentials->get('mailjet', 'sender'),
                'brevoApiKey'         => $this->credentials->get('brevo', 'api_key'),
                'brevoSender'         => $this->credentials->get('brevo', 'sender'),
                'dailyLimit'          => $this->credentials->get('email', 'daily_limit'),
                'monthlyLimit'        => $this->credentials->get('email', 'monthly_limit'),
                'btn_HistoryBack' => true,
                'btn_Parent' => '/webmaster',
            ]));
        }
    }

    public function sendEmailCredentialsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $schema = [
                'sendMethod'       => FilterInputRule::Text->value,
                'smtpAccount'      => FilterInputRule::Email->value,
                'smtpPassword'     => FilterInputRule::Password->value,
                'smtpFrom'         => FilterInputRule::Email->value,
                'smtpHost'         => FilterInputRule::Uri->value,
                'smtpPort'         => FilterInputRule::Integer->value,
                'smtpEncryption'   => FilterInputRule::Text->value,
                'mailjetApiKey'    => FilterInputRule::Text->value,
                'mailjetApiSecret' => FilterInputRule::Text->value,
                'mailjetSender'    => FilterInputRule::Email->value,
                'brevoApiKey'      => FilterInputRule::Text->value,
                'brevoSender'      => FilterInputRule::Email->value,
                'dailyLimit'       => FilterInputRule::Integer->value,
                'monthlyLimit'     => FilterInputRule::Integer->value,
            ];
            $input  = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            $method = self::toStr($input['sendMethod'] ?? null, 'smtp');

            $this->credentials->set('email', 'method', $method);
            $this->credentials->set('email', 'daily_limit', self::toStr($input['dailyLimit'] ?? null, '0'));
            $this->credentials->set('email', 'monthly_limit', self::toStr($input['monthlyLimit'] ?? null, '0'));

            match ($method) {
                'smtp' => (function () use ($input) {
                    $this->credentials->set('smtp', 'username', self::toStr($input['smtpAccount'] ?? null, ''));
                    if (!empty($input['smtpPassword'])) {
                        $this->credentials->set('smtp', 'password', self::toStr($input['smtpPassword'], ''));
                    }
                    $this->credentials->set('smtp', 'from', self::toStr($input['smtpFrom'] ?? null, ''));
                    $this->credentials->set('smtp', 'host', self::toStr($input['smtpHost'] ?? null, ''));
                    $this->credentials->set('smtp', 'port', self::toStr($input['smtpPort'] ?? null, '587'));
                    $this->credentials->set('smtp', 'encryption', self::toStr($input['smtpEncryption'] ?? null, 'tls'));
                })(),

                'mailjet' => (function () use ($input) {
                    $this->credentials->set('mailjet', 'api_key', self::toStr($input['mailjetApiKey'] ?? null, ''));
                    if (!empty($input['mailjetApiSecret'])) {
                        $this->credentials->set('mailjet', 'api_secret', self::toStr($input['mailjetApiSecret'], ''));
                    }
                    $this->credentials->set('mailjet', 'sender', self::toStr($input['mailjetSender'] ?? null, ''));
                })(),

                'brevo' => (function () use ($input) {
                    if (!empty($input['brevoApiKey'])) {
                        $this->credentials->set('brevo', 'api_key', self::toStr($input['brevoApiKey'], ''));
                    }
                    $this->credentials->set('brevo', 'sender', self::toStr($input['brevoSender'] ?? null, ''));
                })(),

                default => null,
            };

            $this->redirect('/webmaster');
        }
    }

    public function showInstallations(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
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
        $base_url = htmlspecialchars(
            WebApp::getBaseUrl(),
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );
        $articles = $this->articleDataHelper->getArticlesForAll();
        $homepageLastmod = !empty($articles)
            ? (new DateTime($articles[0]->LastUpdate))->format(DateTime::ATOM)
            : (new DateTime())->format(DateTime::ATOM);

        header('Content-Type: application/xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . $base_url . '</loc>' . PHP_EOL;
        echo '    <lastmod>' . $homepageLastmod . '</lastmod>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        foreach ($articles as $article) {
            try {
                $lastmod = (new DateTime($article->LastUpdate))->format(DateTime::ATOM);
            } catch (\Exception $e) {
                $lastmod = (new DateTime())->format(DateTime::ATOM);
            }
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . $base_url . '/article/' . (int)$article->Id . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }

        echo '</urlset>';
    }

    public function turnstileCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->render('Webmaster/views/turnstileCredentials.latte', $this->getAllParams([
                'navItems'           => $this->getNavItems($this->application->getConnectedUser()->person),
                'page'               => $this->application->getConnectedUser()->getPage(),
                'turnstileSiteKey'   => $this->credentials->get('turnstile', 'site_key'),
                'turnstileConfigured' => !empty($this->credentials->get('turnstile', 'secret_key')),
                'btn_HistoryBack' => true,
                'btn_Parent' => '/webmaster',
            ]));
        }
    }

    public function turnstileCredentialsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $schema = [
                'turnstileSiteKey'   => FilterInputRule::Text->value,
                'turnstileSecretKey' => FilterInputRule::Text->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $this->credentials->set('turnstile', 'site_key', self::toStr($input['turnstileSiteKey'] ?? null, ''));
            if (!empty($input['turnstileSecretKey'])) {
                $this->credentials->set('turnstile', 'secret_key', self::toStr($input['turnstileSecretKey'], ''));
            }

            $this->redirect('/webmaster');
        }
    }

    public function helloassoCredentialsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->render('Webmaster/views/helloassoCredentials.latte', $this->getAllParams([
                'navItems'               => $this->getNavItems($this->application->getConnectedUser()->person),
                'page'                   => $this->application->getConnectedUser()->getPage(),

                'helloassoClientId'     => $this->credentials->get('helloasso', 'client_id'),
                'helloassoConfigured'   => !empty($this->credentials->get('helloasso', 'client_secret')),
                'helloassoOrgSlug'      => $this->credentials->get('helloasso', 'org_slug'),

                'btn_HistoryBack'        => true,
                'btn_Parent'             => '/webmaster',
            ]));
        }
    }

    public function helloassoCredentialsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $schema = [
                'helloassoClientId'     => FilterInputRule::Text->value,
                'helloassoClientSecret' => FilterInputRule::Text->value,
                'helloassoOrgSlug'      => FilterInputRule::Text->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $this->credentials->set('helloasso', 'client_id', self::toStr($input['helloassoClientId'] ?? null, ''));
            if (!empty($input['helloassoClientSecret'])) {
                $this->credentials->set('helloasso', 'client_secret', self::toStr($input['helloassoClientSecret'], ''));
            }
            if (!empty($input['helloassoOrgSlug'])) {
                $this->credentials->set('helloasso', 'org_slug', self::toStr($input['helloassoOrgSlug'], ''));
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
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $response = curl_exec($ch);
        if (!is_string($response)) {
            $error = curl_error($ch);
            unset($ch);
            return "Test for MyClub new version error : Erreur cURL ($error)";
        }
        unset($ch);
        $data = json_decode($response, true);
        if (
            !is_array($data)
            || ($data['success'] ?? false) !== true
            || !is_array($data['data'] ?? null)
            || !isset($data['data']['lastVersion'])
            || !is_string($data['data']['lastVersion'])
        ) {
            return "Test for MyClub new version error : Réponse API invalide.";
        }
        $lastVersion = $data['data']['lastVersion'];
        return $lastVersion === Application::VERSION ? null : "A new version is available (V{$lastVersion})";
    }
}
