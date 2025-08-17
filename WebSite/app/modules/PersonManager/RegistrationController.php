<?php

namespace app\modules\PersonManager;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;

class RegistrationController extends TableController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function index()
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
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
            $data = $this->prepareTableData((new TableControllerDataHelper($this->application))->getPersonsQuery(), $filterValues, (int)($this->flight->request()->query['tablePage'] ?? 1));
            $this->render('app/views/registration/index.latte', Params::getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/registration',
                'layout' => WebApp::getLayout(),
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function getPersonGroups($personId)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            [$availableGroups, $currentGroups] = (new GroupDataHelper($this->application))->getAvailableGroups($this->connectedUser, $personId);

            $this->render('app/views/registration/groups.latte', Params::getAll([
                'currentGroups' => $currentGroups,
                'availableGroups' => $availableGroups,
                'personId' => $personId
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
