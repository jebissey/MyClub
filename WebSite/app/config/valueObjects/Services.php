<?php

declare(strict_types=1);

namespace app\config\valueObjects;

use app\helpers\MediaManager;
use app\helpers\News;
use app\helpers\NotificationSender;
use app\helpers\PersonPreferences;
use app\models\PersonDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Common\services\AnonymousAccessService;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\services\EmailService;
use app\modules\PersonManager\services\MemberCsvImporter;

/**
 * Services shared by several factories, built once by Routes::buildServices().
 */
final readonly class Services
{
    public function __construct(
        public AnonymousAccessService $anonymousAccessService,
        public ArticleAuthorizationService $articleAuthorizationService,
        public AuthenticationService $authenticationService,
        public EmailService $emailService,
        public MediaManager $mediaManager,
        public MemberCsvImporter $memberCsvImporter,
        public News $news,
        public NotificationSender $notificationSender,
        public PersonDataHelper $personDataHelper,
        public PersonPreferences $personPreferences,
    ) {
    }
}
