<?php

declare(strict_types=1);

namespace app\config\valueObjects;

use app\models\ArticleDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\CrosstabDataHelper;
use app\models\DataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\GroupDataHelper;
use app\models\LanguagesDataHelper;
use app\models\LoanDataHelper;
use app\models\LogDataHelper;
use app\models\MemberImportDataHelper;
use app\models\MembershipDataHelper;
use app\models\MessageDataHelper;
use app\models\NeedDataHelper;
use app\models\OrderDataHelper;
use app\models\ParticipantDataHelper;
use app\models\SharedFileDataHelper;
use app\models\SurveyDataHelper;

/**
 * Data helpers shared by several factories, built once by Routes::buildDataHelpers().
 */
final readonly class DataHelpers
{
    public function __construct(
        public ArticleDataHelper $articleDataHelper,
        public AuthorizationDataHelper $authorizationDataHelper,
        public CrosstabDataHelper $crosstabDataHelper,
        public DataHelper $dataHelper,
        public DesignDataHelper $designDataHelper,
        public EventDataHelper $eventDataHelper,
        public GroupDataHelper $groupDataHelper,
        public LanguagesDataHelper $languagesDataHelper,
        public LoanDataHelper $loanDataHelper,
        public LogDataHelper $logDataHelper,
        public MemberImportDataHelper $memberImportDataHelper,
        public MembershipDataHelper $membershipDataHelper,
        public MessageDataHelper $messageDataHelper,
        public NeedDataHelper $needDataHelper,
        public OrderDataHelper $orderDataHelper,
        public ParticipantDataHelper $participantDataHelper,
        public SharedFileDataHelper $sharedFileDataHelper,
        public SurveyDataHelper $surveyDataHelper,
    ) {
    }
}
