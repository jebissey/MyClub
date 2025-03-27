<?php

namespace app\controllers;

use PDO;

class EventTypeController extends TableController implements CrudControllerInterface
{
    public function index()
    {
        if ($this->getPerson(['Webmaster'])) {
            $filterValues = [];
            $filterConfig = [];
            $columns = [
                ['field' => 'EventTypeName', 'label' => 'Nom'],
                ['field' => 'GroupName', 'label' => 'Groupe'],
                ['field' => 'Attributes', 'label' => 'Attributs'],
            ];
            $query = $this->fluent->from('EventType')
                ->select('EventType.Id AS EventTypeId, EventType.Name AS EventTypeName, `Group`.Name AS GroupName')
                ->select('GROUP_CONCAT(Attribute.Name, ", ") AS Attributes')
                ->leftJoin('`Group` ON EventType.IdGroup = `Group`.Id')
                ->leftJoin('EventTypeAttribute ON EventType.Id = EventTypeAttribute.IdEventType')
                ->leftJoin('Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id')
                ->where('EventType.Inactivated', 0)
                ->groupBy('EventType.Id')
                ->orderBy('EventType.Name');

            $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
            echo $this->latte->render('app/views/eventType/index.latte', $this->params->getAll([
                'eventTypes' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/eventTypes'
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function create()
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $query = $this->pdo->prepare("
                        INSERT INTO EventType (Name) 
                        VALUES ('')
                    ");
                $query->execute([]);
                $id = $this->pdo->lastInsertId();
                $this->flight->redirect('/EventTypes/edit/' . $id);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function edit($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            $eventType = $this->fluent->from('`EventType`')->where('Id', $id)->fetch();
            if (!$eventType) {
                $this->application->error499('EventType', $id, __FILE__, __LINE__);
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->pdo->beginTransaction();
                    try {
                        $name = $_POST['name'];
                        $idGroup = $_POST['idGroup'];
                        $query = $this->pdo->prepare('UPDATE EventType SET Name = ?, IdGroup = ? WHERE Id = ?');
                        $query->execute([$name, $idGroup, $id]);

                        $deleteQuery = $this->pdo->prepare('DELETE FROM EventTypeAttribute WHERE IdEventType = ?');
                        $deleteQuery->execute([$id]);

                        if (isset($_POST['attributes']) && is_array($_POST['attributes'])) {
                            $insertQuery = $this->pdo->prepare('INSERT INTO EventTypeAttribute (IdEventType, IdAttribute) VALUES (?, ?)');
                            foreach ($_POST['attributes'] as $attributeId) {
                                $insertQuery->execute([$id, $attributeId]);
                            }
                        }
                        $this->pdo->commit();
                        $this->flight->redirect('/eventTypes');
                    } catch (\Exception $e) {
                        $this->pdo->rollBack();
                        throw $e;
                    }
                } else if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                    $existingAttributesQuery = $this->pdo->prepare('
                        SELECT IdAttribute 
                        FROM EventTypeAttribute 
                        WHERE IdEventType = ?
                    ');
                    $existingAttributesQuery->execute([$id]);
                    $existingAttributes = $existingAttributesQuery->fetchAll(PDO::FETCH_COLUMN);

                    echo $this->latte->render('app/views/eventType/edit.latte', $this->params->getAll([
                        'name' => $eventType['Name'],
                        'idGroup' => $eventType['IdGroup'],
                        'groups' => $this->fluent->from('`Group`')->where('Inactivated', 0)->orderBy('Name')->fetchAll(),
                        'attributes' => $this->fluent->from('Attribute')->orderBy('Name')->fetchAll(),
                        'existingAttributes' => $existingAttributes
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
        if ($this->getPerson(['Webmaster'])) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $query = $this->pdo->prepare('UPDATE "EventType" SET Inactivated = 1 WHERE Id = ?');
                $query->execute([$id]);

                $this->flight->redirect('/eventTypes');
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function getAttributes()
    {
        if ($this->getPerson(['Webmaster'])) {
            $attributes = $this->fluent->from('Attribute')
                ->orderBy('Name')
                ->fetchAll();

            echo $this->latte->render('app/views/eventType/attributes-list.latte', $this->params->getAll([
                'attributes' => $attributes
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function createAttribute()
    {
        if ($this->getPerson(['Webmaster'])) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            try {
                $this->pdo->beginTransaction();
                $query = $this->pdo->prepare('INSERT INTO Attribute (Name, Detail, Color) VALUES (?, ?, ?)');
                $query->execute([$data['name'], $data['detail'], $data['color']]);
                $this->pdo->commit();

                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                header('Content-Type: application/json', true, 500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
    }

    public function updateAttribute()
    {
        if ($this->getPerson(['Webmaster'])) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            try {
                $this->pdo->beginTransaction();
                $query = $this->pdo->prepare('UPDATE Attribute SET Name = ?, Detail = ?, Color = ? WHERE Id = ?');
                $query->execute([$data['name'], $data['detail'], $data['color'], $data['id']]);
                $this->pdo->commit();

                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
    }

    public function deleteAttribute($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            try {
                $this->pdo->beginTransaction();

                $deleteAssociationsQuery = $this->pdo->prepare('
                    DELETE FROM EventTypeAttribute 
                    WHERE IdAttribute = ?
                ');
                $deleteAssociationsQuery->execute([$id]);

                $deleteQuery = $this->pdo->prepare('
                    DELETE FROM Attribute 
                    WHERE Id = ?
                ');
                $deleteQuery->execute([$id]);

                $attributes = $this->fluent->from('Attribute')
                    ->orderBy('Name')
                    ->fetchAll();

                $this->pdo->commit();

                echo $this->latte->render('app/views/eventType/attributes-list.latte', $this->params->getAll([
                    'attributes' => $attributes
                ]));
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
