<?php

declare(strict_types=1);

namespace app\apis;

use flight;
use JsonException;
use Latte\Engine as LatteEngine;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\LogDataWriterHelper;
use app\models\PersonDataHelper;

abstract class AbstractApi
{
    protected LatteEngine $latte;
    private LogDataWriterHelper $logDataWriterHelper;

    public function __construct(
        protected Application $application,
        protected ConnectedUser $connectedUser,
        protected DataHelper $dataHelper,
        protected PersonDataHelper $personDataHelper
    ) {
        $this->latte = $application->getLatte();
        $this->logDataWriterHelper = new LogDataWriterHelper($application);
    }

    protected function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }

    protected function renderJson(array $data, bool $success, int $statusCode, string $message = ''): void
    {
        $response = [
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ];

        try {
            $json = json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            $json = json_encode([
                'success' => false,
                'message' => 'JSON encoding error: ' . $e->getMessage(),
                'data'    => [],
            ], JSON_THROW_ON_ERROR);
        }

        Flight::response()
            ->status($statusCode)
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->write($json)
            ->send();

        $this->logDataWriterHelper->add((string)$statusCode, $message);
        exit;
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

    protected function userIsAllowedAndMethodIsGood(string $method, callable $permissionCheck): bool
    {
        $user = $this->application->getConnectedUser();
        if (!$user || !$permissionCheck($user)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return false;
        }
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return false;
        }
        return true;
    }

    protected function render(string $templateLatteName, object|array $params = []): void
    {
        $content = $this->latte->renderToString($templateLatteName, $params);
        echo $content;
        if (ob_get_level()) ob_end_flush();
        flush();
        Flight::stop();
    }
}
