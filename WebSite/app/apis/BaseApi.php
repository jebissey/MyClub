<?php

namespace app\apis;

use flight;
use Latte\Engine as LatteEngine;

use app\helpers\Application;
use app\helpers\DataHelper;
use app\helpers\PersonDataHelper;

abstract class BaseApi
{
    private LatteEngine $latte;

    protected DataHelper $dataHelper;
    protected PersonDataHelper $personDataHelper;

    public function __construct()
    {
        $this->latte = Application::getLatte();
        $this->dataHelper = new DataHelper();
        $this->personDataHelper = new PersonDataHelper();
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
