<?php

namespace app\controllers;

use PDO;
use flight\Engine;

class ImportController extends BaseController
{
    private $importSettings;
    private $settingsFile = 'var/tmp/import_settings.json';
    private $results;
    private array $foundEmails = [];

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->loadSettings();
    }

    private function loadSettings()
    {
        if (file_exists($this->settingsFile)) {
            $this->importSettings = json_decode(file_get_contents($this->settingsFile), true);
        } else {
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

    private function saveSettings()
    {
        file_put_contents($this->settingsFile, json_encode($this->importSettings));
    }

    public function showImportForm()
    {
        if ($this->getPerson(['PersonManager'])) {
            $this->flight->set('importSettings', $this->importSettings);
            echo $this->latte->render('app/views/import/form.latte', $this->params->getAll([
                'importSettings' => $this->importSettings,
                'results' => $this->results
            ]));
        }
    }

    public function processImport()
    {
        if ($this->getPerson(['PersonManager'])) {
            if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) {
                $this->results['errors']++;
                $this->results['messages'][] = 'Veuillez sélectionner un fichier CSV valide';

                echo $this->latte->render('app/views/import/form.latte', $this->params->getAll([
                    'importSettings' => $this->importSettings,
                    'results' => $this->results
                ]));
            } else {
                $this->results = [
                    'created' => 0,
                    'updated' => 0,
                    'inactivated' => 0,
                    'errors' => 0,
                    'messages' => []
                ];
                $headerRow = $_POST['headerRow'];
                $mapping = [
                    'email' => $_POST['emailColumn'],
                    'firstName' => $_POST['firstNameColumn'],
                    'lastName' => $_POST['lastNameColumn'],
                    'phone' => $_POST['phoneColumn']
                ];
                $this->importSettings['headerRow'] = $headerRow;
                $this->importSettings['mapping'] = $mapping;
                $this->saveSettings();

                $file = fopen($_FILES['csvFile']['tmp_name'], 'r');
                $currentRow = 0;

                while (($data = fgetcsv($file)) !== false) {
                    $currentRow++;
                    if ($currentRow <= $headerRow) continue;

                    $personData = [
                        'Email' => filter_var($data[$mapping['email']], FILTER_VALIDATE_EMAIL) ?? '',
                        'FirstName' => $data[$mapping['firstName']],
                        'LastName' => $data[$mapping['lastName']],
                        'Phone' => $data[$mapping['phone']],
                        'Imported' => 1
                    ];
                    if ($personData['Email'] == '') {
                        $this->results['errors']++;
                        $this->results['messages'][] = "Adresse email incorrecte ligne $currentRow";
                    } else {
                        $query = $this->pdo->prepare("SELECT Id FROM Person WHERE Email = ?");
                        $query->execute([$personData['Email']]);
                        $existingPerson = $query->fetch(PDO::FETCH_ASSOC);
                        if ($existingPerson) {
                            $query = $this->pdo->prepare("UPDATE Person SET Email = ?, FirstName = ?, LastName = ?, phone = ?, Imported = 1 WHERE Id = " . $existingPerson['Id']);
                            $this->results['updated']++;
                        } else {
                            $query = $this->pdo->prepare("INSERT INTO Person (Email, FirstName, LastName, phone, Imported) VALUES (?, ?, ?, ?, 1)");
                            $this->results['created']++;
                        }
                        array_push($this->foundEmails, $personData['Email']);
                        $query->execute([
                            $personData['Email'],
                            $personData['FirstName'],
                            $personData['LastName'],
                            $personData['Phone'],
                        ]);
                    }
                }
                fclose($file);
                $persons = $this->fluent->from('Person')->where('Inactivated', 0)->fetchAll('Id', 'Email');
                foreach ($persons as $person) {
                    if (!in_array($person['Email'], $this->foundEmails)) {
                        $this->fluent->update('Person', ['Inactivated' => 1], $person['Id'])->execute();
                        $this->results['inactivated']++;
                    }
                }

                echo $this->latte->render('app/views/import/form.latte', $this->params->getAll([
                    'importSettings' => $this->importSettings,
                    'results' => $this->results
                ]));
            }
        }
    }

    public function getHeadersFromCSV()
    {
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) {
            echo json_encode(['error' => 'Fichier non valide']);
            return;
        }

        $headerRow = intval($_POST['headerRow']);
        $headers = [];

        $file = fopen($_FILES['csvFile']['tmp_name'], 'r');
        $currentRow = 0;

        while (($data = fgetcsv($file)) !== false && $currentRow <= $headerRow) {
            $currentRow++;
            if ($currentRow == $headerRow) {
                $headers = $data;
                break;
            }
        }
        fclose($file);

        echo json_encode(['headers' => $headers]);
    }
}
