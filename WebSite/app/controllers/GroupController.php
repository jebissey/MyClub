<?php

namespace app\controllers;

use app\helpers\Application;
use PDO;

class GroupController extends BaseController implements CrudControllerInterface
{
    public function index()
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $having = '';
            if ($this->authorizations->isPersonManager() && !$this->authorizations->isWebmaster()) {
                $having = "HAVING Authorizations IS NULL";
            }
            $query = $this->pdo->query("
                SELECT 
                    g.Id, 
	                g.Name, 
                    g.SelfRegistration,
                    GROUP_CONCAT(a.Name) AS Authorizations
                FROM 'Group' g
                LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
                LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
                WHERE Inactivated = 0
                GROUP BY g.Id, g.Name
                $having
                ORDER BY g.Name
            ");
            $groups = $query->fetchAll();
            $this->render('app/views/groups/index.latte', $this->params->getAll([
                'groups' => $groups,
                'layout' => $this->getLayout()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function create()
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {

            $availableAuthorizations = $this->fluent->from('Authorization')->fetchAll();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = isset($_POST['name']) ? $this->sanitizeInput($_POST['name']) : '';
                $selfRegistration = isset($_POST['selfRegistration']) ? 1 : 0;
                $selectedAuthorizations = isset($_POST['authorizations']) ? $_POST['authorizations'] : [];

                if (empty($name)) {
                    $this->render('app/views/groups/create.latte', $this->params->getAll([
                        'availableAuthorizations' => $availableAuthorizations,
                        'error' => 'Le nom du groupe est requis',
                        'layout' => $this->getLayout()
                    ]));
                }

                $this->pdo->beginTransaction();
                try {
                    $query = $this->pdo->prepare('INSERT INTO "Group" (Name, SelfRegistration) VALUES (?, ?)');
                    $query->execute([$name, $selfRegistration]);
                    $groupId = $this->pdo->lastInsertId();

                    $query = $this->pdo->prepare('INSERT INTO "GroupAuthorization" (IdGroup, IdAuthorization) VALUES (?, ?)');
                    foreach ($selectedAuthorizations as $authId) {
                        $query->execute([$groupId, $authId]);
                    }
                    $this->pdo->commit();
                    $this->flight->redirect('/groups');
                } catch (\Exception $e) {
                    $this->pdo->rollBack();
                    throw $e;
                }
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/groups/create.latte', $this->params->getAll([
                    'availableAuthorizations' => $availableAuthorizations,
                    'layout' => $this->getLayout()
                ]));
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

            $availableAuthorizations = $this->fluent->from('Authorization')->where('Id <> 1')->fetchAll();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = isset($_POST['name']) ? $this->sanitizeInput($_POST['name']) : '';
                $selfRegistration = isset($_POST['selfRegistration']) ? 1 : 0;
                $selectedAuthorizations = isset($_POST['authorizations']) ? $_POST['authorizations'] : [];

                if (empty($name)) {
                    $group = $this->getGroup($id);

                    $this->render('app/views/groups/edit.latte', $this->params->getAll([
                        'group' => $group,
                        'availableAuthorizations' => $availableAuthorizations,
                        'error' => 'Le nom du groupe est requis',
                        'layout' => $this->getLayout()
                    ]));
                } else {
                    $this->pdo->beginTransaction();
                    try {
                        $query = $this->pdo->prepare('UPDATE "Group" SET Name = ?, SelfRegistration = ? WHERE Id = ?');
                        $query->execute([$name, $selfRegistration, $id]);

                        $query = $this->pdo->prepare('DELETE FROM "GroupAuthorization" WHERE IdGroup = ?');
                        $query->execute([$id]);

                        $query = $this->pdo->prepare('INSERT INTO "GroupAuthorization" (IdGroup, IdAuthorization) VALUES (?, ?)');
                        foreach ($selectedAuthorizations as $authId) {
                            $query->execute([$id, $authId]);
                        }
                        $this->pdo->commit();
                        $this->flight->redirect('/groups');
                    } catch (\Exception $e) {
                        $this->pdo->rollBack();
                        throw $e;
                    }
                }
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $group = $this->getGroup($id);
                if (!$group) {
                    $this->application->error499('Group', $id, __FILE__, __LINE__);
                } else {

                    $query = $this->pdo->prepare('SELECT IdAuthorization FROM "GroupAuthorization" WHERE IdGroup = ?');
                    $query->execute([$id]);
                    $currentAuthorizations = $query->fetchAll(PDO::FETCH_COLUMN);

                    $this->render('app/views/groups/edit.latte', $this->params->getAll([
                        'group' => $group,
                        'availableAuthorizations' => $availableAuthorizations,
                        'currentAuthorizations' => $currentAuthorizations,
                        'layout' => $this->getLayout()
                    ]));
                }
            } else {
                (new Application($this->pdo, $this->flight))->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function delete($id)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {

            $query = $this->pdo->prepare('UPDATE "Group" SET Inactivated = 1 WHERE Id = ?');
            $query->execute([$id]);

            $this->flight->redirect('/groups');
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
