<?php

declare(strict_types=1);

namespace app\apis;

use PDOException;
use Throwable;

use app\enums\ApplicationError;
use app\exceptions\UnauthorizedAccessException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\NotificationSender;
use app\models\DataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\services\MessageRecipientService;
use app\valueObjects\ApiResponse;
use app\valueObjects\MessageContext;

class MessageApi extends AbstractApi
{
    public function __construct(
        Application $application,
        private MessageDataHelper $messageDataHelper,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private MessageRecipientService $messageRecipientService,
        private NotificationSender $notificationSender
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function addMessage(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = $this->getJsonInput();
        if ((!isset($data['eventId']) && !isset($data['articleId']) && !isset($data['groupId'])) || !isset($data['text'])) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }
        try {
            $articleId = isset($data['articleId']) && $data['articleId'] !== '' ? (int)$data['articleId'] : null;
            $eventId = isset($data['eventId']) && $data['eventId'] !== '' ? (int)$data['eventId'] : null;
            $groupId = isset($data['groupId']) && $data['groupId'] !== '' ? (int)$data['groupId'] : null;
            $apiResponse = $this->addMessage_(
                $articleId,
                $eventId,
                $groupId,
                $this->application->getConnectedUser()->person->Id,
                (string)$data['text']
            );
            if ($apiResponse->success === true && isset($apiResponse->data['messageId'])) {
                $this->notifyMessageRecipients((int)$apiResponse->data['messageId'], $articleId, $eventId, $groupId);
            }

            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function deleteMessage(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
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
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function updateMessage(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = $this->getJsonInput();
        if (!isset($data['messageId']) || !isset($data['text'])) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $apiResponse = $this->updateMessage_((int)$data['messageId'], $this->application->getConnectedUser()->person->Id, $data['text']);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    #region Private functions
    private function addMessage_(?int $articleId, ?int $eventId, ?int $groupId, int $personId, string $text): ApiResponse
    {
        try {
            $messageId = $this->messageDataHelper->addMessage($articleId, $eventId, $groupId, $personId, $text);
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

    private function notifyMessageRecipients(
        int $messageId,
        ?int $articleId,
        ?int $eventId,
        ?int $groupId
    ): void {
        $articleAuthorId = null;
        $eventCreatorId = null;
        if ($articleId !== null) {
            $article = $this->dataHelper->get(
                'Article',
                ['Id' => $articleId],
                'CreatedBy'
            );
            $articleAuthorId = $article?->CreatedBy;
        }
        if ($eventId !== null) {
            $event = $this->dataHelper->get(
                'Event',
                ['Id' => $eventId],
                'CreatedBy'
            );
            $eventCreatorId = $event?->CreatedBy;
        }
        $context = new MessageContext(
            articleId: $articleId,
            articleAuthorId: $articleAuthorId,
            eventId: $eventId,
            eventCreatorId: $eventCreatorId,
            groupId: $groupId
        );

        $personIds = $this->messageRecipientService->getRecipientsForContext($context);
        $from = '';
        $id = null;
        if($articleId !== null) {
            $from = 'article';
            $id = $articleId;
        } elseif ($eventId !== null) {
            $from = 'event';
            $id = $eventId;
        } elseif ($groupId !== null) {
            $from = 'group';
            $id = $groupId;
        }
        $notificationData = [
            'title' => 'notificationTitle',
            'body' => 'notificationBody',
            'icon' => '/path/to/icon.png',
            'badge' => '/path/to/badge.png',
            'data' => [
                'url' => "/{$from}/chat/{$id}",
                'messageId' => $messageId,
                'type' => 'messageType'
            ]
        ];
        $this->notificationSender->sendToRecipients($personIds, $notificationData);
    }
}
