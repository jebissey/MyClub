<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\MediaManager;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\PersonDataHelper;

class MediaApi extends AbstractApi
{

    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private MediaManager $mediaManager
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function deleteFile(int $year, int $month, string $filename): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isRedactor())) {
            $this->renderJsonOk($this->mediaManager->deleteFile($year, $month, $filename));
        }
    }

    public function isShared(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor())) {
            $filePath = trim($_GET['path']  ?? '');
            $data['path'] ?? null;
            if (!$filePath) {
                $this->renderJsonBadRequest('Fichier manquant', __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk($this->mediaManager->isShared($filePath));
        }
    }

    public function removeFileShare(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isRedactor())) {
            $data = $this->getJsonInput();
            $filePath = trim($data['path'] ?? '');
            if (!$filePath) {
                $this->renderJsonBadRequest('Missing path', __FILE__, __LINE__);
                return;
            }
            $response = $this->mediaManager->removeFileShare($filePath);
            $this->renderJson($response, $response['success'], $response['success'] ? ApplicationError::Ok->value : ApplicationError::BadRequest->value);
        }
    }

    public function shareFile(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isRedactor())) {
            $data = $this->getJsonInput();

            $path = $data['path'] ?? null;
            if (!$path) {
                $this->renderJsonBadRequest('Missing path', __FILE__, __LINE__);
                return;
            }
            $parts = explode('/', $path);
            if (count($parts) !== 3) {
                $this->renderJsonBadRequest('Invalid path format', __FILE__, __LINE__);
                return;
            }
            [$year, $month, $filename] = $parts;

            $response = $this->mediaManager->sharefile(
                (int)$year,
                (int)$month,
                $filename,
                WebApp::nullableCast($data['idGroup'] ?? null, 'int'),
                $data['membersOnly']
            );

            $this->renderJson(
                $response,
                $response['success'],
                $response['success'] ? ApplicationError::Ok->value : ApplicationError::BadRequest->value
            );
        }
    }

    public function uploadFile(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isRedactor())) {

            if (empty($_FILES['file'])) {
                $this->renderJson(['message' => 'Aucun fichier sélectionné'], false, ApplicationError::Ok->value);
                return;
            }
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $response = ['message' => "Erreur lors de l'upload: " . $this->getUploadErrorMessage($file['error'])];
                $this->renderJsonOk($response);
                return;
            }
            try {
                $data = $this->mediaManager->uploadFile($file);
                $this->renderJsonOk($data, 'Fichier uploadé avec succès');
            } catch (Throwable $e) {
                $this->renderJson(
                    [],
                    false,
                    ApplicationError::Error->value,
                    $e->getMessage()
                );
            }
        }
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
