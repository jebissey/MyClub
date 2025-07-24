<?php

namespace app\controllers;

use app\helpers\GroupDataHelper;
use app\helpers\TableControllerHelper;
use app\utils\Webapp;


class PersonController extends TableController implements CrudControllerInterface
{
    private GroupDataHelper $groupDataHelper;
    private TableControllerHelper $tableControllerHelper;

    public function __construct()
    {
        parent::__construct();
        $this->groupDataHelper = new GroupdataHelper();
        $this->tableControllerHelper = new TableControllerHelper();
    }

    public function help(): void
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            $this->render('app/views/info.latte', [
                'content' => $this->application->getSettings()->get('Help_personManager'),
                'hasAuthorization' => $this->application->getAuthorizations()->hasAutorization(),
                'currentVersion' => $this->application->getVersion()
            ]);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function home(): void
    {
        if ($this->personDataHelper->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'personManager';
                $this->render('app/views/admin/personManager.latte', $this->params->getAll([]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function index()
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
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
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function create()
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->flight->redirect('/persons/edit/' . $this->personDataHelper->create());
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function edit($id)
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            $person = $this->dataHelper->get('Person', ['Id' => $id]);
            if (!$person) $this->application->error499('Person', $id, __FILE__, __LINE__);
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
                } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function delete($id)
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $id]);
                $this->flight->redirect('/persons');
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function editPresentation()
    {
        if ($person = $this->personDataHelper->getPerson([])) {
            $this->render('app/views/user/editPresentation.latte', $this->params->getAll([
                'person' => $person,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function savePresentation()
    {
        if ($person = $this->personDataHelper->getPerson([])) {
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
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showPresentation($personId)
    {
        if ($loggedPerson = $this->personDataHelper->getPerson([])) {
            $person = $this->dataHelper->get('Person', [
                'Id' => $personId,
                'Inactivated' => 0,
                'InPresentationDirectory' => 1
            ]);
            if (!$person) {
                $this->application->error404(__FILE__, __LINE__);
                return;
            }

            $this->render('app/views/user/presentation.latte', $this->params->getAll([
                'person' => $person,
                'loggedPerson' => $loggedPerson,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showDirectory()
    {
        if ($person = $this->personDataHelper->getPerson([])) {
            $selectedGroup = isset($_GET['group']) ? (int)$_GET['group'] : null;
            if ($selectedGroup) $persons = $this->personDataHelper->getPersonsInGroupForDirectory($selectedGroup);
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
        } elseif ($person == '') $this->application->message('Il faut être connecté pour pouvoir consulter le trombinoscope', 5000, 403);
        else $this->application->error403(__FILE__, __LINE__);
    }

    public function showMap()
    {
        if ($person = $this->personDataHelper->getPerson([])) {
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
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
