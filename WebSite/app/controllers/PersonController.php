<?php

namespace app\controllers;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\GroupDataHelper;
use app\helpers\PersonDataHelper;
use app\helpers\SettingsDataHelper;
use app\helpers\TableControllerHelper;
use app\helpers\Webapp;
use app\interfaces\CrudControllerInterface;


class PersonController extends TableController implements CrudControllerInterface
{
    private GroupDataHelper $groupDataHelper;
    private TableControllerHelper $tableControllerHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->groupDataHelper = new GroupdataHelper($application);
        $this->tableControllerHelper = new TableControllerHelper($application);
    }

    public function help(): void
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isPersonManager() || $this->connectedUser->isWebmaster()) {
            $this->render('app/views/info.latte', [
                'content' => (new SettingsDataHelper($this->application))->get('Help_personManager'),
                'hasAuthorization' => $this->connectedUser->hasAutorization(),
                'currentVersion' => Application::getVersion()
            ]);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function home(): void
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isAdministrator()) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'personManager';

                $this->render('app/views/admin/personManager.latte', $this->params->getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function index()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isPersonManager() || $this->connectedUser->isWebmaster()) {
            $filterValues = [
                'firstName' => $_GET['firstName'] ?? '',
                'lastName' => $_GET['lastName'] ?? '',
                'nickName' => $_GET['nickName'] ?? '',
                'email' => $_GET['email'] ?? ''
            ];
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
            $data = $this->prepareTableData($this->tableControllerHelper->getPersonsQuery(), $filterValues, $_GET['tablePage'] ?? null);

            $this->render('app/views/persons/index.latte', $this->params->getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/persons'
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isPersonManager() || $this->connectedUser->isWebmaster()) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->flight->redirect('/persons/edit/' . (new PersonDataHelper($this->application))->create());
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function edit($id)
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isPersonManager() || $this->connectedUser->isWebmaster()) {
            $person = $this->dataHelper->get('Person', ['Id' => $id]);
            if (!$person) $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown person id: $id in file " . __FILE__ . ' at line ' . __LINE__);
            else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $firstName = $_POST['firstName'];
                    $lastName = $_POST['lastName'];
                    $this->dataHelper->set('Person', ['FirstName' => $firstName, 'LastName' => $lastName], ['Id' => $person->Id]);
                    if ($person->Imported == 0) {
                        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
                        $this->dataHelper->set('Person', ['Email' => $email], ['Id' => $person->Id]);
                    }
                    $this->flight->redirect('/persons');
                } else if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                    $this->render('app/views/user/account.latte', $this->params->getAll([
                        'readOnly' => $person->Imported == 1 ? true : false,
                        'email' => $person->Email,
                        'firstName' => $person->FirstName,
                        'lastName' => $person->LastName,
                        'isSelfEdit' => false,
                        'layout' => Webapp::getLayout()('account')
                    ]));
                } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
            }
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function delete($id)
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isPersonManager() || $this->connectedUser->isWebmaster()) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $id]);
                $this->flight->redirect('/persons');
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function editPresentation()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $this->render('app/views/user/editPresentation.latte', $this->params->getAll([
                'person' => $person,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function savePresentation()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $presentation = $_POST['content'] ?? '';
                $location =  $_POST['location'] ?? '';
                $inDirectory = isset($_POST['inPresentationDirectory']) ? 1 : 0;

                $this->dataHelper->set('Person', [
                    'Presentation' => $presentation,
                    'PresentationLastUpdate' => date('Y-m-d H:i:s'),
                    'Location' => $location,
                    'InPresentationDirectory' => $inDirectory,
                ], ['Id' => $person->Id]);
                $this->flight->redirect('/directory');
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showPresentation($personId)
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($loggedPerson = $this->connectedUser->person) {
            $person = $this->dataHelper->get('Person', [
                'Id' => $personId,
                'Inactivated' => 0,
                'InPresentationDirectory' => 1
            ]);
            if (!$person) {
                $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown person id $personId in file " . __FILE__ . ' at line ' . __LINE__);
                return;
            }

            $this->render('app/views/user/presentation.latte', $this->params->getAll([
                'person' => $person,
                'loggedPerson' => $loggedPerson,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showDirectory()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $selectedGroup = isset($_GET['group']) ? (int)$_GET['group'] : null;
            if ($selectedGroup) $persons = (new PersonDataHelper($this->application))->getPersonsInGroupForDirectory($selectedGroup);
            else $persons = $this->dataHelper->gets('Person', ['InPresentationDirectory' => 1, 'Inactivated' => 0], 'LastName, FirstName');
            $groupCounts = $this->groupDataHelper->getGroupCount();

            $this->render('app/views/user/directory.latte', $this->params->getAll([
                'persons' => $persons,
                'navItems' => $this->getNavItems($person),
                'loggedPerson' => $person,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'groupCounts' => $groupCounts,
                'selectedGroup' => $selectedGroup,
            ]));
        } elseif ($person == '') $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Il faut être connecté pour pouvoir consulter le trombinoscope', 5000);
        else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
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

            $this->render('app/views/user/map.latte', $this->params->getAll([
                'locationData' => $locationData,
                'membersCount' => count($locationData),
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
