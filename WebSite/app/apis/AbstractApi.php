<?php

declare(strict_types=1);

namespace app\apis;

use Flight;
use JsonException;
use Latte\Engine as LatteEngine;
use flight\net\Response;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
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

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    protected function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false) {
            $raw = '';
        }

        /** @var array<string, mixed> */
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    protected function renderJson(array $data, bool $success, int $statusCode, string $message = ''): void
    {
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];

        try {
            $json = json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            $json = json_encode([
                'success' => false,
                'message' => 'JSON encoding error: ' . $e->getMessage(),
                'data' => [],
            ], JSON_THROW_ON_ERROR);
        }

        /** @var Response $flightResponse */
        $flightResponse = Flight::response();

        $flightResponse->status($statusCode);
        $flightResponse->header('Content-Type', 'application/json; charset=utf-8');
        $flightResponse->write($json);
        $flightResponse->send();

        $this->logDataWriterHelper->add((string) $statusCode, $message);
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

    /**
     * @param array<string, mixed> $data
     */
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
        Flight::set('code', $statusCode);
        Flight::set('message', $message);

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
            "Method {WebApp::getRequestMethod()} not allowed in file {$file} at line {$line}"
        );
    }

    /**
     * @param array<string, mixed>|array<int, array<string, mixed>>|array<int, mixed> $data
     */
    protected function renderJsonOk(array $data = [], string $message = 'OK'): void
    {
        $this->renderJson(
            $data,
            true,
            ApplicationError::Ok->value,
            $message
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function renderPartial(string $template, array $params = []): void
    {
        $this->latte->render($template, $params);
    }

    protected function userIsAllowedAndMethodIsGood(
        string $method,
        callable $permissionCheck,
        string $file,
        int $line
    ): bool {
        $user = $this->application->getConnectedUser();

        if ($user->person === null || !$permissionCheck($user)) {
            $this->renderJsonForbidden($file, $line);
            return false;
        }

        if (WebApp::getRequestMethod() !== $method) {
            $this->renderJsonMethodNotAllowed($file, $line);
            return false;
        }

        return true;
    }

    /**
     * @param object|array<string, mixed> $params
     */
    protected function render(string $templateLatteName, object|array $params = []): void
    {
        $content = $this->latte->renderToString($templateLatteName, $params);

        echo $content;

        if (ob_get_level() > 0) {
            ob_end_flush();
        }

        flush();
        Flight::stop();
    }
}
