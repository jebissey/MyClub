<?php

namespace app\controllers;

use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\GroupDataHelper;
use app\helpers\Params;
use app\helpers\TableControllerHelper;
use app\helpers\Webapp;

class RegistrationController extends TableController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function index()
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $filterValues = [
                'lastName' => $_GET['lastName'] ?? '',
                'firstName' => $_GET['firstName'] ?? '',
                'nickName' => $_GET['nickName'] ?? ''
            ];
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
            $data = $this->prepareTableData((new TableControllerHelper($this->application))->getPersonsQuery(), $filterValues, $_GET['tablePage'] ?? null);
            $this->render('app/views/registration/index.latte', Params::getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/registration',
                'layout' => Webapp::getLayout()()
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function getPersonGroups($personId)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            [$availableGroups, $currentGroups] = (new GroupDataHelper($this->application))->getAvailableGroups($personId);

            $this->render('app/views/registration/groups.latte', Params::getAll([
                'currentGroups' => $currentGroups,
                'availableGroups' => $availableGroups,
                'personId' => $personId
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
