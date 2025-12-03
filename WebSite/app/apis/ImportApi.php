<?php

declare(strict_types=1);

namespace app\apis;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\PersonDataHelper;

class ImportApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function getHeadersFromCSV()
    {
        if (!($this->application->getConnectedUser()->isPersonManager() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->renderJson($this->getHeadersFromCSV_(intval($_POST['headerRow'] ?? 1)), true, ApplicationError::Ok->value);
    }

    #region Private functions
    private function getHeadersFromCSV_(int $headerRow)
    {
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) return ['error' => 'Fichier non valide'];
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
