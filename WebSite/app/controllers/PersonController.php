<?php

namespace app\controllers;

use PDO;

class PersonController extends TableController implements CrudControllerInterface
{
    public function help(): void
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $this->render('app/views/info.latte', [
                'content' => $this->settings->get('Help_personManager'),
                'hasAuthorization' => $this->authorizations->hasAutorization(),
                'currentVersion' => self::VERSION
            ]);
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function home(): void
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'personManager';
                $this->render('app/views/admin/personManager.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function index()
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
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
            $query = $this->fluent->from('Person')
                ->select('Id, FirstName, LastName, Email')
                ->orderBy('LastName')
                ->where('Inactivated = 0');
            $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
            $this->render('app/views/persons/index.latte', $this->params->getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/persons'
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function create()
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $query = $this->pdo->prepare("SELECT Id FROM Person WHERE Email = ''");
                $query->execute();
                $id = $query->fetch()->Id ?? null;
                if ($id == null) {
                    $query = $this->pdo->prepare("
                        INSERT INTO Person (Email, FirstName, LastName, Imported) 
                        VALUES ('', '', '', 0)
                    ");
                    $query->execute([]);
                    $id = $this->pdo->lastInsertId();
                }
                $this->flight->redirect('/persons/edit/' . $id);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function edit($id)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $query = $this->pdo->prepare('SELECT * FROM "Person" WHERE Id = ?');
            $query->execute([$id]);
            $person = $query->fetch();
            if (!$person) {
                $this->application->error499('Person', $id, __FILE__, __LINE__);
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                    $firstName = $_POST['firstName'];
                    $lastName = $_POST['lastName'];
                    $query = $this->pdo->prepare('UPDATE Person SET FirstName = ?, LastName = ? WHERE Id = ' . $person->Id);
                    $query->execute([$firstName, $lastName]);

                    if ($person->Imported == 0) {
                        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
                        $query = $this->pdo->prepare('UPDATE Person SET Email = ? WHERE Id = ' . $person->Id);
                        $query->execute([$email]);
                    }
                    $this->flight->redirect('/persons');
                } else if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                    $this->render('app/views/user/account.latte', $this->params->getAll([
                        'readOnly' => $person->Imported == 1 ? true : false,
                        'email' => $person->Email,
                        'firstName' => $person->FirstName,
                        'lastName' => $person->LastName,
                        'isSelfEdit' => false,
                        'layout' => $this->getLayout('account')
                    ]));
                } else {
                    $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
                }
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function delete($id)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $query = $this->pdo->prepare('UPDATE "Person" SET Inactivated = 1 WHERE Id = ?');
                $query->execute([$id]);

                $this->flight->redirect('/persons');
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function getWebmasterEmail()
    {
        $query = $this->pdo->query(
            '
            SELECT Email FROM Person
            INNER JOIN PersonGroup on Person.Id = PersonGroup.IdPerson
            INNER JOIN "Group" on "Group".Id = PersonGroup.IdGroup
            INNER JOIN GroupAuthorization on "Group".Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id
            WHERE Authorization.Name = "Webmaster";'
        );
        return $query->fetchColumn();
    }



    public function editPresentation()
    {
        if ($person = $this->getPerson([])) {
            $this->render('app/views/user/editPresentation.latte', $this->params->getAll([
                'person' => $person,
                'navItems' => $this->getNavItems(),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function savePresentation()
    {
        if ($person = $this->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $presentation = $_POST['content'] ?? '';
                $location =  $_POST['location'] ?? '';
                $inDirectory = isset($_POST['inPresentationDirectory']) ? 1 : 0;

                $success = $this->fluent->update('Person')
                    ->set([
                        'Presentation' => $presentation,
                        'Location' => $location,
                        'InPresentationDirectory' => $inDirectory,
                    ])
                    ->where('Id', $person->Id)
                    ->execute();
                if ($success) {
                    $this->flight->redirect('/directory');
                } else {
                    die("Save presentation error");
                }
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showPresentation($personId)
    {
        if ($loggedPerson = $this->getPerson([])) {
            $person = $this->fluent->from('Person')
                ->where('Id', $personId)
                ->where('Inactivated', 0)
                ->where('InPresentationDirectory', 1)
                ->fetch();

            if (!$person) {
                $this->application->error404(__FILE__, __LINE__);
                return;
            }

            $this->render('app/views/user/presentation.latte', $this->params->getAll([
                'person' => $person,
                'loggedPerson' => $loggedPerson,
                'navItems' => $this->getNavItems(),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showDirectory()
    {
        if ($person = $this->getPerson([])) {

            $selectedGroup = isset($_GET['group']) ? (int)$_GET['group'] : null;
            if ($selectedGroup) {
                $stmt = $this->pdo->prepare("
                    SELECT DISTINCT p.* 
                    FROM Person p
                    JOIN PersonGroup pg ON p.Id = pg.IdPerson
                    WHERE pg.IdGroup = ? AND p.InPresentationDirectory = 1 AND p.Inactivated = 0
                    ORDER BY p.LastName, p.FirstName
                ");
                $stmt->execute([$selectedGroup]);
                $persons = $stmt->fetchAll();
            } else {
                $persons = $this->pdo->query("
                    SELECT * FROM Person 
                    WHERE InPresentationDirectory = 1 AND Inactivated = 0
                    ORDER BY LastName, FirstName
                ")->fetchAll();
            }

            $groupCounts = [];
            $groupCountResult = $this->pdo->query("
                SELECT g.Id, COUNT(DISTINCT pg.IdPerson) as Count 
                FROM `Group` g
                JOIN PersonGroup pg ON g.Id = pg.IdGroup
                JOIN Person p ON pg.IdPerson = p.Id
                WHERE p.InPresentationDirectory = 'yes'
                GROUP BY g.Id
                ")->fetchAll();
            foreach ($groupCountResult as $count) {
                $groupCounts[$count->Id] = $count['Count'];
            }

            $this->render('app/views/user/directory.latte', $this->params->getAll([
                'persons' => $persons,
                'navItems' => $this->getNavItems(),
                'loggedPerson' => $person,
                'groups' => $this->getGroups(),
                'groupCounts' => $groupCounts,
                'selectedGroup' => $selectedGroup,
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showMap()
    {
        if ($this->getPerson([])) {
            $members = $this->fluent->from('Person')
                ->where('InPresentationDirectory', 1)
                ->where('Location IS NOT NULL')
                ->where('Inactivated', 0)
                ->fetchAll();

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
                'navItems' => $this->getNavItems(),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
