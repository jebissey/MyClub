<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\NotificationSender;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\LogDataHelper;
use app\modules\Common\TableController;
use app\modules\Webmaster\services\InstallationDisplayService;
use app\modules\Webmaster\services\InstallationStatusFilter;
use app\modules\Webmaster\services\SitemapBuilder;
use app\modules\Webmaster\services\VapidKeyService;
use app\modules\Webmaster\services\VersionChecker;
use app\modules\Webmaster\viewModels\AdminHomeViewModel;
use app\modules\Webmaster\viewModels\ClubCustomizationViewModel;
use app\modules\Webmaster\viewModels\SitemapViewModel;
use app\modules\Webmaster\viewModels\WebmasterHomeViewModel;
use app\modules\Webmaster\viewModels\WebmasterInstallationsViewModel;
use app\modules\Webmaster\viewModels\WebmasterNotificationsViewModel;

final class WebmasterController extends TableController
{
    public function __construct(
        Application $application,
        private readonly LogDataHelper $logDataHelper,
        private readonly NotificationSender $notificationSender,
        private readonly InstallationDisplayService $installationDisplayService,
        private readonly InstallationStatusFilter $installationStatusFilter,
        private readonly VersionChecker $versionChecker,
        private readonly VapidKeyService $vapidKeyService,
        private readonly SitemapBuilder $sitemapBuilder
    ) {
        parent::__construct($application);
    }

    public function clubCustomizationEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $viewModel = new ClubCustomizationViewModel(
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                settings: [
                    'clubName'      => $this->dataHelper->getSetting('PWA_Name', 'MyClub'),
                    'clubShortName' => $this->dataHelper->getSetting('PWA_ShortName', 'MyClub'),
                    'themeColor'    => $this->dataHelper->getSetting('PWA_ThemeColor', '#0d6efd'),
                    'background'    => $this->dataHelper->getSetting('PWA_BackgroundColor', '#ffffff'),
                ],
                layoutParams: $this->getAllParams([]),
            );

            $this->views->render('Webmaster/views/clubCustomization.latte', $viewModel);
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

            $this->dataHelper->setSetting('PWA_Name', To::str($input['clubName'] ?? null, 'MyClub'));
            $this->dataHelper->setSetting('PWA_ShortName', To::str($input['clubShortName'] ?? null, 'MyClub'));
            $this->dataHelper->setSetting('PWA_ThemeColor', To::str($input['themeColor'] ?? null, '#0d6efd'));
            $this->dataHelper->setSetting('PWA_BackgroundColor', To::str($input['background'] ?? null, '#ffffff'));
            $this->redirect('/webmaster');
        }
    }

    public function helpAdmin(): void
    {
        $this->renderHelp('Help_Admin', fn($u) => $u->isAdministrator(), __FILE__, __LINE__);
    }

    public function helpWebmaster(): void
    {
        $this->renderHelp('Help_Webmaster', fn($u) => $u->isWebmaster(), __FILE__, __LINE__);
    }

    public function homeAdmin(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $connectedUser = $this->application->getConnectedUser();

        if (!$connectedUser->isAdministrator()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        if ($connectedUser->hasOnlyOneAutorization()) {
            $this->redirectToSingleAuthorizationHome($connectedUser);
            return;
        }

        $this->views->render(
            'Webmaster/views/admin.latte',
            new AdminHomeViewModel(
                content: '',
                layoutParams: $this->getAllParams([]),
            )
        );
    }

    public function homeWebmaster(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $_SESSION['navbar'] = 'webmaster';

            $viewModel = new WebmasterHomeViewModel(
                newVersion: $this->versionChecker->getNewVersionMessage(),
                content: '',
                previousPage: true,
                layoutParams: $this->getAllParams([]),
            );
            $this->views->render('Webmaster/views/webmaster.latte', $viewModel);
        }
    }

    public function notifications(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $vapid     = $this->vapidKeyService->ensureKeys();
            $publicKey = $vapid['publicKey'];
            $message   = $vapid['generated']
                ? "✅ Les clés VAPID ont été générées et enregistrées avec succès."
                : "ℹ️ Les clés VAPID existent déjà dans la base de données.";

            $notification = '';
            if ($this->input->hasQuery('test')) {
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

            $viewModel = new WebmasterNotificationsViewModel(
                message: $message,
                notification: $notification,
                publicKey: $publicKey,
                phpExtensions: [
                    'gmp'      => extension_loaded('gmp'),
                    'mbstring' => extension_loaded('mbstring'),
                ],
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->views->render('Webmaster/views/notifications.latte', $viewModel);
        }
    }

    public function showInstallations(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            return;
        }

        $connectedUser = $this->application->getConnectedUser();

        $schema = [
            'Host'           => FilterInputRule::Text->value,
            'webappVersions' => FilterInputRule::String->value,
            'phpVersions'    => FilterInputRule::String->value,
            'lastCheck'      => FilterInputRule::DateTime->value,
            'status'         => FilterInputRule::String->value,
        ];
        $filterValues = $this->readFilterValues($schema);

        $filterConfig = [
            ['name' => 'Host',           'label' => ($this->t)('webmaster.installations.label.host')],
            ['name' => 'webappVersions', 'label' => ($this->t)('webmaster.installations.label.webapp_version')],
            ['name' => 'phpVersions',    'label' => ($this->t)('webmaster.installations.label.php_version')],
            ['name' => 'lastCheck',      'label' => ($this->t)('webmaster.installations.label.last_check')],
            ['name' => 'status',         'label' => ($this->t)('webmaster.installations.label.status')],
        ];

        $query = $this->logDataHelper->getInstallations();

        $sqlFilters = $filterValues;
        unset($sqlFilters['status']);

        $statusFilter = To::str($filterValues['status'] ?? null, '');
        if ($statusFilter !== '') {
            $query = $query->where($this->installationStatusFilter->buildCondition($statusFilter, $this->t));
        }

        $data = $this->prepareTableData($query, $sqlFilters, true);

        $columns = [
            ['field' => 'Host',           'label' => ($this->t)('webmaster.installations.label.host')],
            ['field' => 'webappVersions', 'label' => ($this->t)('webmaster.installations.label.webapp_version')],
            ['field' => 'phpVersions',    'label' => ($this->t)('webmaster.installations.label.php_version')],
            ['field' => 'lastCheck',      'label' => ($this->t)('webmaster.installations.label.last_check')],
            ['field' => 'timeAgo',        'label' => ($this->t)('webmaster.installations.label.time_ago')],
            ['field' => 'checkCount',     'label' => ($this->t)('webmaster.installations.label.check_count')],
            ['field' => 'status',         'label' => ($this->t)('webmaster.installations.label.status')],
        ];

        $viewModel = new WebmasterInstallationsViewModel(
            installations: $this->installationDisplayService->enrich($data['items'], $this->t),
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            filters: $filterConfig,
            columns: $columns,
            resetUrl: '/installations',
            navItems: $this->getNavItems($connectedUser->person?->email),
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
            ]),
        );

        $this->views->render('Webmaster/views/installations.latte', $viewModel);
    }

    public function sitemapGenerator(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $baseUrl = htmlspecialchars(
            WebApp::getBaseUrl(),
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );

        header('Content-Type: application/xml; charset=utf-8');
        $this->views->render(
            'Webmaster/views/sitemap.latte',
            new SitemapViewModel($this->sitemapBuilder->build($baseUrl))
        );
    }

    #region Private methods
    private function redirectToSingleAuthorizationHome(ConnectedUser $user): void
    {
        if ($user->isRedactor()) {
            $_SESSION['navbar'] = 'redactor';
            $this->redirect('/articles');
            return;
        }

        $route = match (true) {
            $user->isDesigner()        => '/designer',
            $user->isEventManager()    => '/eventManager',
            $user->isPersonManager()   => '/personManager',
            $user->isVisitorInsights() => '/visitorInsights',
            $user->isWebmaster()       => '/webmaster',
            default                    => null,
        };

        if ($route !== null) {
            $this->redirect($route);
        }
    }
}
