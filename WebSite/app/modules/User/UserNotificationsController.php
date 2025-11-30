<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\modules\Common\AbstractController;

class UserNotificationsController extends AbstractController
{
    public function __construct(Application $application, private GroupDataHelper $groupDataHelper)
    {
        parent::__construct($application);
    }

    public function notifications(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $this->render('User/views/user_notifications.latte', $this->getAllParams([
            'currentNotifications' => json_decode($person->Notifications ?? '{}', true) ?? [],
            'groups' => $this->groupDataHelper->getGroupsWithType($person->Id),
            'page' => $this->application->getConnectedUser()->getPage(1),
        ]));
    }

    public function notificationsSave(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $notifications = WebApp::getFiltered('notifications', FilterInputRule::CheckboxMatrix->value, $this->flight->request()->data->getData()) ?? [];
        $this->dataHelper->set('Person', ['notifications' => json_encode($notifications)], ['Id' => $person->Id]);
        $this->redirect('/user');
    }
}
