<?php

namespace app\apis;

use flight;
use Latte\Engine as LatteEngine;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\PersonDataHelper;

abstract class AbstractApi
{
    private LatteEngine $latte;

    protected Application $application;
    protected ConnectedUser $connectedUser;
    protected DataHelper $dataHelper;
    protected PersonDataHelper $personDataHelper;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->latte = $application->getLatte();
        $this->connectedUser = new ConnectedUser($application);
        $this->dataHelper = new DataHelper($application);
        $this->personDataHelper = new PersonDataHelper($application);
    }

    protected function renderJson(array $response, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response);
        if (ob_get_level()) ob_end_flush();
        flush();
        Flight::stop();
    }

    protected function renderPartial(string $template, array $params = []): void
    {
        $this->latte->render($template, $params);
    }
}
