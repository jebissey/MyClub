<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\models\MessageDataHelper;
use app\modules\Common\AbstractShowController;
use app\modules\User\viewModels\UserMessagesViewModel;

final class UserMessagesController extends AbstractShowController
{
    /** @var array<string, array{label: string, icon: string, bg: string, url: string}> */
    private const GROUP_CONFIG = [
        'event'   => ['label' => 'user.messages.group.events',   'icon' => 'bi-calendar-event', 'bg' => 'success', 'url' => '/event/chat/'],
        'article' => ['label' => 'user.messages.group.articles', 'icon' => 'bi-file-text',      'bg' => 'primary', 'url' => '/article/chat/'],
        'group'   => ['label' => 'user.messages.group.groups',   'icon' => 'bi-people',         'bg' => 'info',    'url' => '/group/chat/'],
    ];

    public function __construct(
        Application $application,
        private readonly MessageDataHelper $messageDataHelper,
    ) {
        parent::__construct($application);
    }

    public function showMessages(): void
    {
        $connectedUser = $this->requireConnectedPerson();
        if ($connectedUser === false) {
            return;
        }

        [$searchMode, $searchFrom] = $this->resolveSearchPeriod(
            $connectedUser,
            cookieName: 'period_user_messages',
        );

        $viewModel = new UserMessagesViewModel(
            searchFrom: $searchFrom,
            searchMode: $searchMode,
            navItems: $this->getNavItems($connectedUser->person?->email),
            person: $connectedUser->person,
            messages: $this->messageDataHelper->getGroupedMessages(
                $connectedUser->person->id ?? 0,
                $searchFrom,
                new GravatarHandler()
            ),
            groupConfig: self::GROUP_CONFIG,
            btnParent: '/user',
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/messages.latte', $viewModel);
    }
}
