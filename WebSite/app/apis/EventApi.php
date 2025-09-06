<?php

namespace app\apis;

use InvalidArgumentException;
use Throwable;

use app\enums\ApplicationError;
use app\enums\Period;
use app\helpers\Application;
use app\helpers\WebApp;
use app\interfaces\AuthorizationServiceInterface;
use app\interfaces\EventServiceInterface;
use app\interfaces\MessageServiceInterface;
use app\interfaces\NeedServiceInterface;
use app\interfaces\NeedTypeServiceInterface;
use app\models\ApiEventDataHelper;
use app\models\EventDataHelper;

class EventApi extends AbstractApi
{
    private ApiEventDataHelper $apiEventDataHelper;
    private AuthorizationServiceInterface $authService;
    private EventDataHelper $eventDataHelper;
    private EventServiceInterface $eventService;
    private MessageServiceInterface $messageService;
    private NeedServiceInterface $needService;
    private NeedTypeServiceInterface $needTypeService;

    public function __construct(
        Application $application,
        AuthorizationServiceInterface $authService,
        EventDataHelper $eventDataHelper,
        EventServiceInterface $eventService,
        MessageServiceInterface $messageService,
        NeedServiceInterface $needService,
        NeedTypeServiceInterface $needTypeService
    ) {
        parent::__construct($application);
        $this->authService = $authService;
        $this->eventDataHelper = $eventDataHelper;
        $this->eventService = $eventService;
        $this->messageService = $messageService;
        $this->needService = $needService;
        $this->needTypeService = $needTypeService;
    }



    #region Event
    public function deleteEvent(int $id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
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
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
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
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
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
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
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

    #region Message
    public function addMessage(): void
    {
        $userId = $this->authService->getUserId();
        if ($userId === 0) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $this->validateMessageData($data);
            $apiResponse = $this->messageService->addMessage($data['eventId'], $userId, $data['text']);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function deleteMessage(): void
    {
        $userId = $this->authService->getUserId();
        if (!$userId) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $apiResponse = $this->messageService->deleteMessage($data['messageId'] ?? 0, $userId);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function updateMessage(): void
    {
        $userId = $this->authService->getUserId();
        if (!$userId) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            if (!isset($data['messageId']) || !isset($data['text'])) throw new InvalidArgumentException('Données manquantes');
            $apiResponse = $this->messageService->updateMessage($data['messageId'], $userId, $data['text']);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    #region Need
    public function deleteNeed(int $id): void
    {
        if (!$this->authService->isEventDesigner()) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $apiResponse = $this->needService->deleteNeed($id);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function getEventNeeds(int $id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $apiResponse = $this->needService->getEventNeeds($id);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function saveNeed(): void
    {
        if (!$this->authService->isEventDesigner()) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = $this->getJsonInput();
        try {
            $this->needService->saveNeed($data);
            $this->renderJson([], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function deleteNeedType(int $id): void
    {
        if (!$this->authService->isEventDesigner()) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $apiResponse = $this->needTypeService->deleteNeedType($id);
            $this->renderJson([], $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function saveNeedType(): void
    {
        if (!$this->authService->isEventDesigner()) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $apiResponse = $this->needTypeService->saveNeedType($data);
            $this->renderJson([$apiResponse->data], $apiResponse->success,  $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function getNeedsByNeedType(int $id): void
    {
        try {
            $apiResponse = $this->needService->getNeedsByNeedType($id);
            $this->renderJson([$apiResponse->data], $apiResponse->success,  $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }


    #region Emails
    public function sendEmails()
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonUnauthorized(__FILE__, __LINE__);
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

    #region Private functions
    private function validateMessageData(array $data): void
    {
        if (!isset($data['eventId']) || !isset($data['text'])) throw new InvalidArgumentException('Données manquantes');
    }

}
