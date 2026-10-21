<?php

declare(strict_types=1);

namespace app\apis;

use RuntimeException;
use Throwable;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\MemberCustomFields;
use app\helpers\To;
use app\models\LanguagesDataHelper;
use app\models\PersonDataHelper;
use app\modules\PersonManager\services\MemberOdsImporter;

final class ImportApi extends AbstractApi
{
    private const MAX_ODS_BYTES = 10 * 1024 * 1024;

    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        PersonDataHelper $personDataHelper,
        LanguagesDataHelper $languagesDataHelper,
        private readonly MemberOdsImporter $memberOdsImporter,
        private readonly MemberCustomFields $memberCustomFields
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function getHeadersFromCSV(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            return;
        }

        $this->renderJsonOk(
            $this->doGetHeadersFromCSV(
                To::int($_POST['headerRow'] ?? 1)
            )
        );
    }

    /**
     * POST /api/import/ods (fields: odsFile, dryRun)
     * Responds with {success, message?, data?} as expected by exportForm.js.
     */
    public function importOds(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            return;
        }

        $path = $this->uploadedOdsPath();
        if ($path === null) {
            $this->renderJsonBadRequest(($this->t)('import.error.invalid_file'), __FILE__, __LINE__);
            return;
        }

        $dryRun = $this->getFormString('dryRun', '0') === '1';

        try {
            $data = $this->memberOdsImporter->import(
                $path,
                $this->memberCustomFields->loadCustomDefinitions(),
                $this->t,
                dryRun: $dryRun
            );
            $this->renderJsonOk($data);
        } catch (RuntimeException $e) {
            // Unreadable or invalid file (not an ODS, too many rows, ...)
            $this->renderJson([], false, ApplicationError::UnprocessableEntity->value, ($this->t)('export.import.error.wrong_type'));
        } catch (Throwable $e) {
            $this->renderJsonError(($this->t)('export.import.error.generic'), __FILE__, __LINE__);
        }
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

    /** Returns the temporary path of a valid uploaded .ods file, or null. */
    private function uploadedOdsPath(): ?string
    {
        $file = $_FILES['odsFile'] ?? null;
        if (!is_array($file)) {
            return null;
        }

        $name = $file['name'] ?? null;
        $tmpName = $file['tmp_name'] ?? null;
        $error = $file['error'] ?? null;
        $size = $file['size'] ?? null;

        if (
            !is_string($name) || !is_string($tmpName) || $tmpName === ''
            || $error !== UPLOAD_ERR_OK
            || !is_int($size) || $size > self::MAX_ODS_BYTES
            || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'ods'
        ) {
            return null;
        }

        return $tmpName;
    }
    #endregion
}
