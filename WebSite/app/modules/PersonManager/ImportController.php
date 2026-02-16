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
    private $importSettings;
    private $results;
    private array $foundEmails = [];

    public function __construct(
        Application $application,
        private PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application);
    }

    public function showImportForm(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager())) {
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
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager())) {
            return;
        }
        $this->results = array_merge([
            'errors' => 0,
            'messages' => [],
            'inactivated' => 0,
        ], $this->results ?? []);

        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) {
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
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

        $headerRow = $input['headerRow'] ?? 1;
        $mapping = [
            'email' => $input['emailColumn'] ?? '',
            'firstName' => $input['firstNameColumn'] ?? '',
            'lastName' => $input['lastNameColumn'] ?? '',
            'phone' => $input['phoneColumn'] ?? ''
        ];

        $this->importSettings['headerRow'] = $headerRow;
        $this->importSettings['mapping'] = $mapping;

        $this->dataHelper->set('Settings', ['Value' => json_encode($this->importSettings)], ['Name' => 'ImportPersonParameters']);

        $path = $_FILES['csvFile']['tmp_name'] ?? null;
        if ($path === null) {
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
    private function loadSettings()
    {
        if (!$this->importSettings = json_decode($this->dataHelper->get('Settings', ['Name' => 'ImportPersonParameters'], 'Value')->Value ?? '', true)) {
            $this->importSettings = [
                'headerRow' => 1,
                'mapping' => [
                    'email' => null,
                    'firstName' => null,
                    'lastName' => null,
                    'phone' => null
                ]
            ];
        }
    }
}
