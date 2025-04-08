<?php

namespace app\controllers;

use PDO;

class DesignController extends BaseController
{
    public function index()
    {
        if ($person = $this->getPerson(['Redactor'])) {
            $query = "SELECT d.Id, d.Name, d.Detail, d.NavBar, d.Status, d.OnlyForMembers, d.IdGroup, 
                p.FirstName || ' ' || p.LastName || 
                    CASE WHEN p.NickName IS NOT NULL AND p.NickName != '' 
                        THEN ' (' || p.NickName || ')' 
                        ELSE '' 
                    END AS NameOfDesigner,
                    CASE 
                        WHEN COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) = 0 
                            AND COUNT(CASE WHEN dv.Vote = 'voteDown' THEN 1 END) = 0
                            AND COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) = 0
                        THEN '0/0'
                        ELSE 
                            COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) || ' / ' || 
                            (COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) + COUNT(CASE WHEN dv.Vote = 'voteDown' THEN 1 END)) ||
                            CASE 
                                WHEN COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) > 0 
                                THEN ' (+' || COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) || ')' 
                                ELSE '' 
                            END
                    END AS Votes
                FROM Design d
                LEFT JOIN DesignVote dv ON d.Id = dv.IdDesign
                JOIN Person p ON d.IdPerson = p.Id
                GROUP BY d.Id";
            $designs = $this->pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

            $groups = $this->fluent->from("'Group'")->orderBy('Name ASC')->fetchAll();

            $userVotes = [];
            $votes = $this->fluent->from('DesignVote')
                ->where('IdPerson', $person['Id'])
                ->fetchAll();

            foreach ($votes as $vote) {
                $userVotes[$vote['IdDesign']] = $vote['Vote'];
            }

            $this->latte->render('app/views/designs/index.latte', $this->params->getAll([
                'designs' => $designs,
                'groups' => $groups,
                'userVotes' => $userVotes
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function vote()
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);

                $designId = (int)$data['designId'] ?? 0;
                $userId = $person['Id'];
                $voteValue = $data['vote'] ?? 'voteNeutral';

                $existingVote = $this->fluent->from('DesignVote')
                    ->where('IdDesign', $designId)
                    ->where('IdPerson', $userId)
                    ->fetch();
                if ($existingVote) {
                    $this->fluent->update('DesignVote')
                        ->set(['Vote' => $voteValue])
                        ->where('Id', $existingVote['Id'])
                        ->execute();
                } else {
                    $this->fluent->insertInto('DesignVote')
                        ->values([
                            'IdDesign' => $designId,
                            'IdPerson' => $userId,
                            'Vote' => $voteValue
                        ])
                        ->execute();
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                header('Content-Type: application/json', true, 470);
                echo json_encode(['success' => false, 'message' => 'Bad request method']);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
    }

    public function create()
    {
        if ($this->getPerson(['Redactor'])) {
            $groups = $this->fluent->from("'Group'")->orderBy('Name ASC')->fetchAll();

            $this->latte->render('app/views/designs/create.latte', $this->params->getAll([
                'groups' => $groups
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function store()
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $values = [
                    'IdPerson' => $person['Id'],
                    'Name' => $_POST['name'] ?? '',
                    'Detail' => $_POST['detail'] ?? '',
                    'NavBar' => $_POST['navbar'] ?? '',
                    'Status' => 'UnderReview',
                    'OnlyForMembers' => $_POST['onlyForMembers'] ? 1 : 0,
                    'IdGroup' => $_POST['idGroup'] ?? null
                ];
                $this->fluent->insertInto('Design')->values($values)->execute();

                $this->flight->redirect('/designs');
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
