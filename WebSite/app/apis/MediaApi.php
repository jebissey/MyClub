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
            $result = $this->mediaManager->deleteFile($year, $month, $filename);

            if ($result['success']) {
                $this->renderJsonOk($result);
            } else {
                $this->renderJsonError(
                    $result['message'],
                    ApplicationError::Error->value,
                    $result['file'],
                    $result['line']
                );
            }
        }
    }

    public function editImage(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isRedactor())) {
            $data = $this->getJsonInput();
            $path      = $data['path']      ?? '';
            $imageData = $data['imageData'] ?? '';  // data:image/jpeg;base64,...
            $maxSize   = min((int)($data['maxSize'] ?? 1200), 1200);

            if (!preg_match('#^[\w/-]+\.(jpg|jpeg|png|gif)$#i', $path)) {
                $this->renderJsonError(
                    'Invalid path',
                    ApplicationError::Error->value,
                    __FILE__,
                    __LINE__
                );
                return;
            }

            $fullPath = MediaManager::GetMediaPath() . '/' . $path;
            if (!file_exists($fullPath)) {
                $this->renderJsonError(
                    'File not found',
                    ApplicationError::Error->value,
                    __FILE__,
                    __LINE__
                );
                return;
            }

            if (!preg_match('#^data:image/(\w+);base64,#', $imageData, $m)) {
                $this->renderJsonError(
                    'Invalid image data',
                    ApplicationError::Error->value,
                    __FILE__,
                    __LINE__
                );
                return;
            }
            $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $imageData));
            $img    = imagecreatefromstring($binary);
            if (!$img) {
                $this->renderJsonError(
                    'Cannot decode image',
                    ApplicationError::Error->value,
                    __FILE__,
                    __LINE__
                );
                return;
            }

            // Sécurité : redimensionner côté serveur si nécessaire
            $w = imagesx($img);
            $h = imagesy($img);
            if ($w > $maxSize || $h > $maxSize) {
                $ratio  = min($maxSize / $w, $maxSize / $h);
                $newW   = (int)round($w * $ratio);
                $newH   = (int)round($h * $ratio);
                $resized = imagecreatetruecolor($newW, $newH);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                unset($img);
                $img = $resized;
            }

            // Sauvegarder en écrasant l'original
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            match ($ext) {
                'png'         => imagepng($img, $fullPath, 8),
                'gif'         => imagegif($img, $fullPath),
                default       => imagejpeg($img, $fullPath, 92),
            };
            unset($img);

            $this->renderJsonOk();
        }
    }

    public function isShared(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor())) {
            $filePath = trim($_GET['path']  ?? '');
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
            $this->renderJson(
                $response,
                $response['success'],
                $response['success'] ? ApplicationError::Ok->value : ApplicationError::BadRequest->value
            );
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
                true,
                ApplicationError::Ok->value
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
            $raw = $_FILES['file'];
            if (is_array($raw['name'])) {
                $files = array_map(fn($i) => [
                    'name'     => $raw['name'][$i],
                    'tmp_name' => $raw['tmp_name'][$i],
                    'error'    => $raw['error'][$i],
                    'size'     => $raw['size'][$i],
                    'type'     => $raw['type'][$i],
                ], array_keys($raw['name']));
            } else {
                $files = [$raw];
            }

            foreach ($files as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $this->renderJsonOk(['message' => "Erreur lors de l'upload: " . $this->getUploadErrorMessage($file['error'])]);
                    return;
                }
            }

            try {
                $uploaded = array_map(fn($file) => $this->mediaManager->uploadFile($file), $files);
                $this->renderJsonOk(['files' => array_column($uploaded, 'file')], 'Fichier(s) uploadé(s) avec succès');
            } catch (Throwable $e) {
                $this->renderJson([], false, ApplicationError::Error->value, $e->getMessage());
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
