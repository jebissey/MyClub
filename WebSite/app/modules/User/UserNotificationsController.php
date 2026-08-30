<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\NotificationSender;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\CredentialService;
use app\modules\User\viewModels\UserNotificationsViewModel;

class UserNotificationsController extends AbstractController
{
    public function __construct(
        Application $application,
        private GroupDataHelper $groupDataHelper,
        private NotificationSender $notificationSender,
        private CredentialService $credentials
    ) {
        parent::__construct($application);
    }

    public function notifications(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $notification = '';
        if (isset($_GET['test'])) {
            $this->notificationSender->sendToRecipients(
                [$person->Id],
                [
                    'title' => 'Notification de test',
                    'body'  => 'Ceci est une notification push de test.',
                    'url'   => '/',
                ]
            );
            $notification = "🚀 Notification de test envoyée à l'utilisateur #{$person->Id}.";
        }

        $row = $this->dataHelper->get('Person', ['Id' => $person->Id], 'Notifications');
        /** @var object{Notifications: string|null}|false $row */
        $notificationsJson = $row !== false ? ($row->Notifications ?? '{}') : '{}';

        /** @var array<string, mixed>|null $decodedNotifications */
        $decodedNotifications = json_decode($notificationsJson, true);

        $groupsWithType = $this->groupDataHelper->getGroupsWithType($person->Id);

        $viewModel = new UserNotificationsViewModel(
            currentNotifications: is_array($decodedNotifications) ? $decodedNotifications : [],
            groups: $groupsWithType !== false ? array_values($groupsWithType) : [],
            vapidPubliKey: $this->credentials->get('vapid', 'publicKey') ?? '',
            notification: $notification,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/user_notifications.latte', $viewModel->toArray());
    }

    public function notificationsSave(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
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
        $this->dataHelper->set('Person', ['notifications' => json_encode($notifications)], ['Id' => $person->Id]);
        $this->redirect('/user');
    }
}
