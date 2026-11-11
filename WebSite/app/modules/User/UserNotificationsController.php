<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\NotificationSender;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\CredentialService;
use app\modules\User\viewModels\UserNotificationsViewModel;

final class UserNotificationsController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly GroupDataHelper $groupDataHelper,
        private readonly NotificationSender $notificationSender,
        private readonly CredentialService $credentials
    ) {
        parent::__construct($application);
    }

    public function notifications(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;
        $notification = '';
        if ($this->input->hasQuery('test')) {
            $this->notificationSender->sendToRecipients(
                [$person->id],
                [
                    'title' => $this->translate('user.notifications.test.title'),
                    'body'  => $this->translate('user.notifications.test.body'),
                    'url'   => '/',
                ]
            );
            $notification = str_replace(
                '{id}',
                (string) $person->id,
                $this->translate('user.notifications.test.sent')
            );
        }

        $row = $this->dataHelper->get('Member', ['Id' => $person->id], 'Notifications');
        /** @var object{Notifications: string|null}|false $row */
        $notificationsJson = $row !== false ? ($row->Notifications ?? '{}') : '{}';

        /** @var array<string, mixed>|null $decodedNotifications */
        $decodedNotifications = json_decode($notificationsJson, true);

        $viewModel = new UserNotificationsViewModel(
            navItems: $this->getNavItems($person->email),
            currentNotifications: is_array($decodedNotifications) ? $decodedNotifications : [],
            subscribedGroupIds: $this->checkedIds($decodedNotifications['groupsSubscribed'] ?? null),
            joinedGroupIds: $this->checkedIds($decodedNotifications['groupsJoined'] ?? null),
            groups: $this->groupDataHelper->getGroupsWithType($person->id),
            vapidPubliKey: $this->credentials->get('vapid', 'publicKey') ?? '',
            notification: $notification,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_notifications.latte', $viewModel);
    }

    public function notificationsSave(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $notifications = WebApp::getFiltered(
            'notifications',
            FilterInputRule::CheckboxMatrix->value,
            $this->flight->request()->data->getData()
        );
        if (!is_array($notifications)) {
            $notifications = [];
        }
        unset(
            $notifications['messageOnGroupSubscribed'],
            $notifications['messageOnGroupJoined']
        );
        $person = $this->application->getConnectedUser(true)->person;
        $this->dataHelper->set('Member', ['Notifications' => json_encode($notifications)], ['Id' => $person->id]);
        $this->redirect('/user');
    }


    /**
     * Turns a checkbox matrix ([groupId => 'on', ...]) into a set of checked group ids.
     *
     * @return array<int, true>
     */
    private function checkedIds(mixed $matrix): array
    {
        $ids = [];
        if (is_array($matrix)) {
            foreach (array_keys($matrix) as $id) {
                $ids[To::int($id)] = true;
            }
        }

        return $ids;
    }
}
