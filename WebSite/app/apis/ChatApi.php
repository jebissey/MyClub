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
use app\models\LanguagesDataHelper;
use app\models\LogDataHelper;
use app\models\MemberDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\valueObjects\Person;

final class ChatApi extends AbstractApi
{
    public function __construct(
        Application $application,
        protected ConnectedUser $connectedUser,
        LanguagesDataHelper $languagesDataHelper,
        PersonDataHelper $personDataHelper,
        private readonly LogDataHelper $logDataHelper,
        private readonly GravatarHandler $gravatarHandler,
        private readonly MessageDataHelper $messageDataHelper,
        private readonly MemberDataHelper $memberDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function getActiveUsers(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $previousLogId = To::int($_SESSION['last_log_id'] ?? null);
        $activePersons = $this->memberDataHelper->getActiveMembersBasicInfo();
        $visits = $this->logDataHelper->getLastVisitPerActivePersonWithTimeAgo($activePersons);

        $result = [];
        foreach ($visits as $visit) {
            $visitPerson = new Person(
                id: To::int($visit->PersonId),
                email: To::str($visit->Email ?? ''),
                useGravatar: To::bool($visit->UseGravatar ?? null),
                avatar: To::nullableStr($visit->Avatar ?? null),
            );

            $result[] = [
                'personId'      => $visit->PersonId,
                'displayName'   => $visit->NickName ?? $visit->FullName,
                'timeAgo'       => $visit->TimeAgo,
                'minutesAgo'    => $visit->MinutesAgo,
                'formattedDate' => $visit->FormattedDate,
                'useGravatar'   => $visit->UseGravatar,
                'userImg'       => WebApp::getUserImg(
                    $visitPerson->useGravatar,
                    $visitPerson->email,
                    $visitPerson->avatar,
                    $this->gravatarHandler
                ),
                'os'            => $visit->Os,
                'browser'       => $visit->Browser,
            ];
        }
        $hasNew = $this->messageDataHelper->hasNewMessages($this->connectedUser->person->id ?? 0, $previousLogId);
        $this->renderJsonOk([
            'users'          => $result,
            'hasNewMessages' => $hasNew,
        ]);
    }
}
