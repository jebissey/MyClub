<?php
declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\GroupDataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;

class UserGroupsController extends AbstractController
{
    public function __construct(
        Application $application,
        private PersonGroupDataHelper $personGroupDataHelper,
        private GroupDataHelper $groupDataHelper,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function groups(): void
    {
        $person = $this->application->getConnectedUser()->get()->person;
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
        $person = $this->application->getConnectedUser()->get(1)->person;
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
