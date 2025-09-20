<?php

declare(strict_types=1);

namespace app\config;

use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\ErrorManager;
use app\helpers\WebApp;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\CrosstabDataHelper;
use app\models\DbBrowserDataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\GenericDataHelper;
use app\models\MessageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Article\ArticleController;
use app\modules\Designer\DesignController;
use app\modules\Designer\DesignerController;
use app\modules\Event\EventController;
use app\modules\Event\EventEmailController;
use app\modules\Event\EventGuestController;
use app\modules\Event\EventTypeController;
use app\modules\User\ContactController;
use app\modules\Webmaster\ArwardsController;
use app\modules\Webmaster\DbBrowserController;
use app\services\EmailService;

class ControllerFactory
{
    public function __construct(
        private Application $application,
        private ArticleCrosstabDataHelper $articleCrosstabDataHelper,
        private ArticleDataHelper $articleDataHelper,
        private ArticleTableDataHelper $articleTableDataHelper,
        private Backup $backup,
        private CrosstabDataHelper $crosstabDataHelper,
        private DbBrowserDataHelper $dbBrowserDataHelper,
        private DesignDataHelper $designDataHelper,
        private EmailService $emailService,
        private ErrorManager $errorManager,
        private EventDataHelper $eventDataHelper,
        private GenericDataHelper $genericDataHelper,
        private MessageDataHelper $messageDataHelper,
        private ParticipantDataHelper $participantDataHelper,
        private PersonDataHelper $personDataHelper,
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
            $this->genericDataHelper
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
            $this->eventDataHelper
        );
    }

    public function makeEventTypeController(): EventTypeController
    {
        return new EventTypeController(
            $this->application,
            $this->eventDataHelper,
            $this->tableControllerDataHelper,
            $this->errorManager,
            $this->genericDataHelper
        );
    }


}
