<?php

declare(strict_types=1);

namespace app\apis;

use Closure;
use Flight;
use JsonException;
use Latte\Engine as LatteEngine;
use flight\net\Response;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\RequestInput;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\LogWriterDataHelper;
use app\models\PersonDataHelper;

abstract class AbstractApi
{
    protected LatteEngine $latte;
    protected DataHelper $dataHelper;
    protected readonly RequestInput $input;
    private readonly LogWriterDataHelper $logWriterDataHelper;

    /** @var Closure(string): string */
    protected Closure $t;

    public function __construct(
        protected Application $application,
        protected ConnectedUser $connectedUser,
        protected PersonDataHelper $personDataHelper,
        protected LanguagesDataHelper $languagesDataHelper,
    ) {
        $this->dataHelper = new DataHelper(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
        $this->latte = $application->getLatte();
        $this->input = $application->getRequestInput();
        $this->logWriterDataHelper = new LogWriterDataHelper($application, $application->getErrorManager());
        $this->t = fn(string $key): string => $this->languagesDataHelper->translate($key);
    }

    /**
     * Form fields of a request sent as application/x-www-form-urlencoded or multipart/form-data
     * (e.g. FormData with a file upload). Counterpart of getJsonInput() for non-JSON bodies.
     *
     * @return array<string, mixed>
     */
    protected function getFormInput(): array
    {
        return $this->input->form();
    }

    protected function getFormString(string $key, string $default = ''): string
    {
        return $this->input->formString($key, $default);
    }

    protected function getFormInt(string $key, int $default = 0): int
    {
        return To::int($this->input->form()[$key] ?? null, $default);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException If the body is not valid JSON or is not an object/array
     */
    protected function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');

        $decoded = json_decode($raw === false ? '' : $raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new JsonException('JSON body must be an object or an array');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
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

        $this->logWriterDataHelper->add((string) $statusCode, $message);
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

    protected function renderJsonError(string $message, string $file, int $line): void
    {
        Flight::set('code', ApplicationError::Error->value);
        Flight::set('message', $message);

        $this->renderJson(
            [],
            false,
            ApplicationError::Error->value,
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
        $method = WebApp::getRequestMethod();

        $this->renderJson(
            [],
            false,
            ApplicationError::MethodNotAllowed->value,
            "Method {$method} not allowed in file {$file} at line {$line}"
        );
    }

    /**
     * @param array<int|string, mixed> $data
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

    /**
     * @param callable(ConnectedUser): bool $permissionCheck
     */
    protected function userIsAllowedAndMethodIsGood(
        string $method,
        callable $permissionCheck,
        string $file,
        int $line
    ): bool {
        if (WebApp::getRequestMethod() !== $method) {
            $this->renderJsonMethodNotAllowed($file, $line);
            return false;
        }

        if (!$permissionCheck($this->application->getConnectedUser())) {
            $this->renderJsonForbidden($file, $line);
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
