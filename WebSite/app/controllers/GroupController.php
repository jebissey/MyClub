<?php

namespace app\controllers;

use app\helpers\Application;
use PDO;

class GroupController extends BaseController implements CrudControllerInterface 
{

    public function index()
    {
        $query = $this->pdo->query('SELECT * FROM "Group" WHERE Inactivated = 0');
        $groups = $query->fetchAll(PDO::FETCH_ASSOC);

        echo $this->latte->render('app/views/groups/index.latte', [
            'groups' => $groups
        ]);
    }

    public function create()
    {
        $stmtAuthorizations = $this->pdo->query('SELECT * FROM "Authorization"');
        $availableAuthorizations = $stmtAuthorizations->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? $this->sanitizeInput($_POST['name']) : '';
            $selfRegistration = isset($_POST['selfRegistration']) ? 1 : 0;
            $selectedAuthorizations = isset($_POST['authorizations']) ? $_POST['authorizations'] : [];

            if (empty($name)) {
                echo $this->latte->render('app/views/groups/create.latte', [
                    'availableAuthorizations' => $availableAuthorizations,
                    'error' => 'Le nom du groupe est requis'
                ]);
                return;
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
            return;
        }

        echo $this->latte->render('app/views/groups/create.latte', [
            'availableAuthorizations' => $availableAuthorizations
        ]);
    }

    public function edit($id)
    {
        $stmtAuthorizations = $this->pdo->query('SELECT * FROM "Authorization"');
        $availableAuthorizations = $stmtAuthorizations->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? $this->sanitizeInput($_POST['name']) : '';
            $selfRegistration = isset($_POST['selfRegistration']) ? 1 : 0;
            $selectedAuthorizations = isset($_POST['authorizations']) ? $_POST['authorizations'] : [];

            if (empty($name)) {
                $query = $this->pdo->prepare('SELECT * FROM "Group" WHERE Id = ?');
                $query->execute([$id]);
                $group = $query->fetch(PDO::FETCH_ASSOC);

                echo $this->latte->render('app/views/groups/edit.latte', [
                    'group' => $group,
                    'availableAuthorizations' => $availableAuthorizations,
                    'error' => 'Le nom du groupe est requis'
                ]);
                return;
            }

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
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $query = $this->pdo->prepare('SELECT * FROM "Group" WHERE Id = ?');
            $query->execute([$id]);
            $group = $query->fetch(PDO::FETCH_ASSOC);

            if (!$group) {
                $this->flight->redirect('/groups');
                return;
            }

            $stmtGroupAuth = $this->pdo->prepare('SELECT IdAuthorization FROM "GroupAuthorization" WHERE IdGroup = ?');
            $stmtGroupAuth->execute([$id]);
            $currentAuthorizations = $stmtGroupAuth->fetchAll(PDO::FETCH_COLUMN);

            echo $this->latte->render('app/views/groups/edit.latte', [
                'group' => $group,
                'availableAuthorizations' => $availableAuthorizations,
                'currentAuthorizations' => $currentAuthorizations
            ]);
        } else {
            (new Application($this->pdo, $this->flight))->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare('UPDATE "Group" SET Inactivated = 1 WHERE Id = ?');
        $stmt->execute([$id]);

        $this->flight->redirect('/groups');
    }
}
