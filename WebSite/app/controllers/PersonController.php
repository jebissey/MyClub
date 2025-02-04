<?php

namespace app\controllers;

use PDO;

class PersonController extends BaseController
{

    private function getCurrentUser()
    {
        $userEmail = $_SESSION['userEmail'] ?? '';
        if (empty($userEmail)) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM "Person" WHERE Email = ?');
        $stmt->execute([$userEmail]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getWebmasterEmail()
    {
        $stmt = $this->pdo->query('
            SELECT Email FROM Person
            INNER JOIN PersonGroup on Person.Id = PersonGroup.IdPerson
            INNER JOIN "Group" on "Group".Id = PersonGroup.IdGroup
            INNER JOIN GroupAuthorization on "Group".Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id
            WHERE Authorization.Name = "Webmaster";');
        return $stmt->fetchColumn();
    }

    private function hasPersonManagerAuthorization()
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) return false;

        $stmt = $this->pdo->prepare('
            SELECT 1 FROM "PersonGroup" pg
            JOIN "GroupAuthorization" ga ON pg.IdGroup = ga.IdGroup
            JOIN "Authorization" a ON ga.IdAuthorization = a.Id
            WHERE pg.IdPerson = ? AND a.Name = ?
        ');
        $stmt->execute([$currentUser['Id'], 'PersonManager']);
        return $stmt->fetchColumn() ? true : false;
    }

    public function index()
    {
        // Vérifier l'accès
        $currentUser = $this->getCurrentUser();
        $hasPersonManagerAuth = $this->hasPersonManagerAuthorization();

        if (!$currentUser || !$hasPersonManagerAuth) {
            $this->flight->redirect('/');
            return;
        }

        $stmt = $this->pdo->query('SELECT * FROM "Person" WHERE Inactivated = 0');
        $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo $this->latte->render('views/persons/index.latte', [
            'persons' => $persons
        ]);
    }

    public function edit($id)
    {
        $currentUser = $this->getCurrentUser();
        $hasPersonManagerAuth = $this->hasPersonManagerAuthorization();

        // Vérifier l'accès
        if (!$currentUser) {
            $this->flight->redirect('/');
            return;
        }

        // Récupérer la personne à éditer
        $stmt = $this->pdo->prepare('SELECT * FROM "Person" WHERE Id = ?');
        $stmt->execute([$id]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$person) {
            $this->flight->redirect('/persons');
            return;
        }

        $stmtGroups = $this->pdo->query('SELECT * FROM "Group" WHERE Inactivated = 0');
        $availableGroups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

        $stmtCurrentGroups = $this->pdo->prepare('SELECT IdGroup FROM "PersonGroup" WHERE IdPerson = ?');
        $stmtCurrentGroups->execute([$id]);
        $currentGroups = $stmtCurrentGroups->fetchAll(PDO::FETCH_COLUMN);

        // Déterminer les champs éditables
        $isEditingSelf = $currentUser['Email'] === $person['Email'];
        $editableFields = $this->determineEditableFields($isEditingSelf, $hasPersonManagerAuth, $person['Imported']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Valider et mettre à jour
            $updateData = $this->validateAndPrepareUpdate($person, $editableFields);

            if (!empty($updateData['errors'])) {
                echo $this->latte->render('views/persons/edit.latte', [
                    'person' => $person,
                    'errors' => $updateData['errors'],
                    'availableGroups' => $availableGroups,
                    'currentGroups' => $currentGroups,
                    'editableFields' => $editableFields
                ]);
                return;
            }

            // Début de la transaction
            $this->pdo->beginTransaction();

            try {
                // Mise à jour de la personne
                $updateFields = array_keys($updateData['update']);
                $updateValues = array_values($updateData['update']);
                $updateValues[] = $id;

                $updateQuery = 'UPDATE "Person" SET ' .
                    implode(' = ?, ', $updateFields) . ' = ? ' .
                    'WHERE Id = ?';

                $stmt = $this->pdo->prepare($updateQuery);
                $stmt->execute($updateValues);

                // Mettre à jour les groupes si l'utilisateur a l'autorisation
                if ($hasPersonManagerAuth && isset($_POST['groups'])) {
                    // Supprimer les anciens groupes
                    $stmt = $this->pdo->prepare('DELETE FROM "PersonGroup" WHERE IdPerson = ?');
                    $stmt->execute([$id]);

                    // Ajouter les nouveaux groupes
                    $stmt = $this->pdo->prepare('INSERT INTO "PersonGroup" (IdPerson, IdGroup) VALUES (?, ?)');
                    foreach ($_POST['groups'] as $groupId) {
                        $stmt->execute([$id, $groupId]);
                    }
                }

                $this->pdo->commit();
                $this->flight->redirect('/persons');
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            return;
        }

        echo $this->latte->render('views/persons/edit.latte', [
            'person' => $person,
            'availableGroups' => $availableGroups,
            'currentGroups' => $currentGroups,
            'editableFields' => $editableFields
        ]);
    }

    private function determineEditableFields($isEditingSelf, $hasPersonManagerAuth, $isImported)
    {
        $editableFields = [];

        if ($isEditingSelf) {
            $editableFields = [
                'Password' => true,
                'NickName' => true
            ];
            if (!$isImported) {
                $editableFields['Email'] = true;
                $editableFields['FirstName'] = true;
                $editableFields['LastName'] = true;
                $editableFields['Phone'] = true;
            }
        } elseif ($hasPersonManagerAuth) {
            $editableFields = [
                'Email' => true,
                'FirstName' => true
            ];
            if (!$isImported) {
                $editableFields['LastName'] = true;
                $editableFields['Phone'] = true;
            }
        }

        return $editableFields;
    }

    private function validateAndPrepareUpdate($person, $editableFields)
    {
        $update = [];
        $errors = [];

        // Validation et préparation des champs
        foreach ($editableFields as $field => $allowed) {
            $value = $_POST[$field] ?? null;

            switch ($field) {
                case 'Email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors['Email'] = 'Email invalide';
                    }
                    $update['Email'] = $value;
                    break;
                case 'Password':
                    if (!empty($value)) {
                        $update['Password'] = password_hash($value, PASSWORD_DEFAULT);
                    }
                    break;
                case 'FirstName':
                case 'LastName':
                case 'NickName':
                case 'Phone':
                    $update[$field] = $value;
                    break;
            }
        }

        return [
            'update' => $update,
            'errors' => $errors
        ];
    }

    public function create()
    {
        $currentUser = $this->getCurrentUser();
        $hasPersonManagerAuth = $this->hasPersonManagerAuthorization();

        // Vérifier l'accès
        if (!$currentUser || !$hasPersonManagerAuth) {
            $this->flight->redirect('/');
            return;
        }

        // Récupérer les groupes disponibles
        $stmtGroups = $this->pdo->query('SELECT * FROM "Group" WHERE Inactivated = 0');
        $availableGroups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validationResult = $this->validateNewPerson();

            if (!empty($validationResult['errors'])) {
                echo $this->latte->render('views/persons/create.latte', [
                    'errors' => $validationResult['errors'],
                    'availableGroups' => $availableGroups
                ]);
                return;
            }

            // Début de la transaction
            $this->pdo->beginTransaction();

            try {
                // Insertion de la personne
                $stmt = $this->pdo->prepare('
                    INSERT INTO "Person" (
                        Email, Password, FirstName, LastName, 
                        NickName, Phone, Imported
                    ) VALUES (?, ?, ?, ?, ?, ?, 0)
                ');
                $stmt->execute([
                    $validationResult['data']['Email'],
                    password_hash($validationResult['data']['Password'], PASSWORD_DEFAULT),
                    $validationResult['data']['FirstName'],
                    $validationResult['data']['LastName'],
                    $validationResult['data']['NickName'] ?? null,
                    $validationResult['data']['Phone'] ?? null
                ]);
                $personId = $this->pdo->lastInsertId();

                // Ajouter les groupes
                if (isset($_POST['groups'])) {
                    $stmt = $this->pdo->prepare('INSERT INTO "PersonGroup" (IdPerson, IdGroup) VALUES (?, ?)');
                    foreach ($_POST['groups'] as $groupId) {
                        $stmt->execute([$personId, $groupId]);
                    }
                }

                $this->pdo->commit();
                $this->flight->redirect('/persons');
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            return;
        }

        echo $this->latte->render('views/persons/create.latte', [
            'availableGroups' => $availableGroups
        ]);
    }

    private function validateNewPerson()
    {
        $errors = [];
        $data = [];

        // Validation des champs
        $data['Email'] = $_POST['Email'] ?? '';
        if (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            $errors['Email'] = 'Email invalide';
        }

        // Vérifier si l'email existe déjà
        $stmt = $this->pdo->prepare('SELECT 1 FROM "Person" WHERE Email = ?');
        $stmt->execute([$data['Email']]);
        if ($stmt->fetchColumn()) {
            $errors['Email'] = 'Cet email est déjà utilisé';
        }

        $data['Password'] = $_POST['Password'] ?? '';
        if (empty($data['Password'])) {
            $errors['Password'] = 'Mot de passe requis';
        }

        $data['FirstName'] = $_POST['FirstName'] ?? '';
        if (empty($data['FirstName'])) {
            $errors['FirstName'] = 'Prénom requis';
        }

        $data['LastName'] = $_POST['LastName'] ?? '';
        if (empty($data['LastName'])) {
            $errors['LastName'] = 'Nom requis';
        }

        // Champs optionnels
        $data['NickName'] = $_POST['NickName'] ?? null;
        $data['Phone'] = $_POST['Phone'] ?? null;

        return [
            'data' => $data,
            'errors' => $errors
        ];
    }

    public function delete($id)
    {
        $currentUser = $this->getCurrentUser();
        $hasPersonManagerAuth = $this->hasPersonManagerAuthorization();

        // Vérifier l'accès
        if (!$currentUser || !$hasPersonManagerAuth) {
            $this->flight->redirect('/');
            return;
        }

        // Suppression logique
        $stmt = $this->pdo->prepare('UPDATE "Person" SET Inactivated = 1 WHERE Id = ?');
        $stmt->execute([$id]);

        $this->flight->redirect('/persons');
    }
}
