<?php

declare(strict_types=1);

namespace app\config\valueObjects;

use app\models\AlertRecipientDataHelper;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\AttributeDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\AvailabilityDataHelper;
use app\models\CarouselDataHelper;
use app\models\ChatMessageDataHelper;
use app\models\ContactDataHelper;
use app\models\CrosstabDataHelper;
use app\models\DataHelper;
use app\models\DbBrowserDataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\EventInvitationDataHelper;
use app\models\EventListDataHelper;
use app\models\EventNeedDataHelper;
use app\models\EventNewsDataHelper;
use app\models\EventParticipationDataHelper;
use app\models\EventTypeDataHelper;
use app\models\ExerciseTableDataHelper;
use app\models\GroupDataHelper;
use app\models\KanbanDataHelper;
use app\models\KaraokeDataHelper;
use app\models\LanguagesDataHelper;
use app\models\LoanItemDataHelper;
use app\models\LoanRecordDataHelper;
use app\models\LoanReservationDataHelper;
use app\models\LogAnalyticsDataHelper;
use app\models\LogStatisticsDataHelper;
use app\models\LogDataHelper;
use app\models\LogWriterDataHelper;
use app\models\MemberDataHelper;
use app\models\MemberDirectoryDataHelper;
use app\models\MemberImportDataHelper;
use app\models\MembershipDataHelper;
use app\models\MenuItemDataHelper;
use app\models\MessageDataHelper;
use app\models\MessageImageDataHelper;
use app\models\MessageStatisticsDataHelper;
use app\models\MessageWriteDataHelper;
use app\models\MetadataDataHelper;
use app\models\NeedDataHelper;
use app\models\NeedTypeDataHelper;
use app\models\OrderDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\models\PersonGroupDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\models\PresentationNewsDataHelper;
use app\models\SharedFileDataHelper;
use app\models\SurveyDataHelper;
use app\models\TableControllerDataHelper;

/**
 * Every data helper of the application, built once by Routes::buildDataHelpers().
 */
final readonly class DataHelpers
{
    public function __construct(
        public AlertRecipientDataHelper $alertRecipientDataHelper,
        public ArticleCrosstabDataHelper $articleCrosstabDataHelper,
        public ArticleDataHelper $articleDataHelper,
        public ArticleTableDataHelper $articleTableDataHelper,
        public AttributeDataHelper $attributeDataHelper,
        public AuthorizationDataHelper $authorizationDataHelper,
        public AvailabilityDataHelper $availabilityDataHelper,
        public CarouselDataHelper $carouselDataHelper,
        public ChatMessageDataHelper $chatMessageDataHelper,
        public ContactDataHelper $contactDataHelper,
        public CrosstabDataHelper $crosstabDataHelper,
        public DataHelper $dataHelper,
        public DbBrowserDataHelper $dbBrowserDataHelper,
        public DesignDataHelper $designDataHelper,
        public EventDataHelper $eventDataHelper,
        public EventInvitationDataHelper $eventInvitationDataHelper,
        public EventListDataHelper $eventListDataHelper,
        public EventNeedDataHelper $eventNeedDataHelper,
        public EventNewsDataHelper $eventNewsDataHelper,
        public EventParticipationDataHelper $eventParticipationDataHelper,
        public EventTypeDataHelper $eventTypeDataHelper,
        public ExerciseTableDataHelper $exerciseTableDataHelper,
        public GroupDataHelper $groupDataHelper,
        public KanbanDataHelper $kanbanDataHelper,
        public KaraokeDataHelper $karaokeDataHelper,
        public LanguagesDataHelper $languagesDataHelper,
        public LoanItemDataHelper $loanItemDataHelper,
        public LoanRecordDataHelper $loanRecordDataHelper,
        public LoanReservationDataHelper $loanReservationDataHelper,
        public LogAnalyticsDataHelper $logAnalyticsDataHelper,
        public LogStatisticsDataHelper $logStatisticsDataHelper,
        public LogDataHelper $logDataHelper,
        public LogWriterDataHelper $logWriterDataHelper,
        public MemberDataHelper $memberDataHelper,
        public MemberDirectoryDataHelper $memberDirectoryDataHelper,
        public MemberImportDataHelper $memberImportDataHelper,
        public MembershipDataHelper $membershipDataHelper,
        public MenuItemDataHelper $menuItemDataHelper,
        public MessageDataHelper $messageDataHelper,
        public MessageImageDataHelper $messageImageDataHelper,
        public MessageStatisticsDataHelper $messageStatisticsDataHelper,
        public MessageWriteDataHelper $messageWriteDataHelper,
        public MetadataDataHelper $metadataDataHelper,
        public NeedDataHelper $needDataHelper,
        public NeedTypeDataHelper $needTypeDataHelper,
        public OrderDataHelper $orderDataHelper,
        public ParticipantDataHelper $participantDataHelper,
        public PersonDataHelper $personDataHelper,
        public PersonGroupDataHelper $personGroupDataHelper,
        public PersonStatisticsDataHelper $personStatisticsDataHelper,
        public PresentationNewsDataHelper $presentationNewsDataHelper,
        public SharedFileDataHelper $sharedFileDataHelper,
        public SurveyDataHelper $surveyDataHelper,
        public TableControllerDataHelper $tableControllerDataHelper,
    ) {
    }
}
