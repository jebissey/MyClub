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
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;


class PersonController extends TableController
{
    public function __construct(
        Application $application,
        private TableControllerDataHelper $tableControllerDataHelper,
        private PersonDataHelper $personDataHelper,
        GenericDataHelper $genericDataHelper,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $genericDataHelper, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function help(): void
    {
        if (!($this->application->getConnectedUser()->get()->isPersonManager() ?? false)) {
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
            'previousPage' => true
        ]);
    }

    public function home(): void
    {
        if (!($this->application->getConnectedUser()->get()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = 'personManager';

        $this->render('Webmaster/views/personManager.latte', Params::getAll([]));
    }

    public function index(): void
    {
        if (!($this->application->getConnectedUser()->get()->isPersonManager() ?? false)) {
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
        $data = $this->prepareTableData($this->tableControllerDataHelper->getPersonsQuery(), $filterValues, (int)($this->flight->request()->query['tablePage'] ?? 1));

        $this->render('PersonManager/views/users_index.latte', Params::getAll([
            'persons' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/persons'
        ]));
    }

    public function create(): void
    {
        if (!($this->application->getConnectedUser()->get()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->redirect('/person/edit/' . $this->personDataHelper->create());
    }

    public function edit(int $id): void
    {
        if (!($this->application->getConnectedUser()->get()->isPersonManager() ?? false)) {
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
        $this->render('User/views/user_account.latte', Params::getAll([
            'readOnly' => $person->Imported == 1 ? true : false,
            'email' => $person->Email,
            'firstName' => $person->FirstName,
            'lastName' => $person->LastName,
            'isSelfEdit' => false,
            'layout' => $this->getLayout()
        ]));
    }

    public function editSave(int $id): void
    {
        if (!($this->application->getConnectedUser()->get()->isPersonManager() ?? false)) {
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

    public function delete(int $id): void
    {
        if (!($this->application->getConnectedUser()->get()->isPersonManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] !== 'POST')) {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $id]);
        $this->redirect('/persons');
    }
}
