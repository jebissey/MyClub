<?php

declare(strict_types=1);

namespace app\apis;

use RuntimeException;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\LanguagesDataHelper;
use app\models\PersonDataHelper;

final class ImportApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        PersonDataHelper $personDataHelper,
        LanguagesDataHelper $languagesDataHelper
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function getHeadersFromCSV(): void
    {
        if (!$this->application->getConnectedUser()->isPersonManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $this->renderJsonOk(
            $this->doGetHeadersFromCSV(
                To::int($_POST['headerRow'] ?? 1)
            )
        );
    }

    public function importOds(): void
    {
        if (
            !$this->userIsAllowedAndMethodIsGood(
                'POST',
                fn($u) => $u->isPersonManager(),
                __FILE__,
                __LINE__
            )
        ) {
            return;
        }

        $file = $_FILES['odsFile'] ?? null;

        $tmpName = is_array($file) && is_string($file['tmp_name'] ?? null)
            ? $file['tmp_name']
            : '';

        $fileName = is_array($file) && is_string($file['name'] ?? null)
            ? $file['name']
            : '';

        $uploadError = is_array($file) && is_int($file['error'] ?? null)
            ? $file['error']
            : UPLOAD_ERR_NO_FILE;

        if (
            $uploadError !== UPLOAD_ERR_OK
            || $tmpName === ''
            || !is_uploaded_file($tmpName)
        ) {
            $this->renderJsonBadRequest(
                ($this->t)('export.import.error.no_file'),
                __FILE__,
                __LINE__
            );
            return;
        }

        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'ods') {
            $this->renderJsonBadRequest(
                ($this->t)('export.import.error.wrong_type'),
                __FILE__,
                __LINE__
            );
            return;
        }

        $dryRun = (
            $this->application->getFlight()->request()->data->getData()['dryRun'] ?? ''
        ) === '1';

        try {
            $results = $this->personDataHelper->importFromOdsFile(
                $tmpName,
                $this->t,
                $dryRun
            );
        } catch (RuntimeException $e) {
            error_log('ODS import: ' . $e->getMessage());

            $this->renderJsonError(
                ($this->t)('export.import.error.invalid_file'),
                ApplicationError::UnprocessableEntity->value,
                $e->getFile(),
                $e->getLine()
            );
            return;
        }

        $this->renderJsonOk($results);
    }

    #region Private functions

    /**
     * @return array{error: string}|array{headers: array<int, string|null>}
     */
    private function doGetHeadersFromCSV(int $headerRow): array
    {
        /** @var array{name: string, type: string, tmp_name: string, error: int, size: int}|null $csvFile */
        $csvFile = $_FILES['csvFile'] ?? null;

        if ($csvFile === null || $csvFile['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Fichier non valide'];
        }

        $headers = [];

        $file = fopen($csvFile['tmp_name'], 'r');

        if ($file === false) {
            return ['error' => 'Impossible d\'ouvrir le fichier'];
        }

        $currentRow = 0;

        while (
            ($data = fgetcsv($file, 0, ',', '"', '\\')) !== false
            && $currentRow <= $headerRow
        ) {
            $currentRow++;

            if ($currentRow === $headerRow) {
                $headers = $data;
                break;
            }
        }

        fclose($file);

        return ['headers' => $headers];
    }

    #endregion
}
