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

    protected ConnectedUser $connectedUser;
    protected DataHelper $dataHelper;
    protected PersonDataHelper $personDataHelper;

    public function __construct(protected Application $application)
    {
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

    protected function renderJson(array $data, bool $success, int $statusCode): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        $response = array_merge(['success' => $success], $data);
        echo json_encode($response);
        if (ob_get_level()) ob_end_flush();
        flush();
        Flight::stop();
    }

    protected function renderJsonError(string $message, int $statusCode): void
    {
        $this->renderJson(
            ['error' => $message],
            false,
            $statusCode
        );
    }

    protected function renderPartial(string $template, array $params = []): void
    {
        $this->latte->render($template, $params);
    }

    protected function renderJsonBadRequest(string $message, string $file, int $line): void
    {
        $this->renderJson(
            ['message' => "Bad request: {$message} in file {$file} at line {$line}"],
            false,
            ApplicationError::BadRequest->value
        );
    }

    protected function renderJsonForbidden(string $file, int $line): void
    {
        $this->renderJson(
            ['message' => "User not allowed in file {$file} at line {$line}"],
            false,
            ApplicationError::Forbidden->value
        );
    }

    protected function renderJsonMethodNotAllowed(string $file, int $line)
    {
        $this->renderJson(
            ['message' => "Method {$_SERVER['REQUEST_METHOD']} not allowed in file {$file} at line {$line}"],
            false,
            ApplicationError::MethodNotAllowed->value,
        );
    }
}
