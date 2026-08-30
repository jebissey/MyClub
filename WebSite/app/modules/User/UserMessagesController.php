<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\models\MessageDataHelper;
use app\modules\Common\AbstractShowController;
use app\modules\User\viewModels\UserMessagesViewModel;

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
        if ($connectedUser === false) {
            return;
        }

        [$searchMode, $searchFrom] = $this->resolveSearchPeriod($connectedUser);

        $viewModel = new UserMessagesViewModel(
            searchFrom: $searchFrom,
            searchMode: $searchMode,
            navItems: $this->getNavItems($connectedUser->person),
            person: $connectedUser->person,
            messages: $this->messageDataHelper->getGroupedMessages(
                $connectedUser->person->Id ?? 0,
                $searchFrom,
                new GravatarHandler()
            ),
            btnParent: '/user',
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/messages.latte', $viewModel->toArray());
    }
}
