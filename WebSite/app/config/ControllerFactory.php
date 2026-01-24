<?php

declare(strict_types=1);

namespace app\config;

use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\ErrorManager;
use app\helpers\News;
use app\helpers\NotificationSender;
use app\helpers\WebApp;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\CarouselDataHelper;
use app\models\CrosstabDataHelper;
use app\models\DbBrowserDataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\EventTypeDataHelper;
use app\models\GroupDataHelper;
use app\models\ImportDataHelper;
use app\models\KanbanDataHelper;
use app\models\LogDataHelper;
use app\models\LogDataAnalyticsHelper;
use app\models\LogDataStatisticsHelper;
use app\models\MessageDataHelper;
use app\models\MetadataDataHelper;
use app\models\NeedDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\models\PersonGroupDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\models\SharedFileDataHelper;
use app\models\SurveyDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Article\ArticleController;
use app\modules\Article\MediaController;
use app\modules\Article\SurveyController;
use app\modules\Designer\DesignController;
use app\modules\Designer\DesignerController;
use app\modules\Designer\NavBarController;
use app\modules\Event\EventController;
use app\modules\Event\EventEmailController;
use app\modules\Event\EventGuestController;
use app\modules\Event\EventNeedController;
use app\modules\Event\EventTypeController;
use app\modules\Games\Karaoke\KaraokeController;
use app\modules\Games\Leapfrog\LeapfrogController;
use app\modules\Games\Solfege\SolfegeController;
use app\modules\Kanban\KanbanController;
use app\modules\PersonManager\GroupController;
use app\modules\PersonManager\ImportController;
use app\modules\PersonManager\PersonController;
use app\modules\PersonManager\RegistrationController;
use app\modules\User\ContactController;
use app\modules\User\HomeController;
use app\modules\User\FFAController;
use app\modules\User\UserController;
use app\modules\User\UserAccountController;
use app\modules\User\UserAvailabilitiesController;
use app\modules\User\UserConnectionsController;
use app\modules\User\UserDashboardController;
use app\modules\User\UserDirectoryController;
use app\modules\User\UserGroupsController;
use app\modules\User\UserMessagesController;
use app\modules\User\UserNewsController;
use app\modules\User\UserNotepadController;
use app\modules\User\UserNotificationsController;
use app\modules\User\UserPreferencesController;
use app\modules\User\UserPresentationController;
use app\modules\User\UserStatisticsController;
use app\modules\VisitorInsights\VisitorInsightsController;
use app\modules\Webmaster\ArwardsController;
use app\modules\Webmaster\DbBrowserController;
use app\modules\Webmaster\MaintenanceController;
use app\modules\Webmaster\RssController;
use app\modules\Webmaster\WebappSettingsController;
use app\modules\Webmaster\WebmasterController;
use app\services\AuthenticationService;
use app\services\EmailService;

class ControllerFactory
{
    public function __construct(
        private Application $application,
        private ArticleCrosstabDataHelper $articleCrosstabDataHelper,
        private ArticleDataHelper $articleDataHelper,
        private ArticleTableDataHelper $articleTableDataHelper,
        private AuthenticationService $authenticationService,
        private Backup $backup,
        private CarouselDataHelper $carouselDataHelper,
        private CrosstabDataHelper $crosstabDataHelper,
        private DbBrowserDataHelper $dbBrowserDataHelper,
        private DesignDataHelper $designDataHelper,
        private EmailService $emailService,
        private ErrorManager $errorManager,
        private EventDataHelper $eventDataHelper,
        private EventTypeDataHelper $eventTypeDataHelper,
        private GroupDataHelper $groupDataHelper,
        private ImportDataHelper $importDataHelper,
        private KanbanDataHelper $kanbanDataHelper,
        private LogDataHelper $logDataHelper,
        private MessageDataHelper $messageDataHelper,
        private MetadataDataHelper $metadataDataHelper,
        private NeedDataHelper $needDataHelper,
        private News $news,
        private NotificationSender $notificationSender,
        private ParticipantDataHelper $participantDataHelper,
        private PersonDataHelper $personDataHelper,
        private PersonGroupDataHelper $personGroupDataHelper,
        private PersonStatisticsDataHelper $personStatisticsDataHelper,
        private SharedFileDataHelper $sharedFileDataHelper,
        private SurveyDataHelper $surveyDataHelper,
        private TableControllerDataHelper $tableControllerDataHelper,
        private WebApp $webapp,
    ) {}

    public function makeArticleController(): ArticleController
    {
        return new ArticleController(
            $this->application,
            $this->articleDataHelper,
            $this->articleTableDataHelper,
            $this->personDataHelper,
            $this->backup,
            $this->articleCrosstabDataHelper,
            $this->messageDataHelper,
            $this->emailService
        );
    }

    public function makeArwardsController(): ArwardsController
    {
        return new ArwardsController($this->application);
    }

    public function makeDbBrowserController(): DbBrowserController
    {
        return new DbBrowserController($this->application, $this->dbBrowserDataHelper);
    }

    public function makeContactController(): ContactController
    {
        return new ContactController(
            $this->application,
            $this->emailService,
            $this->personDataHelper,
            $this->webapp
        );
    }

    public function makeDesignController(): DesignController
    {
        return new DesignController($this->application, $this->designDataHelper);
    }

    public function makeDesignerController(): DesignerController
    {
        return new DesignerController($this->application);
    }

    public function makeEventController(): EventController
    {
        return new EventController(
            $this->application,
            $this->eventDataHelper,
            $this->crosstabDataHelper,
            $this->participantDataHelper,
            $this->messageDataHelper
        );
    }

    public function makeEventEmailController(): EventEmailController
    {
        return new EventEmailController(
            $this->application,
            $this->personDataHelper
        );
    }

    public function makeEventGuestController(): EventGuestController
    {
        return new EventGuestController(
            $this->application,
            $this->eventDataHelper,
            $this->emailService
        );
    }

    public function makeEventNeedController(): EventNeedController
    {
        return new EventNeedController(
            $this->application,
            $this->needDataHelper
        );
    }

    public function makeEventTypeController(): EventTypeController
    {
        return new EventTypeController(
            $this->application,
            $this->eventTypeDataHelper,
            $this->tableControllerDataHelper,
            $this->errorManager,
        );
    }

    public function makeFfaController(): FFAController
    {
        return new FFAController($this->application);
    }

    public function makeGroupController(): GroupController
    {
        return new GroupController(
            $this->application,
            $this->groupDataHelper,
            $this->personGroupDataHelper,
            $this->messageDataHelper
        );
    }

    public function makeHomeController(): HomeController
    {
        return new HomeController(
            $this->application,
            $this->articleDataHelper,
            $this->surveyDataHelper,
            $this->designDataHelper,
            $this->news,
            $this->personDataHelper,
            $this->metadataDataHelper
        );
    }

    public function makeImportController(): ImportController
    {
        return new ImportController(
            $this->application,
            $this->importDataHelper
        );
    }

    public function makeKanbanController(): KanbanController
    {
        return new KanbanController(
            $this->application,
            $this->kanbanDataHelper
        );
    }

    public function makeKaraokeController(): KaraokeController
    {
        return new KaraokeController($this->application);
    }

    public function makeLeapfrogController(): LeapfrogController
    {
        return new LeapfrogController(
            $this->application,
            $this->tableControllerDataHelper
        );
    }

    public function makeMaintenanceController(): MaintenanceController
    {
        return new MaintenanceController(
            $this->application,
            $this->errorManager
        );
    }

    public function makeMediaController(): MediaController
    {
        return new MediaController(
            $this->application,
            $this->articleDataHelper,
            $this->carouselDataHelper,
            $this->personGroupDataHelper,
            $this->sharedFileDataHelper
        );
    }

    public function makeNavBarController(): NavBarController
    {
        return new NavBarController($this->application);
    }

    public function makePersonController(): PersonController
    {
        return new PersonController(
            $this->application,
            $this->tableControllerDataHelper,
            $this->personDataHelper,
        );
    }

    public function makeRegistrationController(): RegistrationController
    {
        return new RegistrationController(
            $this->application,
            $this->tableControllerDataHelper,
            $this->groupDataHelper,
        );
    }

    public function makeRssController(): RssController
    {
        return new RssController(
            $this->application,
            $this->articleDataHelper,
            $this->eventDataHelper
        );
    }

    public function makeSurveyController(): SurveyController
    {
        return new SurveyController(
            $this->application,
            $this->surveyDataHelper
        );
    }

    public function makeSolfegeController(): SolfegeController
    {
        return new SolfegeController($this->application);
    }

    public function makeUserController(): UserController
    {
        return new UserController(
            $this->application,
            $this->authenticationService
        );
    }

    public function makeUserAccountController(): UserAccountController
    {
        return new UserAccountController($this->application);
    }

    public function makeUserAvailabilitiesController(): UserAvailabilitiesController
    {
        return new UserAvailabilitiesController($this->application);
    }

    public function makeUserConnectionsController(): UserConnectionsController
    {
        return new UserConnectionsController(
            $this->application,
            $this->participantDataHelper,
        );
    }

    public function makeUserDashboardController(): UserDashboardController
    {
        return new UserDashboardController($this->application);
    }

    public function makeUserDirectoryController(): UserDirectoryController
    {
        return new UserDirectoryController(
            $this->application,
            $this->personDataHelper,
            $this->groupDataHelper,
            $this->personGroupDataHelper
        );
    }

    public function makeUserGroupsController(): UserGroupsController
    {
        return new UserGroupsController(
            $this->application,
            $this->personGroupDataHelper,
            $this->groupDataHelper
        );
    }

    public function makeUserMessagesController(): UserMessagesController
    {
        return new UserMessagesController(
            $this->application,
            $this->messageDataHelper
        );
    }

    public function makeUserNewsController(): UserNewsController
    {
        return new UserNewsController(
            $this->application,
            $this->news
        );
    }

    public function makeUserNotepadController(): UserNotepadController
    {
        return new UserNotepadController($this->application);
    }

    public function makeUserNotificationsController(): UserNotificationsController
    {
        return new UserNotificationsController(
            $this->application,
            $this->groupDataHelper,
            $this->notificationSender
        );
    }

    public function makeUserPreferencesController(): UserPreferencesController
    {
        return new UserPreferencesController(
            $this->application,
            $this->eventTypeDataHelper
        );
    }

    public function makeUserPresentationController(): UserPresentationController
    {
        return new UserPresentationController($this->application);
    }

    public function makeUserStatisticsController(): UserStatisticsController
    {
        return new UserStatisticsController(
            $this->application,
            $this->personStatisticsDataHelper,
            $this->logDataHelper
        );
    }

    public function makeWebappSettingsController(): WebappSettingsController
    {
        return new WebappSettingsController($this->application);
    }

    public function makeVisitorInsightsController(): VisitorInsightsController
    {
        return new VisitorInsightsController(
            $this->application,
            $this->personDataHelper,
            $this->logDataHelper,
            $this->crosstabDataHelper,
            new LogDataAnalyticsHelper($this->application),
            new LogDataStatisticsHelper($this->application)
        );
    }

    public function makeWebmasterController(): WebmasterController
    {
        return new WebmasterController(
            $this->application,
            $this->logDataHelper,
            $this->articleDataHelper,
            $this->notificationSender
        );
    }
}
