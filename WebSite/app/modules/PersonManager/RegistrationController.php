<?php
declare(strict_types=1);

namespace app\modules\PersonManager;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\GenericDataHelper;
use app\models\GroupDataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;

class RegistrationController extends TableController
{
    public function __construct(
        Application $application,
        private TableControllerDataHelper $tableControllerDataHelper,
        private GroupDataHelper $groupDataHelper,
        GenericDataHelper $genericDataHelper,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $genericDataHelper, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function index()
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager())) {
            $schema = [
                'lastName' => FilterInputRule::PersonName->value,
                'firstName' => FilterInputRule::PersonName->value,
                'nickName' => FilterInputRule::PersonName->value,
            ];
            $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
            $filterConfig = [
                ['name' => 'lastName', 'label' => 'Nom'],
                ['name' => 'firstName', 'label' => 'Prénom'],
                ['name' => 'nickName', 'label' => 'Surnom']
            ];
            $columns = [
                ['field' => 'LastName', 'label' => 'Nom'],
                ['field' => 'FirstName', 'label' => 'Prénom'],
                ['field' => 'NickName', 'label' => 'Surnom']
            ];
            $data = $this->prepareTableData($this->tableControllerDataHelper->getPersonsQuery(), $filterValues, (int)($this->flight->request()->query['tablePage'] ?? 1));
            $this->render('PersonManager/views/registration_groups_index.latte', Params::getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/registration',
                'layout' => $this->getLayout(),
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
            ]));
        }
    }

    public function getPersonGroups($personId)
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager())) {
            [$availableGroups, $currentGroups] = $this->groupDataHelper->getAvailableGroups($this->application->getConnectedUser(), $personId);

            $this->render('PersonManager/views/registration_user_groups_partial.latte', Params::getAll([
                'currentGroups' => $currentGroups,
                'availableGroups' => $availableGroups,
                'personId' => $personId
            ]));
        }
    }
}
