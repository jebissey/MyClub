<?php

namespace app\helpers;

class ApiImportHelper extends Data
{
    public function getHeadersFromCSV()
    {
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) return ['error' => 'Fichier non valide'];

        $headerRow = intval($_POST['headerRow']);
        $headers = [];

        $file = fopen($_FILES['csvFile']['tmp_name'], 'r');
        $currentRow = 0;

        while (($data = fgetcsv($file, 0, ",", "\"", "\\")) !== false && $currentRow <= $headerRow) {
            $currentRow++;
            if ($currentRow == $headerRow) {
                $headers = $data;
                break;
            }
        }
        fclose($file);

        return ['headers' => $headers];
    }
}
