<?php

namespace app\api;

use Exception;
use app\controllers\BaseController;

class WebmasterApi extends BaseController
{

    public function addToGroup($personId, $groupId)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $success = $this->fluent->insertInto('PersonGroup', [
                'IdPerson' => $personId,
                'IdGroup'  => $groupId
            ])->execute();

            echo json_encode(['success' => $success]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function getNavbarItem($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            $item = $this->fluent->from('Page')->where('Id', $id)->fetch();
            header('Content-Type: application/json');
            echo json_encode($item);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

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
                } catch (Exception $e) {
                    header('Content-Type: application/json', true, 500);
                    echo json_encode(['error' => $e->getMessage()]);
                }
            } else {
                header('Content-Type: application/json', true, 470);
                echo json_encode(['success' => false, 'message' => 'Bad request method']);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        exit();
    }

    public function lastVersion()
    {
        $this->fluentForLog
            ->insertInto('Log', [
                'IpAddress'        => $_SERVER['REMOTE_ADDR'],
                'Referer'          => $_SERVER['HTTP_REFERER'] ?? '',
                'Os'               => '',
                'Browser'          => '',
                'ScreenResolution' => '',
                'Type'             => '',
                'Uri'              => $_SERVER['REQUEST_URI'],
                'Token'            => '',
                'Who'              => gethostbyaddr($_SERVER['REMOTE_ADDR']) ?? '',
                'Code'             => '',
                'Message'          => $_SERVER['HTTP_USER_AGENT']
            ])
            ->execute();

        header('Content-Type: application/json');
        echo json_encode(['lastVersion' => self::VERSION]);
        exit();
    }

    public function removeFromGroup($personId, $groupId)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $success = $this->fluent->deleteFrom('PersonGroup')
                ->where('IdPerson', $personId)
                ->where('IdGroup', $groupId)
                ->execute();

            echo json_encode(['success' => $success]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function saveNavbarItem()
    {
        if ($this->getPerson(['Webmaster'])) {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name']) || empty($data['route'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Name and Route are required']);
                exit();
            }

            if (empty($data['id'])) {
                $maxPosition = $this->fluent->from('Page')->select('MAX(Position) AS MaxPos')->fetch();
                $newPosition = ($maxPosition && $maxPosition->MaxPos) ? $maxPosition->MaxPos + 1 : 1;

                $this->fluent->insertInto('Page')
                    ->values([
                        'Name' => $data['name'],
                        'Route' => $data['route'],
                        'Position' => $newPosition,
                        'IdGroup' => $data['idGroup'],
                        'ForMembers' => $data['forMembers'],
                        'ForAnonymous' => $data['forAnonymous'],
                    ])
                    ->execute();
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                $this->fluent->update('Page')
                    ->set([
                        'Name' => $data['name'],
                        'Route' => $data['route'],
                        'IdGroup' => $data['idGroup'],
                        'ForMembers' => $data['forMembers'],
                        'ForAnonymous' => $data['forAnonymous'],
                    ])
                    ->where('Id', $data['id'])
                    ->execute();
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function updateNavbarPositions()
    {
        if ($this->getPerson(['Webmaster'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            $positions = $data['positions'];

            foreach ($positions as $id => $position) {
                $this->fluent->update('Page')
                    ->set(['Position' => $position])
                    ->where('Id', $id)
                    ->execute();
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function deleteNavbarItem($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            $result = $this->fluent->deleteFrom('Page')->where('Id', $id)->execute();
            header('Content-Type: application/json');
            echo json_encode(['success' => $result == 1]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }
}
