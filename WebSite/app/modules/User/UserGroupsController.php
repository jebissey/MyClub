<?php

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;

class UserGroupsController extends AbstractController
{
    public function __construct(Application $application, private PersonGroupDataHelper $personGroupDataHelper, private GroupDataHelper $groupDataHelper)
    {
        parent::__construct($application);
    }

    public function groups(): void
    {
        $person = $this->connectedUser->get()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $currentGroups = $this->groupDataHelper->getCurrentGroups($person->Id);

        $this->render('User/views/user_groups.latte', Params::getAll([
            'groups' => $currentGroups,
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
        ]));
    }

    public function groupsSave(): void
    {
        $person = $this->connectedUser->get(1)->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $groups = WebApp::getFiltered('groups', FilterInputRule::ArrayInt->value, $this->flight->request()->data->getData());
        $this->personGroupDataHelper->update($person->Id, $groups ?? []);
        $this->redirect('/user');
    }
}
