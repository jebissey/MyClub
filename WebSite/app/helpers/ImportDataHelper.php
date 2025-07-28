<?php

namespace app\helpers;

class ImportDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getResults($headerRow, $mapping, $foundEmails)
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'inactivated' => 0,
            'errors' => 0,
            'messages' => []
        ];
        $file = fopen($_FILES['csvFile']['tmp_name'], 'r');
        $currentRow = 0;

        while (($data = fgetcsv($file, 0, ",", "\"", "\\")) !== false) {
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
                $results['errors']++;
                $results['messages'][] = "Adresse email incorrecte ligne $currentRow";
            } else {
                $existingPerson = $this->fluent->from('Person')->select('Id')->where('Email COLLATE NOCASE', $personData['Email'])->fetch();
                if ($existingPerson) {
                    $query = $this->pdo->prepare("UPDATE Person SET Email = ?, FirstName = ?, LastName = ?, phone = ?, Imported = 1 WHERE Id = " . $existingPerson->Id);
                    $results['updated']++;
                } else {
                    $query = $this->pdo->prepare("INSERT INTO Person (Email, FirstName, LastName, phone, Imported) VALUES (?, ?, ?, ?, 1)");
                    $results['created']++;
                    $results['messages'][] = '(+) ' . $personData['Email'];
                }
                array_push($foundEmails, $personData['Email']);
                $query->execute([
                    $personData['Email'],
                    $personData['FirstName'],
                    $personData['LastName'],
                    $personData['Phone'],
                ]);
            }
        }
        fclose($file);
        return $results;
    }
}
