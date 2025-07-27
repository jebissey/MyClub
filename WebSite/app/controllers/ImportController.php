<?php

namespace app\controllers;

use app\helpers\ImportDataHelper;
use app\helpers\SettingsDataHelper;

class ImportController extends BaseController
{
    private $importSettings;
    private $results;
    private array $foundEmails = [];

    public function __construct()
    {
        parent::__construct();
    }

    private function loadSettings()
    {
        if (!$this->importSettings = json_decode((new SettingsDataHelper())->get_('ImportPersonParameters'), true)) {
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

    public function showImportForm()
    {
        if ($this->personDataHelper->getPerson(['PersonManager'])) {
            $this->loadSettings();

            $this->render('app/views/import/form.latte', $this->params->getAll([
                'importSettings' => $this->importSettings,
                'results' => $this->results
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function processImport()
    {
        if ($this->personDataHelper->getPerson(['PersonManager'])) {
            if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) {
                $this->results['errors']++;
                $this->results['messages'][] = 'Veuillez sélectionner un fichier CSV valide';

                $this->render('app/views/import/form.latte', $this->params->getAll([
                    'importSettings' => $this->importSettings,
                    'results' => $this->results
                ]));
            } else {
                $headerRow = $_POST['headerRow'];
                $mapping = [
                    'email' => $_POST['emailColumn'],
                    'firstName' => $_POST['firstNameColumn'],
                    'lastName' => $_POST['lastNameColumn'],
                    'phone' => $_POST['phoneColumn']
                ];
                $this->importSettings['headerRow'] = $headerRow;
                $this->importSettings['mapping'] = $mapping;
                (new SettingsDataHelper())->set_('ImportPersonParameters', json_encode($this->importSettings));

                $persons = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Id, Email');
                $results = (new ImportDataHelper())->getResults($headerRow, $mapping, $this->foundEmails);
                foreach ($persons as $person) {
                    if (!in_array($person->Email, $this->foundEmails)) {
                        $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $person->Id]);
                        $this->results['inactivated']++;
                        $this->results['messages'][] = '(-) ' . $person->Email;
                    }
                }

                $this->render('app/views/import/form.latte', $this->params->getAll([
                    'importSettings' => $this->importSettings,
                    'results' => $results
                ]));
            }
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
