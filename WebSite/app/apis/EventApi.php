<?php

namespace app\apis;

use InvalidArgumentException;
use Throwable;

use app\enums\ApplicationError;
use app\enums\Period;
use app\helpers\Application;
use app\helpers\WebApp;
use app\interfaces\AttributeServiceInterface;
use app\interfaces\AuthorizationServiceInterface;
use app\interfaces\EventServiceInterface;
use app\interfaces\MessageServiceInterface;
use app\interfaces\NeedServiceInterface;
use app\interfaces\NeedTypeServiceInterface;
use app\interfaces\SupplyServiceInterface;
use app\models\ApiEventDataHelper;
use app\models\EventDataHelper;

class EventApi extends AbstractApi
{
    private ApiEventDataHelper $apiEventDataHelper;
    private AuthorizationServiceInterface $authService;
    private AttributeServiceInterface $attributeService;
    private EventDataHelper $eventDataHelper;
    private EventServiceInterface $eventService;
    private MessageServiceInterface $messageService;
    private NeedServiceInterface $needService;
    private NeedTypeServiceInterface $needTypeService;
    private SupplyServiceInterface $supplyService;

    public function __construct(
        ApiEventDataHelper $apiEventDataHelper,
        Application $application,
        AuthorizationServiceInterface $authService,
        AttributeServiceInterface $attributeService,
        EventDataHelper $eventDataHelper,
        EventServiceInterface $eventService,
        MessageServiceInterface $messageService,
        NeedServiceInterface $needService,
        NeedTypeServiceInterface $needTypeService,
        SupplyServiceInterface $supplyService,
    ) {
        parent::__construct($application);
        $this->apiEventDataHelper = $apiEventDataHelper;
        $this->authService = $authService;
        $this->attributeService = $attributeService;
        $this->eventDataHelper = $eventDataHelper;
        $this->eventService = $eventService;
        $this->messageService = $messageService;
        $this->needService = $needService;
        $this->needTypeService = $needTypeService;
        $this->supplyService = $supplyService;
    }

    // region Attribute
    public function createAttribute(): void
    {
        if (!$this->authService->isWebmaster()) {
            $this->renderUnauthorized();
            return;
        }
        try {
            $data = $this->getJsonInput();
            [$response, $statusCode] = $this->attributeService->createAttribute($data);
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function deleteAttribute(int $id): void
    {
        if (!$this->authService->isWebmaster()) {
            $this->renderUnauthorized();
            return;
        }

        try {
            [$response, $statusCode] = $this->attributeService->deleteAttribute($id);
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function getAttributesByEventType(int $eventTypeId): void
    {
        if ($eventTypeId <= 0) {
            $this->renderJson(['success' => false, 'message' => 'Unknown event type'], ApplicationError::BadRequest->value);
            return;
        }
        try {
            $this->renderJson(['success' => true, 'attributes' => $this->attributeService->getAttributesByEventType($eventTypeId)]);
        } catch (Throwable $e) {
            $this->renderJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateAttribute(): void
    {
        if (!$this->authService->isWebmaster()) {
            $this->renderUnauthorized();
            return;
        }

        try {
            $data = $this->getJsonInput();
            [$response, $statusCode] = $this->attributeService->updateAttribute($data);
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    #region Event
    public function deleteEvent(int $id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderUnauthorized();
            return;
        }
        try {
            [$response, $statusCode] = $this->eventService->deleteEvent($id, $this->authService->getUserId());
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function duplicateEvent($id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderUnauthorized();
            return;
        }
        try {
            [$response, $statusCode] = $this->eventService->duplicateEvent(
                $id,
                $this->connectedUser->person->Id,
                WebApp::getFiltered('mode', $this->application->enumToValues(Period::class), $_GET) ?: Period::Today->value
            );
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function getEvent($id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderUnauthorized();
            return;
        }
        try {
            $this->renderJson($this->eventService->getEvent($id));
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function saveEvent(): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderUnauthorized();
            return;
        }
        try {
            $data = $this->getJsonInput();
            [$response, $statusCode] = $this->apiEventDataHelper->update($data, $this->authService->getUserId());
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    #region Message
    public function addMessage(): void
    {
        $userId = $this->authService->getUserId();
        if (!$userId) {
            $this->renderUnauthorized();
            return;
        }
        try {
            $data = $this->getJsonInput();
            $this->validateMessageData($data);

            $response = $this->messageService->addMessage($data['eventId'], $userId, $data['text']);
            $this->renderJson($response);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function updateMessage(): void
    {
        $userId = $this->authService->getUserId();
        if (!$userId) {
            $this->renderUnauthorized();
            return;
        }
        try {
            $data = $this->getJsonInput();
            if (!isset($data['messageId']) || !isset($data['text'])) {
                throw new InvalidArgumentException('Données manquantes');
            }
            $response = $this->messageService->updateMessage($data['messageId'], $userId, $data['text']);
            $this->renderJson($response);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    #region Need

    public function deleteNeed(int $id): void
    {
        if (!$this->authService->isWebmaster()) {
            $this->renderUnauthorized();
            return;
        }
        try {
            [$response, $statusCode] = $this->needService->deleteNeed($id);
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function getEventNeeds(int $id)
    {
        try {
            $this->renderJson($this->needService->getEventNeeds($id), ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function saveNeed(): void
    {
        if (!$this->authService->isWebmaster()) {
            $this->renderUnauthorized();
            return;
        }
        $data = $this->getJsonInput();
        try {
            [$response, $statusCode] = $this->needService->saveNeed($data);
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function deleteNeedType(int $id): void
    {
        if (!$this->authService->isWebmaster()) {
            $this->renderUnauthorized();
            return;
        }
        try {
            [$response, $statusCode] = $this->needTypeService->deleteNeedType($id);
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    public function saveNeedType()
    {
        if (!$this->authService->isWebmaster()) {
            $this->renderUnauthorized();
            return;
        }
        try {
            $data = $this->getJsonInput();
            [$response, $statusCode] = $this->needTypeService->saveNeedType($data);
            $this->renderJson($response, $statusCode);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    #region Supply
    public function updateSupply(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJson(['success' => false, 'message' => 'Not allowed method: ' . $_SERVER['REQUEST_METHOD'] . ' in file ' . __FILE__ . ' at line ' . __LINE__], ApplicationError::Forbidden->value);
            return;
        }
        $userEmail = $this->authService->getUserEmail();
        if (empty($userEmail)) {
            $this->renderJson(['success' => false, 'message' => 'Non authentifié'], ApplicationError::Unauthorized->value);
            return;
        }
        try {
            $input = $this->getJsonInput();
            $this->validateSupplyData($input);
            $response = $this->supplyService->updateSupply(
                $input['eventId'],
                $userEmail,
                $input['needId'],
                intval($input['supply'])
            );
            $this->renderJson($response);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    #region Emails
    public function sendEmails()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJson(['success' => false, 'message' => 'Method ' + $_SERVER['REQUEST_METHOD'] + ' invalid in file ' + __FILE__ + ' at line ' + __LINE__], ApplicationError::Forbidden->value);
            return;
        }
        if (!$this->authService->isEventManager()) {
            $this->renderUnauthorized();
            return;
        }

        try {
            $data = $this->getJsonInput();
            $eventId = $data['EventId'] ?? '';
            $event = $this->eventDataHelper->getEvent($eventId);
            if (!$event) {
                $this->renderJson(['success' => false, 'message' => "Unknown event ($eventId)"], ApplicationError::Forbidden->value);
                return;
            }
            [$message, $code] = $this->eventService->sendEventEmails($event, $data['Title'] ?? '', $data['Body'] ?? '', $data['Recipients'] ?? '');
            $this->renderJson($message, $code);
        } catch (Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    #region Private functions
    private function validateMessageData(array $data): void
    {
        if (!isset($data['eventId']) || !isset($data['text'])) {
            throw new InvalidArgumentException('Données manquantes');
        }
    }

    private function validateSupplyData(array $data): void
    {
        $eventId = $data['eventId'] ?? null;
        $needId = $data['needId'] ?? null;
        $supply = intval($data['supply'] ?? 0);

        if (!$eventId || !$needId || $supply < 0) {
            throw new InvalidArgumentException("Invalid parameters");
        }
    }
}
