<?php

namespace app\controllers;

use PDO;

class WebmasterController extends BaseController
{
    public function help(): void
    {
        $this->getPerson();

        echo $this->latte->render('app/views/info.latte', [
            'content' => $this->settings->getHelpWebmaster(),
            'hasAuthorization' => $this->authorizations->hasAutorization()
        ]);
    }

    public function home(): void
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo $this->latte->render('app/views/admin/webmaster.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        }
    }

    public function arwards(): void
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $query = $this->pdo->query('SELECT DISTINCT Name FROM Counter ORDER BY Name');
                $counterNames = array_column($query->fetchAll(), 'Name');

                $query = $this->pdo->query('
                    SELECT p.Id, p.FirstName, p.LastName, p.NickName, c.Name as CounterName, SUM(c.Value) as CounterValue, (SELECT SUM(Value) FROM Counter WHERE IdPerson = p.Id) as Total
                    FROM Person p
                    LEFT JOIN Counter c ON p.Id = c.IdPerson
                    GROUP BY p.Id, p.FirstName, p.LastName, p.NickName, c.Name
                    HAVING Total > 0
                    ORDER BY Total DESC');
                $results =  $query->fetchAll(PDO::FETCH_ASSOC);
                $data = [];
                foreach ($results as $row) {
                    $personId = $row['Id'];
                    if (!isset($data[$personId])) {
                        $data[$personId] = [
                            'name' => trim(sprintf(
                                '%s %s %s',
                                $row['FirstName'],
                                $row['LastName'],
                                $row['NickName'] ? "({$row['NickName']})" : ''
                            )),
                            'counters' => array_fill_keys($counterNames, 0),
                            'total' => $row['Total']
                        ];
                    }
                    if ($row['CounterName']) {
                        $data[$personId]['counters'][$row['CounterName']] = $row['CounterValue'];
                    }
                }
                $query = $this->pdo->query("
                    SELECT Id, Name
                    FROM 'Group'
                    WHERE Inactivated = 0
                ");
                $groups =  $query->fetchAll(PDO::FETCH_ASSOC);
                echo $this->latte->render('app/views/admin/arwards.latte', $this->params->getAll([
                    'counterNames' => $counterNames,
                    'data' => $data,
                    'groups' => $groups
                ]));
            } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $request = $this->flight->request();
                $name = $request->data->customName ?? $request->data->name;
                $detail = $request->data->detail;
                $value = (int)$request->data->value;
                $idPerson = (int)$request->data->idPerson;
                $idGroup = (int)$request->data->idGroup;

                if (empty($name) || $value < 0 || $idPerson <= 0 || $idGroup <= 0) {
                    $this->flight->redirect('/arwards?error=invalid_data');
                } else {
                    try {
                        $this->fluent->insertInto('Counter')
                            ->values([
                                'Name' => $name,
                                'Detail' => $detail,
                                'Value' => $value,
                                'IdPerson' => $idPerson,
                                'IdGroup' => $idGroup
                            ])
                            ->execute();
                        $this->flight->redirect('/arwards?success=true');
                    } catch (\Exception $e) {
                        $this->flight->redirect('/arwards?error=' . urlencode($e->getMessage()));
                    }
                }
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        }
    }
}
