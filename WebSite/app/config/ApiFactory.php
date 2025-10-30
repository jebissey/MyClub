<?php

declare(strict_types=1);

namespace app\config;

use app\apis\ArticleApi;
use app\apis\CarouselApi;
use app\apis\EventApi;
use app\apis\EventAttributeApi;
use app\apis\EventNeedApi;
use app\apis\EventNeedTypeApi;
use app\apis\EventSupplyApi;
use app\apis\GroupApi;
use app\apis\ImportApi;
use app\apis\KaraokeApi;
use app\apis\MediaApi;
use app\apis\MessageApi;
use app\apis\NavbarApi;
use app\apis\WebmasterApi;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\PersonPreferences;
use app\models\ArticleDataHelper;
use app\models\AttributeDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\CarouselDataHelper;
use app\models\DataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\EventNeedDataHelper;
use app\models\KaraokeDataHelper;
use app\models\LogDataHelper;
use app\models\MessageDataHelper;
use app\models\NeedDataHelper;
use app\models\NeedTypeDataHelper;
use app\models\PageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\models\SharedFileDataHelper;
use app\services\AuthorizationService;
use app\services\EmailService;
use app\services\EventService;

class ApiFactory
{
    public function __construct(
        private Application $application,
        private ArticleDataHelper $articleDataHelper,
        private AttributeDataHelper $attributeDataHelper,
        private AuthorizationDataHelper $authorizationDataHelper,
        private CarouselDataHelper $carouselDataHelper,
        private ConnectedUser $connectedUser,
        private DataHelper $dataHelper,        
        private EmailService $emailService,
        private EventDataHelper $eventDataHelper,
        private EventNeedDataHelper $eventNeedDataHelper,
        private EventService $eventService,
        private KaraokeDataHelper $karaokeDataHelper,
        private LogDataHelper $logDataHelper,
        private MessageDataHelper $messageDataHelper,
        private NeedDataHelper $needDataHelper,
        private NeedTypeDataHelper $needTypeDataHelper,
        private DesignDataHelper $designDataHelper,
        private PageDataHelper $pageDataHelper,
        private ParticipantDataHelper $participantDataHelper,
        private PersonDataHelper $personDataHelper,
        private PersonPreferences $personPreferences,
        private SharedFileDataHelper $sharedFileDataHelper,
    ) {}

    public function makeArticleApi(): ArticleApi
    {
        return new ArticleApi(
            $this->application,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper,
            $this->designDataHelper,
            $this->articleDataHelper
        );
    }

    public function makeCarouselApi(): CarouselApi
    {
        return new CarouselApi(
            $this->application,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper,
            $this->authorizationDataHelper,
            $this->carouselDataHelper
        );
    }

    public function makeEventApi(): EventApi
    {
        return new EventApi(
            $this->application,
            new AuthorizationService($this->connectedUser),
            $this->eventDataHelper,
            $this->eventService,
            $this->participantDataHelper,
            $this->personPreferences,
            $this->messageDataHelper,
            $this->emailService,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeEventAttributeApi(): EventAttributeApi
    {
        return new EventAttributeApi(
            $this->application,
            $this->attributeDataHelper,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeEventNeedApi(): EventNeedApi
    {
        return new EventNeedApi(
            $this->application,
            $this->eventNeedDataHelper,
            $this->eventDataHelper,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeEventNeedTypeApi(): EventNeedTypeApi
    {
        return new EventNeedTypeApi(
            $this->application,
            $this->needDataHelper,
            $this->needTypeDataHelper,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeEventSupplyApi(): EventSupplyApi
    {
        return new EventSupplyApi(
            $this->application,
            $this->eventDataHelper,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeGroupApi(): GroupApi
    {
        return new GroupApi(
            $this->application,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeImportApi(): ImportApi
    {
        return new ImportApi(
            $this->application,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeKaraokeApi(): KaraokeApi
    {
        return new KaraokeApi(
            $this->application,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper,
            $this->karaokeDataHelper
        );
    }

    public function makeMediaApi(): MediaApi
    {
        return new MediaApi(
            $this->application,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper,
            $this->sharedFileDataHelper
        );
    }

    public function makeMessageApi(): MessageApi
    {
        return new MessageApi(
            $this->application,
            $this->messageDataHelper,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeNavbarApi(): NavbarApi
    {
        return new NavbarApi(
            $this->application,
            $this->pageDataHelper,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper
        );
    }

    public function makeWebmasterApi(): WebmasterApi
    {
        return new WebmasterApi(
            $this->application,
            $this->connectedUser,
            $this->dataHelper,
            $this->personDataHelper,
            $this->logDataHelper
        );
    }
}
