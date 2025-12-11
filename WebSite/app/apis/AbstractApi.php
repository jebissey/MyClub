<?php

declare(strict_types=1);

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

    public function __construct(
        protected Application $application,
        protected ConnectedUser $connectedUser,
        protected DataHelper $dataHelper,
        protected PersonDataHelper $personDataHelper
    ) {
        $this->latte = $application->getLatte();
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

    protected function renderJsonBadRequest(string $message, string $file, int $line): void
    {
        $this->renderJson(
            ['message' => "Bad request: {$message} in file {$file} at line {$line}"],
            false,
            ApplicationError::BadRequest->value
        );
    }

    protected function renderJsonError(string $message, int $statusCode, string $file, int $line): void
    {
        $this->application->getFlight()->setData('code', $statusCode);
        $this->application->getFlight()->setData('message', $message);
        $this->renderJson(
            ['message' => "{$message} in file {$file} at line {$line}"],
            false,
            $statusCode
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

    protected function renderJsonOk(array $data = [], string $message = 'OK'): void
    {
        $this->renderJson(
            [
                'message' => $message,
                ...$data
            ],
            true,
            ApplicationError::Ok->value
        );
    }

    protected function renderPartial(string $template, array $params = []): void
    {
        $this->latte->render($template, $params);
    }
}
