<?php

namespace app\apis;

use Exception;

use app\helpers\ArticleDataHelper;
use app\helpers\DesignDataHelper;
use app\helpers\Media;
use app\helpers\ReplyHelper;

class ArticleApi extends BaseApi
{
    private Media $media;
    private ReplyHelper $replyHelper;

    public function __construct()
    {
        $this->media = new Media();
        $this->replyHelper = new ReplyHelper();
    }

    public function deleteFile($year, $month, $filename)
    {
        $this->renderJson($this->media->deleteFile($year, $month, $filename));
    }

    public function designVote()
    {
        if ($person = $this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                (new DesignDataHelper())->insertOrUpdate(json_decode(file_get_contents('php://input'), true), $person->Id);
                $this->renderJson(['success' => true]);
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function saveSurveyReply()
    {
        if ($person = $this->personDataHelper->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                $surveyId = $data['survey_id'] ?? null;
                if (!$surveyId) {
                    $this->renderJson(['success' => false, 'message' => 'Missing data'], 400);
                    return;
                }
                $this->replyHelper->insertOrUpdate($person->Id, $surveyId, isset($data['survey_answers']) ? json_encode($data['survey_answers']) : '[]');
                $this->renderJson(['success' => true]);
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function showSurveyReplyForm($articleId)
    {
        if ($person = $this->personDataHelper->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $survey = $this->dataHelper->get('Survey', ['IdArticle' => $articleId]);
                if (!$survey) {
                    $this->renderJson(['success' => false, 'message' => "Aucun sondage trouvé pour l'article $articleId"]);
                    return;
                }
                try {
                    $options = json_decode($survey->Options);
                    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("JSON error: " . json_last_error_msg());
                    $previousReply = $this->replyHelper->get_($survey->Id, $person->Id);
                    $previousAnswers = $previousReply ? json_decode($previousReply->Answers, true) : null;
                    $this->renderJson([
                        'success' => true,
                        'survey' => [
                            'id' => $survey->Id,
                            'question' => $survey->Question,
                            'options' => $options,
                            'previousAnswers' => $previousAnswers
                        ]
                    ]);
                } catch (Exception $e) {
                    $this->renderJson(['success' => false, 'message' => $e->getMessage()]);
                }
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
        } else $this->renderJson(['success' => false, 'message' => 'User not found'], 403);
    }

    public function uploadFile()
    {
        if ($this->personDataHelper->getPerson(['Redactor'])) {
            $response = ['success' => false, 'message' => '', 'file' => null];

            if (empty($_FILES['file'])) {
                $response['message'] = 'Aucun fichier sélectionné';
                $this->renderJson($response);
                return;
            }
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = 'Erreur lors de l\'upload: ' . $this->getUploadErrorMessage($file['error']);
                $this->renderJson($response);
                return;
            }
            $this->renderJson($this->media->uploadFile($file));
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function getAuthor($articleId)
    {
        if (!$articleId) $this->renderJson(['success' => false, 'message' => 'Unknown article'], 499);
        else {
            $result = (new ArticleDataHelper())->getAuthor($articleId);
            $this->renderJson(['author' => $result ? [$result] : []]);
        }
    }

    #region private methods
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
    #endregion
}
