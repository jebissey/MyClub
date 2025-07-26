<?php

namespace app\controllers;

use app\helpers\GroupDataHelper;
use app\helpers\TableControllerHelper;
use app\helpers\Webapp;

class RegistrationController extends TableController
{
    public function index()
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
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
            $data = $this->prepareTableData((new TableControllerHelper())->getPersonsQuery(), $filterValues, $_GET['tablePage'] ?? null);
            $this->render('app/views/registration/index.latte', $this->params->getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/registration',
                'layout' => Webapp::getLayout()()
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function getPersonGroups($personId)
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            [$availableGroups, $currentGroups] = (new GroupDataHelper())->getAvailableGroups($personId);

            $this->render('app/views/registration/groups.latte', $this->params->getAll([
                'currentGroups' => $currentGroups,
                'availableGroups' => $availableGroups,
                'personId' => $personId
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
