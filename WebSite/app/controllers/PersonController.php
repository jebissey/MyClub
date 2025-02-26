<?php

namespace app\controllers;

use PDO;

class PersonController extends TableController implements CrudControllerInterface
{
    public function help(): void
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            echo $this->latte->render('app/views/info.latte', [
                'content' => $this->settings->get('Help_personManager'),
                'hasAuthorization' => $this->authorizations->hasAutorization()
            ]);
        }
    }

    public function home(): void
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'personManager';
                echo $this->latte->render('app/views/admin/personManager.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
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
                ['field' => 'Email', 'label' => 'Email'],
                ['field' => 'LastName', 'label' => 'Nom'],
                ['field' => 'FirstName', 'label' => 'Prénom']
            ];
            $query = $this->fluent->from('Person')
                ->select('Id, FirstName, LastName, Email')
                ->orderBy('LastName')
                ->where('Inactivated', 0);
            $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
            echo $this->latte->render('app/views/persons/index.latte', $this->params->getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/persons' 
            ]));
        }
    }

    public function create()
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $query = $this->pdo->prepare("SELECT Id FROM Person WHERE Email = ''");
                $query->execute();
                $id = $query->fetch(PDO::FETCH_ASSOC)['Id'] ?? null;
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
        }
    }

    public function edit($id)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $query = $this->pdo->prepare('SELECT * FROM "Person" WHERE Id = ?');
            $query->execute([$id]);
            $person = $query->fetch(PDO::FETCH_ASSOC);
            if (!$person) {
                $this->application->error499('Person', $id, __FILE__, __LINE__);
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                    $firstName = $_POST['firstName'];
                    $lastName = $_POST['lastName'];
                    $query = $this->pdo->prepare('UPDATE Person SET FirstName = ?, LastName = ? WHERE Id = ' . $person['Id']);
                    $query->execute([$firstName, $lastName]);

                    if ($person['Imported'] == 0) {
                        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
                        $query = $this->pdo->prepare('UPDATE Person SET Email = ? WHERE Id = ' . $person['Id']);
                        $query->execute([$email]);
                    }
                    $this->flight->redirect('/persons');
                } else if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                    echo $this->latte->render('app/views/user/account.latte', $this->params->getAll([
                        'readOnly' => $person['Imported'] == 1 ? true : false,
                        'email' => $person['Email'],
                        'firstName' => $person['FirstName'],
                        'lastName' => $person['LastName'],
                        'isSelfEdit' => false,
                        'layout' => $this->getLayout('account')
                    ]));
                } else {
                    $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
                }
            }
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
}
