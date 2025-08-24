<?php

namespace app\modules\PersonManager;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\interfaces\CrudControllerInterface;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;


class PersonController extends TableController implements CrudControllerInterface
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function help(): void
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $this->render('Common/views/info.latte', [
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_personManager'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->connectedUser->hasAutorization(),
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true
            ]);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function home(): void
    {
        if ($this->connectedUser->get()->isAdministrator() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'personManager';

                $this->render('Webmaster/views/personManager.latte', Params::getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function index()
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
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
            $data = $this->prepareTableData((new TableControllerDataHelper($this->application))->getPersonsQuery(), $filterValues, (int)($this->flight->request()->query['tablePage'] ?? 1));

            $this->render('PersonManager/views/users_index.latte', Params::getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/persons'
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->redirect('/persons/edit/' . (new PersonDataHelper($this->application))->create());
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function edit($id)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $person = $this->dataHelper->get('Person', ['Id' => $id], 'Id, Imported, Email, FirstName, LastName');
            if (!$person) $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown person id: $id in file " . __FILE__ . ' at line ' . __LINE__);
            else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $schema = [
                        'email' => FilterInputRule::Email->value,
                        'firstName' => FilterInputRule::PersonName->value,
                        'lastName' => FilterInputRule::PersonName->value,
                    ];
                    $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                    $this->dataHelper->set('Person', ['FirstName' => $input['firstName'] ?? '???', 'LastName' => $input['lastName']] ?? '???', ['Id' => $person->Id]);
                    if ($person->Imported == 0) $this->dataHelper->set('Person', ['Email' => $input['email']], ['Id' => $person->Id]);
                    $this->redirect('/persons');
                } else if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                    $this->render('User/views/user_account.latte', Params::getAll([
                        'readOnly' => $person->Imported == 1 ? true : false,
                        'email' => $person->Email,
                        'firstName' => $person->FirstName,
                        'lastName' => $person->LastName,
                        'isSelfEdit' => false,
                        'layout' => $this->getLayout()
                    ]));
                } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
            }
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function delete($id)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            if (($_SERVER['REQUEST_METHOD'] === 'DELETE')) {
                $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $id]);
                $this->redirect('/persons');
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
