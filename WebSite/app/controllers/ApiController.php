<?php

namespace app\controllers;

use Exception;

class ApiController extends BaseController
{
    public function getPersonsByGroup($id)
    {
        if ($this->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                try {
                    $users = $this->fluent
                        ->from('PersonGroup')
                        ->join('Person ON PersonGroup.IdPerson = Person.Id')
                        ->where('PersonGroup.IdGroup', $id)
                        ->where('Person.Inactivated', 0)
                        ->select('Person.Id, Person.FirstName, Person.LastName, Person.Email')
                        ->orderBy('Person.FirstName ASC, Person.LastName ASC')
                        ->fetchAll();
                    if (!$users) {
                        $users = [];
                    }

                    header('Content-Type: application/json');
                    echo json_encode($users);
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json', true, 500);
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
            } else {
                header('Content-Type: application/json', true, 470);
                echo json_encode(['success' => false, 'message' => 'Bad request method']);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }
}
