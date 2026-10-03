<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\MemberCustomFields;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;
use app\modules\PersonManager\viewModels\UsersImportViewModel;
use app\modules\PersonManager\valueObjects\CustomFieldDefinition;

/**
 * @phpstan-import-type ImportSettings from UsersImportViewModel
 * @phpstan-import-type ImportResults from UsersImportViewModel
 */
final class ImportController extends AbstractController
{
    /** @var ImportSettings */
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
            $this->renderImportForm($this->results ?? null);
        }
    }

    public function processImport(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            return;
        }
        $this->loadSettings();
        $this->results = array_merge([
            'errors' => 0,
            'messages' => [],
            'inactivated' => 0,
        ], $this->results ?? []);

        $file = $this->getUploadedFile('csvFile');
        if ($file === null || ($file['error'] ?? 1) !== 0) {
            $this->results['errors']++;
            $this->results['messages'][] = ($this->t)('import.error.invalid_file');
            $this->renderImportForm($this->results);
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

        $postData = $this->flight->request()->data->getData();
        $rawColumns = is_array($postData['customColumn'] ?? null) ? $postData['customColumn'] : [];
        $customMapping = [];
        foreach ($this->loadCustomDefinitions() as $definition) {
            $column = $rawColumns[$definition->key] ?? '';
            if (is_string($column) && ctype_digit($column)) {
                $customMapping[$definition->key] = (int)$column;
            }
        }

        $headerRow = $input['headerRow'] ?? 1;
        $mapping = [
            'email' => $input['emailColumn'] ?? 0,
            'firstName' => $input['firstNameColumn'] ?? 0,
            'lastName' => $input['lastNameColumn'] ?? 0,
            'phone' => $input['phoneColumn'] ?? 0,
            'custom' => $customMapping,
        ];

        $this->importSettings['headerRow'] = $headerRow;
        $this->importSettings['mapping'] = $mapping;

        $this->dataHelper->set('Settings', ['Value' => json_encode($this->importSettings)], ['Name' => 'ImportPersonParameters']);

        $path = $file['tmp_name'] ?? null;
        if (!is_string($path) || $path === '') {
            $this->results['messages'][] = ($this->t)('import.error.invalid_file');
            $this->renderImportForm($this->results);
            return;
        }

        $this->renderImportForm(
            $this->personDataHelper->importFromCsvFile(
                $path,
                $headerRow,
                $mapping,
                $this->personDataHelper->getAllPersons(),
                $this->t
            )
        );
    }

    #region Private functions
    /** @param ImportResults|null $results */
    private function renderImportForm(?array $results): void
    {
        $viewModel = new UsersImportViewModel(
            importSettings: $this->importSettings,
            results: $results,
            layout: $this->getLayout(),
            customFields: $this->customFieldsForView(),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('PersonManager/views/users_import.latte', $viewModel->toArray());
    }

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
     * @return ImportSettings
     */
    private function buildImportSettings(array $data): array
    {
        $headerRow = isset($data['headerRow']) && is_int($data['headerRow']) ? $data['headerRow'] : 1;
        $mappingData = isset($data['mapping']) && is_array($data['mapping']) ? $data['mapping'] : [];
        $customData = isset($mappingData['custom']) && is_array($mappingData['custom']) ? $mappingData['custom'] : [];

        $custom = [];
        foreach ($customData as $key => $index) {
            if (is_int($index)) {
                $custom[(string)$key] = $index;
            }
        }

        return [
            'headerRow' => $headerRow,
            'mapping' => [
                'email' => isset($mappingData['email']) && is_int($mappingData['email']) ? $mappingData['email'] : null,
                'firstName' => isset($mappingData['firstName']) && is_int($mappingData['firstName']) ? $mappingData['firstName'] : null,
                'lastName' => isset($mappingData['lastName']) && is_int($mappingData['lastName']) ? $mappingData['lastName'] : null,
                'phone' => isset($mappingData['phone']) && is_int($mappingData['phone']) ? $mappingData['phone'] : null,
                'custom' => $custom,
            ],
        ];
    }

    /** @return list<CustomFieldDefinition> */
    private function loadCustomDefinitions(): array
    {
        return MemberCustomFields::parseDefinitions(
            $this->dataHelper->getSetting(MemberCustomFields::SETTING_KEY, '[]')
        );
    }

    /** @return list<array{key: string, label: string}> */
    private function customFieldsForView(): array
    {
        return array_map(
            static fn(CustomFieldDefinition $d) => ['key' => $d->key, 'label' => $d->label],
            $this->loadCustomDefinitions()
        );
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
