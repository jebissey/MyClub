<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;

class ImportController extends AbstractController
{
    /** @var array{headerRow: int, mapping: array{email: int|null, firstName: int|null, lastName: int|null, phone: int|null}} */
    private array $importSettings;

    /** @var array{errors: int, messages: array<int, string>, inactivated: int} */
    private array $results;

    public function __construct(
        Application $application,
        private PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application);
    }

    public function showImportForm(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $this->loadSettings();
            $this->render('PersonManager/views/users_import.latte', $this->getAllParams([
                'importSettings' => $this->importSettings,
                'results' => $this->results,
                'page' => $this->application->getConnectedUser()->getPage(),
                'layout' => $this->getLayout(),
            ]));
        }
    }

    public function processImport(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            return;
        }
        $this->results = array_merge([
            'errors' => 0,
            'messages' => [],
            'inactivated' => 0,
        ], $this->results ?? []);

        $file = $this->getUploadedFile('csvFile');
        if ($file === null || ($file['error'] ?? 1) !== 0) {
            $this->results['errors']++;
            $this->results['messages'][] = 'Veuillez sélectionner un fichier CSV valide';

            $this->render('PersonManager/views/users_import.latte', $this->getAllParams([
                'importSettings' => $this->importSettings,
                'results' => $this->results,
                'page' => $this->application->getConnectedUser()->getPage(),
                'layout' => $this->getLayout(),
            ]));
            return;
        }

        $schema = [
            'headerRow' => FilterInputRule::Int->value,
            'emailColumn' => FilterInputRule::Int->value,
            'firstNameColumn' => FilterInputRule::Int->value,
            'lastNameColumn' => FilterInputRule::Int->value,
            'phoneColumn' => FilterInputRule::Int->value,
        ];
        /** @var array{headerRow: int|null, emailColumn: int|null, firstNameColumn: int|null, lastNameColumn: int|null, phoneColumn: int|null} $input */
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

        $headerRow = $input['headerRow'] ?? 1;
        $mapping = [
            'email' => $input['emailColumn'] ?? 0,
            'firstName' => $input['firstNameColumn'] ?? 0,
            'lastName' => $input['lastNameColumn'] ?? 0,
            'phone' => $input['phoneColumn'] ?? 0,
        ];

        $this->importSettings['headerRow'] = $headerRow;
        $this->importSettings['mapping'] = $mapping;

        $this->dataHelper->set('Settings', ['Value' => json_encode($this->importSettings)], ['Name' => 'ImportPersonParameters']);

        $path = $file['tmp_name'] ?? null;
        if (!is_string($path) || $path === '') {
            $this->results['messages'][] = 'Veuillez sélectionner un fichier CSV valide';

            $this->render('PersonManager/views/users_import.latte', $this->getAllParams([
                'importSettings' => $this->importSettings,
                'results' => $this->results,
                'page' => $this->application->getConnectedUser()->getPage(),
                'layout' => $this->getLayout(),
            ]));
            return;
        }

        $this->render('PersonManager/views/users_import.latte', $this->getAllParams([
            'importSettings' => $this->importSettings,
            'results' => $this->personDataHelper->importFromCsvFile($path, $headerRow, $mapping, $this->personDataHelper->getAllPersons()),
            'page' => $this->application->getConnectedUser()->getPage(),
            'layout' => $this->getLayout(),
        ]));
    }

    #region Private functions
    private function loadSettings(): void
    {
        $row = $this->dataHelper->get('Settings', ['Name' => 'ImportPersonParameters'], 'Value');
        /** @var object{Value: string|null}|false $row */
        $json = ($row !== false && $row->Value !== null) ? $row->Value : null;
        $decoded = $json !== null ? json_decode($json, true) : null;

        $this->importSettings = $this->buildImportSettings(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param array<mixed> $data
     * @return array{headerRow: int, mapping: array{email: int|null, firstName: int|null, lastName: int|null, phone: int|null}}
     */
    private function buildImportSettings(array $data): array
    {
        $headerRow = isset($data['headerRow']) && is_int($data['headerRow']) ? $data['headerRow'] : 1;
        $mappingData = isset($data['mapping']) && is_array($data['mapping']) ? $data['mapping'] : [];

        return [
            'headerRow' => $headerRow,
            'mapping' => [
                'email' => isset($mappingData['email']) && is_int($mappingData['email']) ? $mappingData['email'] : null,
                'firstName' => isset($mappingData['firstName']) && is_int($mappingData['firstName']) ? $mappingData['firstName'] : null,
                'lastName' => isset($mappingData['lastName']) && is_int($mappingData['lastName']) ? $mappingData['lastName'] : null,
                'phone' => isset($mappingData['phone']) && is_int($mappingData['phone']) ? $mappingData['phone'] : null,
            ],
        ];
    }

    /**
     * @return array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int}|null
     */
    private function getUploadedFile(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        if (!is_array($file)) {
            return null;
        }

        $result = [];
        if (isset($file['name']) && is_string($file['name'])) {
            $result['name'] = $file['name'];
        }
        if (isset($file['type']) && is_string($file['type'])) {
            $result['type'] = $file['type'];
        }
        if (isset($file['tmp_name']) && is_string($file['tmp_name'])) {
            $result['tmp_name'] = $file['tmp_name'];
        }
        if (isset($file['error']) && is_int($file['error'])) {
            $result['error'] = $file['error'];
        }
        if (isset($file['size']) && is_int($file['size'])) {
            $result['size'] = $file['size'];
        }

        return $result;
    }
    #endregion
}
