<?php

declare(strict_types=1);

namespace app\apis;

use PDOException;
use Throwable;

use app\enums\ApplicationError;
use app\exceptions\UnauthorizedAccessException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\valueObjects\ApiResponse;

class EventMessageApi extends AbstractApi
{
    public function __construct(Application $application, private MessageDataHelper $messageDataHelper, ConnectedUser $connectedUser, DataHelper $dataHelper, PersonDataHelper $personDataHelper)
    {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function addMessage(): void
    {
        if ($this->application->getConnectedUser()->get()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!isset($data['eventId']) || !isset($data['text'])) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $apiResponse = $this->addMessage_($data['eventId'], $this->application->getConnectedUser()->person->Id, $data['text']);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function deleteMessage(): void
    {
        if ($this->application->getConnectedUser()->get()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $apiResponse = $this->deleteMessage_((int)$data['messageId'] ?? 0, $this->application->getConnectedUser()->person->Id);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function updateMessage(): void
    {
        if ($this->application->getConnectedUser()->get()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!isset($data['eventId']) || !isset($data['text'])) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $apiResponse = $this->updateMessage_($data['messageId'], $this->application->getConnectedUser()->person->Id, $data['text']);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    #region Private functions
    private function addMessage_(int $eventId, int $personId, string $text): ApiResponse
    {
        try {
            $messageId = $this->messageDataHelper->addMessage($eventId, $personId, $text);
            return new ApiResponse($messageId !== false, $messageId === false ? ApplicationError::BadRequest->value : ApplicationError::Ok->value, ['messageId' => $messageId], 'Message ajouté');
        } catch (PDOException $e) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], $e->getMessage());
        } catch (Throwable $e) {
            return new ApiResponse(false, ApplicationError::Error->value, [], $e->getMessage());
        }
    }

    private function deleteMessage_(int $messageId, int $personId): ApiResponse
    {
        $message = $this->dataHelper->get('Message', ['Id' => $messageId], 'PersonId');
        if (!$message) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], "Message {$messageId} doesn't exist");
        }
        if ($message->PersonId != $personId) {
            return new ApiResponse(false, ApplicationError::Forbidden->value, [], "Person {$personId} isn't allowed to remove message {$messageId}");
        }
        try {
            $result = $this->dataHelper->delete('Message', ['Id' => $messageId]);
            if ($result > 0) return new ApiResponse(true, ApplicationError::Ok->value, ['data' => ['messageId' => $messageId]], 'Message supprimé');
            return new ApiResponse(false, ApplicationError::BadRequest->value);
        } catch (PDOException $e) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], $e->getMessage());
        } catch (Throwable $e) {
            return new ApiResponse(false, ApplicationError::Error->value, [], $e->getMessage());
        }
    }

    private function updateMessage_(int $messageId, int $personId, string $text): ApiResponse
    {
        try {
            $this->messageDataHelper->updateMessage($messageId, $personId, $text);
            return new ApiResponse(true, ApplicationError::Ok->value, ['data' => ['messageId' => $messageId, 'text' => $text]], 'Message mis à jour');
        } catch (UnauthorizedAccessException $e) {
            return new ApiResponse(false, ApplicationError::Forbidden->value, [], $e->getMessage());
        } catch (PDOException $e) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], $e->getMessage());
        } catch (Throwable $e) {
            return new ApiResponse(false, ApplicationError::Error->value, [], $e->getMessage());
        }
    }
}
