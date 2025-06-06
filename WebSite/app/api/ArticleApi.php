<?php

namespace app\api;

use Exception;
use app\controllers\BaseController;

class ArticleApi extends BaseController
{
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

    public function designVote()
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);

                $designId = (int)$data['designId'] ?? 0;
                $userId = $person->Id;
                $voteValue = $data['vote'] ?? 'voteNeutral';

                $existingVote = $this->fluent->from('DesignVote')
                    ->where('IdDesign', $designId)
                    ->where('IdPerson', $userId)
                    ->fetch();
                if ($existingVote) {
                    $this->fluent->update('DesignVote')
                        ->set(['Vote' => $voteValue])
                        ->where('Id', $existingVote->Id)
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
                    ->where('IdPerson', $person->Id)
                    ->where('IdSurvey', $surveyId)
                    ->fetch();
                if ($existingReply) {
                    $this->fluent->update('Reply')
                        ->set(['Answers' => $answers])
                        ->where('Id', $existingReply->Id)
                        ->execute();
                } else {
                    $this->fluent->insertInto('Reply')
                        ->values([
                            'IdPerson' => $person->Id,
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
                    $options = json_decode($survey->Options);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Erreur de décodage JSON : " . json_last_error_msg());
                    }

                    $previousReply = $this->fluent->from('Reply')
                        ->where('IdSurvey', $survey->Id)
                        ->where('IdPerson', $person->Id)
                        ->fetch();

                    $previousAnswers = $previousReply ? json_decode($previousReply->Answers, true) : null;

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'survey' => [
                            'id' => $survey->Id,
                            'question' => $survey->Question,
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

    public function getAuthor($articleId)
    {
        if (!$articleId) {
            header('Content-Type: application/json', true, 499);
            echo json_encode(['success' => false, 'message' => 'Unknown article']);
        } else {
            $query = $this->fluent
                ->from('Article')
                ->where('Article.Id = ?', $articleId)
                ->join('Person ON Article.CreatedBy = Person.Id')
                ->select('CASE WHEN Person.NickName != "" THEN Person.FirstName || " " || Person.LastName || " (" || Person.NickName || ")" ELSE Person.FirstName || " " || Person.LastName END AS PersonName')
                ->select('Article.Title AS ArticleTitle');
            
            $result = $query->fetch();
            
            header('Content-Type: application/json');
            echo json_encode(['author' => $result ? [$result] : []]);
        }
        exit();
    }


    /* #region private methods */
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

    private function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        return $filename;
    }
    /* #endregion */
}
