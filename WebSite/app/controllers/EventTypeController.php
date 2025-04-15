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
}
