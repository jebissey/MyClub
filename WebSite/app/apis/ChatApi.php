<?php

declare(strict_types=1);

namespace app\apis;

use DateTime;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\LogDataHelper;
use app\models\PersonDataHelper;

class ChatApi extends AbstractApi
{
    private const ACTIVE_WINDOW_MINUTES = 15;

    public function __construct(
        Application      $application,
        ConnectedUser    $connectedUser,
        DataHelper       $dataHelper,
        PersonDataHelper $personDataHelper,
        private LogDataHelper  $logDataHelper,
        private GravatarHandler  $gravatarHandler,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function getActiveUsers(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $visits = $this->logDataHelper->getLastVisitPerActivePersonWithTimeAgo($this->dataHelper->gets('Person', ['Inactivated' => 0]));
        $cutoff = new DateTime('-' . self::ACTIVE_WINDOW_MINUTES . ' minutes');
        $result = [];
        foreach ($visits as $visit) {
            if (new DateTime($visit->LastActivity) < $cutoff) continue;
            $result[] = [
                'personId'      => $visit->PersonId,
                'displayName'   => $visit->NickName ?? $visit->FullName,
                'timeAgo'       => $visit->TimeAgo,
                'formattedDate' => $visit->FormattedDate,
                'useGravatar'   => $visit->UseGravatar,
                'userImg'       => WebApp::getUserImg($visit, $this->gravatarHandler),
                'os'            => $visit->Os,
                'browser'       => $visit->Browser,
            ];
        }
        $this->renderJsonOk($result);
    }
}
