<?php

declare(strict_types=1);

namespace app\apis;

use DateTime;
use DateTimeZone;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\LogDataHelper;
use app\models\MemberDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\valueObjects\Person;

final class ChatApi extends AbstractApi
{
    private const ACTIVE_WINDOW_MINUTES = 15;

    public function __construct(
        Application $application,
        protected ConnectedUser $connectedUser,
        PersonDataHelper $personDataHelper,
        private LogDataHelper $logDataHelper,
        private GravatarHandler $gravatarHandler,
        private MessageDataHelper $messageDataHelper,
        private MemberDataHelper $memberDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper);
    }

    public function getActiveUsers(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $previousLogId = To::int($_SESSION['last_log_id'] ?? null);
        $minutes = To::int($_GET['m'] ?? null, self::ACTIVE_WINDOW_MINUTES);

        $activePersons = $this->memberDataHelper->getActiveMembersBasicInfo();
        $visits = $this->logDataHelper->getLastVisitPerActivePersonWithTimeAgo($activePersons);

        $cutoff = new DateTime("-{$minutes} minutes", new DateTimeZone('UTC'));
        $result = [];
        foreach ($visits as $visit) {
            $visitDate = new DateTime($visit->LastActivity, new DateTimeZone('UTC'));
            if ($visitDate < $cutoff) {
                continue;
            }

            $visitPerson = new Person(
                Id: (int)$visit->PersonId,
                Email: $visit->Email ?? null,
                UseGravatar: $visit->UseGravatar ?? null,
                Avatar: $visit->Avatar ?? null,
            );

            $result[] = [
                'personId'      => $visit->PersonId,
                'displayName'   => $visit->NickName ?? $visit->FullName,
                'timeAgo'       => $visit->TimeAgo,
                'minutesAgo'    => $visit->MinutesAgo,
                'formattedDate' => $visit->FormattedDate,
                'useGravatar'   => $visit->UseGravatar,
                'userImg'       => WebApp::getUserImg($visitPerson, $this->gravatarHandler),
                'os'            => $visit->Os,
                'browser'       => $visit->Browser,
            ];
        }
        $hasNew = $this->messageDataHelper->hasNewMessages($this->connectedUser->person->Id ?? 0, $previousLogId);
        $this->renderJsonOk([
            'users'          => $result,
            'hasNewMessages' => $hasNew,
        ]);
    }
}
