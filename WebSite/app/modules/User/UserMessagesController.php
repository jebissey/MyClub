<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\models\MessageDataHelper;
use app\modules\Common\AbstractShowController;

class UserMessagesController extends AbstractShowController
{
    public function __construct(
        Application $application,
        private MessageDataHelper $messageDataHelper,
    ) {
        parent::__construct($application);
    }

    public function showMessages(): void
    {
        $connectedUser = $this->requireConnectedPerson();
        if ($connectedUser === false) return;

        [$searchMode, $searchFrom] = $this->resolveSearchPeriod($connectedUser);

        $this->render('User/views/messages.latte', $this->baseParams($connectedUser, '/user', $searchMode, $searchFrom) + [
            'messages' => $this->messageDataHelper->getGroupedMessages(
                $connectedUser->person->Id,
                $searchFrom,
                new GravatarHandler()
            ),
        ]);
    }
}
