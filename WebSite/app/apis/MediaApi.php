<?php

declare(strict_types=1);

namespace app\apis;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\Media;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\PersonDataHelper;
use app\models\SharedFileDataHelper;

class MediaApi extends AbstractApi
{
    private Media $media;

    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        SharedFileDataHelper $sharedFileDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
        $this->media = new Media($dataHelper, $sharedFileDataHelper);
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

    public function isShared(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = $this->getJsonInput();
        $filePath = $data['item'] ?? null;
        if (!$filePath) {
            $this->renderJson(['error' => 'Fichier manquant'], false, ApplicationError::BadRequest->value);
            return;
        }
        $this->renderJson($this->media->isShared($filePath), true, ApplicationError::Ok->value);
    }

    public function removeFileShare(int $year, int $month, string $filename): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $response = $this->media->removeFileShare($year, $month, $filename);
        $this->renderJson($response, $response['success'], $response['success'] ? ApplicationError::Ok->value : ApplicationError::BadRequest->value);
    }

    public function shareFile(int $year, int $month, string $filename): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = $this->getJsonInput();
        $response = $this->media->sharefile($year, $month, $filename, WebApp::nullableCast($data['idGroup'] ?? null, 'int'), $data['membersOnly']);
        $this->renderJson($response, $response['success'], $response['success'] ? ApplicationError::Ok->value : ApplicationError::BadRequest->value);
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
