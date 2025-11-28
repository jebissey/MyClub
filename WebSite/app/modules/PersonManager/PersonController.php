<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;


class PersonController extends TableController
{
    public function __construct(
        Application $application,
        private TableControllerDataHelper $tableControllerDataHelper,
        private PersonDataHelper $personDataHelper,
    ) {
        parent::__construct($application);
    }

    public function create(): void
    {
        if (!($this->application->getConnectedUser()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->redirect('/person/edit/' . $this->personDataHelper->create());
    }

    public function delete(int $id): void
    {
        if (!($this->application->getConnectedUser()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] !== 'GET')) {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $id]);
        $this->redirect('/persons');
    }

    public function edit(int $id): void
    {
        if (!($this->application->getConnectedUser()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $person = $this->dataHelper->get('Person', ['Id' => $id], 'Id, Imported, Email, FirstName, LastName');
        if (!$person) {
            $this->raiseBadRequest("Unknown person {$id}", __FILE__, __LINE__);
            return;
        }
        $this->render('User/views/user_account.latte', $this->getAllParams([
            'readOnly' => $person->Imported == 1 ? true : false,
            'email' => $person->Email,
            'firstName' => $person->FirstName,
            'lastName' => $person->LastName,
            'isSelfEdit' => false,
            'layout' => $this->getLayout(),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function editSave(int $id): void
    {
        if (!($this->application->getConnectedUser()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $person = $this->dataHelper->get('Person', ['Id' => $id], 'Id, Imported, Email, FirstName, LastName');
        if (!$person) {
            $this->raiseBadRequest("Unknown person {$id}", __FILE__, __LINE__);
            return;
        }
        $schema = [
            'email' => FilterInputRule::Email->value,
            'firstName' => FilterInputRule::PersonName->value,
            'lastName' => FilterInputRule::PersonName->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $this->dataHelper->set('Person', ['FirstName' => $input['firstName'] ?? '???', 'LastName' => $input['lastName']] ?? '???', ['Id' => $person->Id]);
        if ($person->Imported == 0) $this->dataHelper->set('Person', ['Email' => $input['email']], ['Id' => $person->Id]);
        $this->redirect('/persons');
    }

    public function help(): void
    {
        if (!($this->application->getConnectedUser()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Common/views/info.latte', [
            'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_personManager'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization(),
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true,
            'page' => $this->application->getConnectedUser()->getPage(),
        ]);
    }

    public function home(): void
    {
        if (!($this->application->getConnectedUser()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = 'personManager';

        $this->render('Webmaster/views/personManager.latte', $this->getAllParams([
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function index(): void
    {
        if (!($this->application->getConnectedUser()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'firstName' => FilterInputRule::PersonName->value,
            'lastName' => FilterInputRule::PersonName->value,
            'nickName' => FilterInputRule::PersonName->value,
            'email' => FilterInputRule::Email->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $filterConfig = [
            ['name' => 'firstName', 'label' => 'Prénom'],
            ['name' => 'lastName', 'label' => 'Nom'],
            ['name' => 'nickName', 'label' => 'Surnom'],
            ['name' => 'email', 'label' => 'Email']
        ];
        $columns = [
            ['field' => 'LastName', 'label' => 'Nom'],
            ['field' => 'FirstName', 'label' => 'Prénom'],
            ['field' => 'Email', 'label' => 'Email'],
            ['field' => 'Phone', 'label' => 'Téléphone']
        ];
        $data = $this->prepareTableData($this->tableControllerDataHelper->getPersonsQuery());

        $this->render('PersonManager/views/users_index.latte', $this->getAllParams([
            'persons' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/persons',
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }
}
