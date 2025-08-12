<?php

namespace app\apis;

use flight;
use Latte\Engine as LatteEngine;

use app\enums\ApplicationError;
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

    protected function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }

    protected function renderError(string $message): void
    {
        $this->renderJson(['success' => false, 'message' => $message], ApplicationError::Error->value);
    }

    protected function renderJson(array $response, int $statusCode = ApplicationError::Ok->value): void
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

    protected function renderUnauthorized(): void
    {
        $this->renderJson(['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value);
    }
}
