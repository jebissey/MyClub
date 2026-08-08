<?php

declare(strict_types=1);

namespace app\apis;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
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

    public function getHeadersFromCSV(): void
    {
        if (!$this->application->getConnectedUser()->isPersonManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->renderJsonOk($this->doGetHeadersFromCSV(intval($_POST['headerRow'] ?? 1)));
    }

    #region Private functions
    /**
     * @return array{error: string}|array{headers: array<int, string|null>}
     */
    private function doGetHeadersFromCSV(int $headerRow): array
    {
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] != 0) {
            return ['error' => 'Fichier non valide'];
        }
        $headers = [];
        $file = fopen($_FILES['csvFile']['tmp_name'], 'r');
        if ($file === false) {
            return ['error' => 'Impossible d\'ouvrir le fichier'];
        }
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
