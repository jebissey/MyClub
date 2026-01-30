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
    protected LatteEngine $latte;

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

    protected function renderJson(array $data, bool $success, int $statusCode, string $message = ''): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        $response = [
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ];
        echo json_encode($response);
        if (ob_get_level()) ob_end_flush();
        flush();
        Flight::stop();
    }

    protected function renderJsonBadRequest(string $message, string $file, int $line): void
    {
        $this->renderJson(
            [],
            false,
            ApplicationError::BadRequest->value,
            "Bad request: {$message} in file {$file} at line {$line}"
        );
    }

    protected function renderJsonCreated(array $data = [], string $message = 'Created'): void
    {
        $this->renderJson(
            $data,
            true,
            ApplicationError::Created->value,
            $message
        );
    }

    protected function renderJsonError(string $message, int $statusCode, string $file, int $line): void
    {
        $this->application->getFlight()->setData('code', $statusCode);
        $this->application->getFlight()->setData('message', $message);
        $this->renderJson(
            [],
            false,
            $statusCode,
            "{$message} in file {$file} at line {$line}"
        );
    }

    protected function renderJsonForbidden(string $file, int $line): void
    {
        $this->renderJson(
            [],
            false,
            ApplicationError::Forbidden->value,
            "User not allowed in file {$file} at line {$line}"
        );
    }

    protected function renderJsonMethodNotAllowed(string $file, int $line): void
    {
        $this->renderJson(
            [],
            false,
            ApplicationError::MethodNotAllowed->value,
            "Method {$_SERVER['REQUEST_METHOD']} not allowed in file {$file} at line {$line}"
        );
    }

    protected function renderJsonOk(array $data = [], string $message = 'OK'): void
    {
        $this->renderJson(
            $data,
            true,
            ApplicationError::Ok->value,
            $message
        );
    }

    protected function renderPartial(string $template, array $params = []): void
    {
        $this->latte->render($template, $params);
    }
}
