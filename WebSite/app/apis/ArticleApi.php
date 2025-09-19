<?php
declare(strict_types=1);

namespace app\apis;

use Exception;
use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\Media;
use app\models\ArticleDataHelper;
use app\models\DataHelper;
use app\models\DesignDataHelper;
use app\models\PersonDataHelper;
use app\models\ReplyDataHelper;

class ArticleApi extends AbstractApi
{
    private Media $media;
    private ReplyDataHelper $replyDataHelper;

    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private DesignDataHelper $designDataHelper,
        private ArticleDataHelper $articleDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
        $this->media = new Media();
        $this->replyDataHelper = new ReplyDataHelper($application);
    }

    public function deleteFile(int $year, int $month, string $filename): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->renderJson($this->media->deleteFile($year, $month, $filename), true, ApplicationError::Ok->value);
    }

    public function designVote(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->designDataHelper->insertOrUpdate(json_decode(file_get_contents('php://input'), true), $this->application->getConnectedUser()->person->Id);
        $this->renderJson([], true, ApplicationError::Ok->value);
    }

    public function saveSurveyReply(): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!$person) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $surveyId = $data['survey_id'] ?? null;
        if (!$surveyId) {
            $this->renderJsonBadRequest('Missing data', __FILE__, __LINE__);
            return;
        }
        $this->replyDataHelper->insertOrUpdate($person->Id, $surveyId, isset($data['survey_answers']) ? json_encode($data['survey_answers']) : '[]');
        $this->renderJson([], true, ApplicationError::Ok->value);
    }

    public function showSurveyReplyForm(int $articleId): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($person === false) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $survey = $this->dataHelper->get('Survey', ['IdArticle' => $articleId], 'Id, Question, Options');
        if (!$survey) {
            $this->renderJsonBadRequest("Aucun sondage trouvé pour l'article {$articleId}", __FILE__, __LINE__);
            return;
        }
        try {
            $options = json_decode($survey->Options);
            if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("JSON error: " . json_last_error_msg());
            $previousReply = $this->replyDataHelper->get_($survey->Id, $person->Id);
            $previousAnswers = $previousReply ? json_decode($previousReply->Answers, true) : null;
            $this->renderJson([
                'survey' => [
                    'id' => $survey->Id,
                    'question' => $survey->Question,
                    'options' => $options,
                    'previousAnswers' => $previousAnswers
                ]
            ], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function uploadFile(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (empty($_FILES['file'])) {
            $this->renderJson(['message' => 'Aucun fichier sélectionné'], false, ApplicationError::Ok->value);
            return;
        }
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $response = ['message' => 'Erreur lors de l\'upload: ' . $this->getUploadErrorMessage($file['error'])];
            $this->renderJson($response, false, ApplicationError::Ok->value);
            return;
        }
        $this->renderJson($this->media->uploadFile($file), true, ApplicationError::Ok->value);
    }

    public function getAuthor(int $articleId): void
    {
        $result = $this->articleDataHelper->getAuthor($articleId);
        if ($result === false) $this->renderJsonBadRequest("Unknown article {$articleId}", __FILE__, __LINE__);
        $this->renderJson(['author' => $result ? [$result] : []], true, ApplicationError::Ok->value);
    }

    #region private methods
    private function getUploadErrorMessage(int $error)
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
