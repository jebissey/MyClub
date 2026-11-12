<?php

declare(strict_types=1);

namespace app\config\valueObjects;

use app\helpers\MediaManager;
use app\helpers\News;
use app\helpers\NotificationSender;
use app\helpers\PersonPreferences;
use app\models\PersonDataHelper;
use app\modules\Article\services\ArticleAlertService;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Common\services\AnonymousAccessService;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\services\EmailService;
use app\modules\Loan\services\LoanAvailabilityService;
use app\modules\Loan\services\LoanCalendarService;
use app\modules\PersonManager\services\MemberCsvImporter;
use app\modules\Webmaster\services\InstallationDisplayService;

/**
 * Services shared by several factories, built once by Routes::buildServices().
 */
final readonly class Services
{
    public function __construct(
        public AnonymousAccessService $anonymousAccessService,
        public ArticleAlertService $articleAlertService,
        public ArticleAuthorizationService $articleAuthorizationService,
        public AuthenticationService $authenticationService,
        public EmailService $emailService,
        public InstallationDisplayService $installationDisplayService,
        public LoanAvailabilityService $loanAvailabilityService,
        public LoanCalendarService $loanCalendarService,
        public MediaManager $mediaManager,
        public MemberCsvImporter $memberCsvImporter,
        public News $news,
        public NotificationSender $notificationSender,
        public PersonDataHelper $personDataHelper,
        public PersonPreferences $personPreferences,
    ) {
    }
}
