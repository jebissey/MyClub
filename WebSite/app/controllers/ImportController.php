<?php

namespace app\controllers;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\ImportDataHelper;

class ImportController extends AbstractController
{
    private $importSettings;
    private $results;
    private array $foundEmails = [];

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function showImportForm()
    {
        if ($this->connectedUser->get()->isPersonManager() ?? false) {
            $this->loadSettings();

            $this->render('app/views/import/form.latte', Params::getAll([
                'importSettings' => $this->importSettings,
                'results' => $this->results
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function processImport()
    {
        if ($this->connectedUser->get()->isPersonManager() ?? false) {
            if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) {
                $this->results['errors']++;
                $this->results['messages'][] = 'Veuillez sélectionner un fichier CSV valide';

                $this->render('app/views/import/form.latte', Params::getAll([
                    'importSettings' => $this->importSettings,
                    'results' => $this->results
                ]));
            } else {
                $schema = [
                    'headerRow' => FilterInputRule::Int->value,
                    'email' => FilterInputRule::Email->value,
                    'firstName' => FilterInputRule::PersonName->value,
                    'lastName' => FilterInputRule::PersonName->value,
                    'phone' => FilterInputRule::Phone->value,
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

                $persons = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Id, Email');
                $results = (new ImportDataHelper($this->application))->getResults($headerRow, $mapping, $this->foundEmails);
                foreach ($persons as $person) {
                    if (!in_array($person->Email, $this->foundEmails)) {
                        $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $person->Id]);
                        $this->results['inactivated']++;
                        $this->results['messages'][] = '(-) ' . $person->Email;
                    }
                }

                $this->render('app/views/import/form.latte', Params::getAll([
                    'importSettings' => $this->importSettings,
                    'results' => $results
                ]));
            }
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
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
