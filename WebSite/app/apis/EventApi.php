<?php

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\enums\Period;
use app\helpers\Application;
use app\helpers\WebApp;
use app\interfaces\AuthorizationServiceInterface;
use app\interfaces\EventServiceInterface;
use app\interfaces\NeedTypeServiceInterface;
use app\models\ApiEventDataHelper;
use app\models\EventDataHelper;

class EventApi extends AbstractApi
{
    private ApiEventDataHelper $apiEventDataHelper;
    private AuthorizationServiceInterface $authService;
    private EventDataHelper $eventDataHelper;
    private EventServiceInterface $eventService;

    public function __construct(
        Application $application,
        AuthorizationServiceInterface $authService,
        EventDataHelper $eventDataHelper,
        EventServiceInterface $eventService,
    ) {
        parent::__construct($application);
        $this->authService = $authService;
        $this->eventDataHelper = $eventDataHelper;
        $this->eventService = $eventService;
    }

    public function deleteEvent(int $id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            [$success, $response, $statusCode] = $this->eventService->deleteEvent($id, $this->authService->getUserId());
            $this->renderJson($response, $success, $statusCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function duplicateEvent($id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            [$success, $response, $statusCode] = $this->eventService->duplicateEvent(
                $id,
                $this->connectedUser->person->Id,
                WebApp::getFiltered('mode', $this->application->enumToValues(Period::class), $_GET) ?: Period::Today->value
            );
            $this->renderJson($response, $success, $statusCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function getEvent($id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $apiResponse = $this->eventService->getEvent($id);
            $this->renderJson([$apiResponse->data], $apiResponse->success,  $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function saveEvent(): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            [$success, $response, $statusCode] = $this->apiEventDataHelper->update($data, $this->authService->getUserId());
            $this->renderJson($response, $success, $statusCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    #region Emails
    public function sendEmails()
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $eventId = $data['EventId'] ?? '';
            $event = $this->eventDataHelper->getEvent($eventId);
            if (!$event) {
                $this->renderJson(['message' => "Unknown event ($eventId)"], false, ApplicationError::Forbidden->value);
                return;
            }
            $apiResponse = $this->eventService->sendEventEmails($event, $data['Title'] ?? '', $data['Body'] ?? '', $data['Recipients'] ?? '');
            $this->renderJson([$apiResponse->data], $apiResponse->success,  $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }
}
