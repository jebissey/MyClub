<?php

namespace app\controllers;

use app\helpers\EventAudience;
use Exception;

class ApiController extends BaseController
{

    /* #region survey */
    public function saveSurveyReply()
    {
        if ($person = $this->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                $surveyId = $data['survey_id'] ?? null;
                if (!$surveyId) {
                    header('Content-Type: application/json', true, 400);
                    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                    exit();
                }
                $answers = isset($data['survey_answers']) ? json_encode($data['survey_answers']) : '[]';

                $existingReply = $this->fluent->from('Reply')
                    ->where('IdPerson', $person['Id'])
                    ->where('IdSurvey', $surveyId)
                    ->fetch();
                if ($existingReply) {
                    $this->fluent->update('Reply')
                        ->set(['Answers' => $answers])
                        ->where('Id', $existingReply['Id'])
                        ->execute();
                } else {
                    $this->fluent->insertInto('Reply')
                        ->values([
                            'IdPerson' => $person['Id'],
                            'IdSurvey' => $surveyId,
                            'Answers' => $answers
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
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        exit();
    }

    public function showSurveyReplyForm($articleId)
    {
        if ($person = $this->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $survey = $this->fluent->from('Survey')
                    ->where('IdArticle', $articleId)
                    ->fetch();

                if (!$survey) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => "Aucun sondage trouvé pour l'article $articleId"]);
                    exit();
                }

                try {
                    $options = json_decode($survey['Options']);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Erreur de décodage JSON : " . json_last_error_msg());
                    }

                    $previousReply = $this->fluent->from('Reply')
                        ->where('IdSurvey', $survey['Id'])
                        ->where('IdPerson', $person['Id'])
                        ->fetch();

                    $previousAnswers = $previousReply ? json_decode($previousReply['Answers'], true) : null;

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'survey' => [
                            'id' => $survey['Id'],
                            'question' => $survey['Question'],
                            'options' => $options,
                            'previousAnswers' => $previousAnswers
                        ]
                    ]);
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
    /* #endregion */

    /* #region design */
    public function designVote()
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
    /* #endregion */

    /* #region attribute */
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
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
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
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
    }
    /* #endregion */

    /* #region media */
    public function uploadFile()
    {
        if ($this->getPerson(['Redactor'])) {
            $response = ['success' => false, 'message' => '', 'file' => null];

            if (empty($_FILES['file'])) {
                $response['message'] = 'Aucun fichier sélectionné';
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = 'Erreur lors de l\'upload: ' . $this->getUploadErrorMessage($file['error']);
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            $year = date('Y');
            $month = date('m');
            $targetDir = $this->mediaPath . $year . '/' . $month . '/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $originalName = $file['name'];
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $baseFilename = pathinfo($originalName, PATHINFO_FILENAME);
            $safeFilename = $this->sanitizeFilename($baseFilename);
            $targetFile = $targetDir . $safeFilename . '.' . $extension;
            $counter = 1;
            while (file_exists($targetFile)) {
                $targetFile = $targetDir . $safeFilename . '_' . $counter . '.' . $extension;
                $counter++;
            }

            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $relativePath = 'data/media/' . $year . '/' . $month . '/' . basename($targetFile);
                $response = [
                    'success' => true,
                    'message' => 'Fichier uploadé avec succès',
                    'file' => [
                        'name' => basename($targetFile),
                        'path' => $relativePath,
                        'url' => $this->getBaseUrl() . $relativePath,
                        'size' => $file['size'],
                        'type' => $file['type']
                    ]
                ];
            } else {
                $response['message'] = 'Erreur lors de l\'enregistrement du fichier';
            }
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function deleteFile($year, $month, $filename)
    {
        $filePath = $this->mediaPath . $year . '/' . $month . '/' . $filename;
        $response = ['success' => false, 'message' => ''];

        if (!file_exists($filePath)) {
            $response['message'] = 'Fichier non trouvé';
        } else {
            if (unlink($filePath)) {
                $response['success'] = true;
                $response['message'] = 'Fichier supprimé avec succès';

                $monthDir = $this->mediaPath . $year . '/' . $month;
                if (count(glob("$monthDir/*")) === 0) {
                    rmdir($monthDir);

                    $yearDir = $this->mediaPath . $year;
                    if (count(glob("$yearDir/*")) === 0) {
                        rmdir($yearDir);
                    }
                }
            } else {
                $response['message'] = 'Erreur lors de la suppression du fichier';
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }



    private function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        return $filename;
    }

    private function getUploadErrorMessage($error)
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par PHP';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par le formulaire';
            case UPLOAD_ERR_PARTIAL:
                return 'Le fichier n\'a été que partiellement uploadé';
            case UPLOAD_ERR_NO_FILE:
                return 'Aucun fichier n\'a été uploadé';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Dossier temporaire manquant';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Échec d\'écriture du fichier sur le disque';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload arrêté par extension';
            default:
                return 'Erreur inconnue';
        }
    }
    /* #endregion */

    /* #region nav bar */
    public function saveItem()
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
                $newPosition = ($maxPosition && $maxPosition['MaxPos']) ? $maxPosition['MaxPos'] + 1 : 1;

                $this->fluent->insertInto('Page')
                    ->values([
                        'Name' => $data['name'],
                        'Route' => $data['route'],
                        'Position' => $newPosition,
                        'IdGroup' => $data['idGroup'],
                        'OnlyForMembers' => $data['onlyForMembers']
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
                        'OnlyForMembers' => $data['onlyForMembers']
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

    public function getItem($id)
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

    public function updatePositions()
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

    public function deleteItem($id)
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
    /* #endregion */

    public function lastVersion()
    {
        $query = $this->pdoForLog->prepare('INSERT INTO Log(IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token, Who, Code, Message) 
        VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,?)');
        $query->execute([$_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_REFERER'] ?? '', '', '', '', '', $_SERVER['REQUEST_URI'], '', gethostbyaddr($_SERVER['REMOTE_ADDR']) ?? '', '', $_SERVER['HTTP_USER_AGENT']]);

        header('Content-Type: application/json');
        echo json_encode(['lastVersion' => self::VERSION]);
        exit();
    }

    public function getVisitorsByDate()
    {
        if ($this->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $query = $this->fluentForLog
                    ->from('Log')
                    ->select('date(CreatedAt) as date, COUNT(*) as count')
                    ->groupBy('date(CreatedAt)')
                    ->orderBy('date');

                $results = $query->fetchAll();

                $dates = [];
                $counts = [];

                foreach ($results as $row) {
                    $dates[] = $row['date'];
                    $counts[] = $row['count'];
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'labels' => $dates,
                    'data' => $counts
                ]);
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

    /* #region group */
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

    public function addToGroup($personId, $groupId)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $insert = $this->pdo->prepare("INSERT INTO PersonGroup (IdPerson, IdGroup) VALUES (?, ?)");
            $success = $insert->execute([$personId, $groupId]);

            echo json_encode(['success' => $success]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function removeFromGroup($personId, $groupId)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $delete = $this->pdo->prepare("DELETE FROM PersonGroup WHERE IdPerson = ? AND IdGroup = ?");
            $success = $delete->execute([$personId, $groupId]);

            echo json_encode(['success' => $success]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }
    /* #endregion */

    /* #region event */
    public function getAttributesByEventType($eventTypeId)
    {
        if (!$eventTypeId) {
            header('Content-Type: application/json', true, 499);
            echo json_encode(['success' => false, 'message' => 'Unknown event type']);
        } else {
            $query = $this->fluent->from('EventTypeAttribute')
                ->select('Attribute.*')
                ->join('Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id')
                ->where('EventTypeAttribute.IdEventType', $eventTypeId);
            header('Content-Type: application/json');
            echo json_encode(['attributes' => $query->fetchAll()]);
        }
        exit();
    }


    public function getEvent($id): void
    {
        if ($this->getPerson(['EventManager'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'event' => $this->fluent->from('Event')->where('Id', $id)->fetch(),
                'attributes' => $this->fluent->from('EventAttribute')->where('IdEvent', $id)->fetchall(),
            ]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function saveEvent(): void
    {
        if ($person = $this->getPerson(['EventManager'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            $values = [
                'Summary' => $data['summary'] ?? '',
                'Description' => $data['description'] ?? '',
                'Location' => $data['location'] ?? '',
                'StartTime' => $data['startTime'],
                'Duration' => $data['duration'] ?? 1,
                'IdEventType' => $data['idEventType'],
                'CreatedBy' => $person['Id'],
                'MaxParticipants' => $data['maxParticipants'] ?? 0,
                'Audience' => $data['audience'] ?? EventAudience::ForClubMembersOnly->value,
            ];

            $this->pdo->beginTransaction();
            try {
                if ($data['formMode'] == 'create') {

                    $eventId = $this->fluent->insertInto('Event')->values($values)->execute();
                    if (isset($data['attributes']) && is_array($data['attributes'])) {
                        foreach ($data['attributes'] as $attributeId) {
                            $this->fluent->insertInto('EventAttribute')
                                ->values([
                                    'IdEvent' => $eventId,
                                    'IdAttribute' => $attributeId
                                ])
                                ->execute();
                        }
                    }
                } elseif ($data['formMode'] == 'update') {
                    $this->fluent->update('Event')->set($values)->where('Id', $data['id'])->execute();
                    $this->fluent->deleteFrom('EventAttribute')->where('IdEvent', $data['id'])->execute();
                    if (isset($data['attributes']) && is_array($data['attributes'])) {
                        foreach ($data['attributes'] as $attributeId) {
                            $this->fluent->insertInto('EventAttribute')
                                ->values([
                                    'IdEvent' => $data['id'],
                                    'IdAttribute' => $attributeId
                                ])
                                ->execute();
                        }
                    }
                } else die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__ . " with formMode=" . $data['formMode']);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'eventId' => $data['id']]);
                $this->pdo->commit();
            } catch (Exception $e) {
                $this->pdo->rollBack();

                header('Content-Type: application/json', true, 500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de l\'insertion en base de données',
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function deleteEvent($id): void
    {
        if ($person = $this->getPerson(['EventManager'])) {

            if (!$this->fluent->from('Event')->where('Id', $id)->where('CreatedBy', $person['Id'])->fetch()) {
                header('Content-Type: application/json', true, 403);
                echo json_encode(['success' => false, 'message' => 'Not allowed user']);
                exit;
            }
            try {
                $this->pdo->beginTransaction();

                $this->fluent->deleteFrom('EventAttribute')->where('IdEvent', $id)->execute();
                // TODO manage participant and paticipantSupply
                $this->fluent->deleteFrom('Event')->where('Id', $id)->execute();

                $this->pdo->commit();

                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $this->pdo->rollBack();

                header('Content-Type: application/json', true, 500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression en base de données',
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }
    /* #endregion */

    /* #region needs */
    public function saveNeedType()
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? '';
                $name = $data['name'] ?? '';
                if (empty($name)) {
                    header('Content-Type: application/json', true, 472);
                    echo json_encode(['success' => false, 'message' => "Missing parameter name ='$name'"]);
                } else {
                    try {
                        if ($id) {
                            $this->fluent->update('NeedType')->set(['Name' => $name])->where('Id', $id)->execute();
                        } else {
                            $id = $this->fluent->insertInto('NeedType')->values(['Name' => $name])->execute();
                        }
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'id' => $id]);
                    } catch (\Exception $e) {
                        $this->flight->json(['success' => 'false', 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
                    }
                }
            } else {
                header('Content-Type: application/json', true, 470);
                echo json_encode(['success' => false, 'message' => 'Bad request method']);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function deleteNeedType($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                if (!$id) {
                    header('Content-Type: application/json', true, 472);
                    echo json_encode(['success' => false, 'message' => 'Missing Id parameter']);
                } else {
                    $countNeeds = $this->fluent->from('Need')->where('IdNeedType', $id)->count();

                    if ($countNeeds > 0) {
                        header('Content-Type: application/json', true, 409);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Ce type de besoin est associé à ' . $countNeeds . ' besoin(s) et ne peut pas être supprimé'
                        ]);
                    } else {
                        try {
                            $this->fluent->deleteFrom('NeedType')
                                ->where('Id', $id)
                                ->execute();

                            header('Content-Type: application/json');
                            echo json_encode(['success' => true]);
                        } catch (\Exception $e) {
                            header('Content-Type: application/json', true, 500);
                            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
                        }
                    }
                }
            } else {
                header('Content-Type: application/json', true, 470);
                echo json_encode(['success' => false, 'message' => 'Bad request method']);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function saveNeed()
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? false;
                $name = $data['name'] ?? '';
                $participantDependent = isset($data['participantDependent']) ? intval($data['participantDependent']) : 0;
                $idNeedType = $data['idNeedType'] ?? null;

                if (empty($name)) {
                    header('Content-Type: application/json', true, 472);
                    echo json_encode(['success' => false, 'message' => 'Missing parameter name']);
                    exit();
                }

                if (!$idNeedType) {
                    header('Content-Type: application/json', true, 472);
                    echo json_encode(['success' => false, 'message' => 'Missing parameter idNeedType']);
                    exit();
                }

                try {
                    $needData = [
                        'Name' => $name,
                        'ParticipantDependent' => $participantDependent,
                        'IdNeedType' => $idNeedType
                    ];

                    if ($id) {
                        $this->fluent->update('Need')->set($needData)->where('Id', $id)->execute();
                    } else {
                        $id = $this->fluent->insertInto('Need')->values($needData)->execute();
                    }

                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'id' => $id]);
                } catch (\Exception $e) {
                    header('Content-Type: application/json', true, 500);
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
                }
            } else {
                header('Content-Type: application/json', true, 470);
                echo json_encode(['success' => false, 'message' => 'Bad request method']);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function deleteNeed($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                if (!$id) {
                    header('Content-Type: application/json', true, 472);
                    echo json_encode(['success' => false, 'message' => 'Missing ID parameter']);
                } else {
                    try {
                        $this->fluent->deleteFrom('Need')->where('Id', $id)->execute();

                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } catch (\Exception $e) {
                        header('Content-Type: application/json', true, 500);
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
                    }
                }
            } else {
                header('Content-Type: application/json', true, 470);
                echo json_encode(['success' => false, 'message' => 'Bad request method']);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }
    /* #endregion */
}
