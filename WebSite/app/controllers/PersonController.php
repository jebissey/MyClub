<?php

namespace app\controllers;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\interfaces\CrudControllerInterface;
use app\models\GroupDataHelper;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;


class PersonController extends TableController implements CrudControllerInterface
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function help(): void
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $this->render('app/views/info.latte', [
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_personManager'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->connectedUser->hasAutorization(),
                'currentVersion' => Application::VERSION
            ]);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function home(): void
    {
        if ($this->connectedUser->get()->isAdministrator() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'personManager';

                $this->render('app/views/admin/personManager.latte', Params::getAll([]));
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

            $this->render('app/views/persons/index.latte', Params::getAll([
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
                $this->flight->redirect('/persons/edit/' . (new PersonDataHelper($this->application))->create());
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
                    $this->flight->redirect('/persons');
                } else if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                    $this->render('app/views/user/account.latte', Params::getAll([
                        'readOnly' => $person->Imported == 1 ? true : false,
                        'email' => $person->Email,
                        'firstName' => $person->FirstName,
                        'lastName' => $person->LastName,
                        'isSelfEdit' => false,
                        'layout' => WebApp::getLayout()
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
                $this->flight->redirect('/persons');
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function editPresentation()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $this->render('app/views/user/editPresentation.latte', Params::getAll([
                'person' => $person,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function savePresentation()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'content' => FilterInputRule::Html->value,
                    'location' => FilterInputRule::Int->value,
                    'inPresentationDirectory' => FilterInputRule::Int->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $presentation = $input['content'] ?? '???';
                $location =  $input['location'] ?? '???';
                $inDirectory = $input['inPresentationDirectory'] ?? 0;

                $this->dataHelper->set('Person', [
                    'Presentation' => $presentation,
                    'PresentationLastUpdate' => date('Y-m-d H:i:s'),
                    'Location' => $location,
                    'InPresentationDirectory' => $inDirectory,
                ], ['Id' => $person->Id]);
                $this->flight->redirect('/directory');
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showPresentation($personId)
    {
        if ($loggedPerson = $this->connectedUser->get()->person ?? false) {
            $person = $this->dataHelper->get('Person', [
                'Id' => $personId,
                'Inactivated' => 0,
                'InPresentationDirectory' => 1
            ]);
            if (!$person) {
                $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown person id $personId in file " . __FILE__ . ' at line ' . __LINE__);
                return;
            }

            $this->render('app/views/user/presentation.latte', Params::getAll([
                'person' => $person,
                'loggedPerson' => $loggedPerson,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showDirectory()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $groupParam = $this->flight->request()->query['group'] ?? null;
            $selectedGroup = ($groupParam !== null && ctype_digit((string)$groupParam)) ? (int)$groupParam : null;
            if ($selectedGroup) $persons = (new PersonDataHelper($this->application))->getPersonsInGroupForDirectory($selectedGroup);
            else {
                $persons = $this->dataHelper->gets('Person', [
                    'InPresentationDirectory' => 1,
                    'Inactivated' => 0
                ], 'Id, LastName, FirstName, NickName, UseGravatar, Avatar, Email');
            }
            $groupCounts = (new GroupDataHelper($this->application))->getGroupCount();
            $this->render('app/views/user/directory.latte', Params::getAll([
                'persons' => $persons,
                'navItems' => $this->getNavItems($person),
                'loggedPerson' => $person,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'groupCounts' => $groupCounts,
                'selectedGroup' => $selectedGroup,
            ]));
        } elseif ($person == '') $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Il faut être connecté pour pouvoir consulter le trombinoscope', 5000);
        else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showMap()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $members = $this->dataHelper->gets('Person', [
                'InPresentationDirectory' => 1,
                'Location IS NOT NULL' => null,
                'Inactivated' => 0
            ]);
            $locationData = [];
            foreach ($members as $member) {
                if (!empty($member->Location) && preg_match('/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/', $member->Location)) {
                    list($lat, $lng) = explode(',', $member->Location);
                    $locationData[] = [
                        'id' => $member->Id,
                        'name' => $member->FirstName . ' ' . $member->LastName,
                        'nickname' => $member->NickName,
                        'avatar' => $member->Avatar,
                        'useGravatar' => $member->UseGravatar,
                        'email' => $member->Email,
                        'lat' => trim($lat),
                        'lng' => trim($lng)
                    ];
                }
            }

            $this->render('app/views/user/map.latte', Params::getAll([
                'locationData' => $locationData,
                'membersCount' => count($locationData),
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
