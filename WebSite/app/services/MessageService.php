<?php

namespace app\services;

use app\enums\ApplicationError;
use app\exceptions\UnauthorizedAccessException;
use app\interfaces\MessageServiceInterface;
use app\models\MessageDataHelper;
use app\valueObjects\ApiResponse;
use PDOException;
use Throwable;

class MessageService implements MessageServiceInterface
{
    private MessageDataHelper $messageDataHelper;

    public function __construct($messageDataHelper)
    {
        $this->messageDataHelper = $messageDataHelper;
    }

    public function addMessage(int $eventId, int $personId, string $text): ApiResponse
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

    public function updateMessage(int $messageId, int $personId, string $text): ApiResponse
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

    public function deleteMessage(int $messageId, int $personId): ApiResponse
    {
        try {
            $this->messageDataHelper->deleteMessage($messageId, $personId);
            return new ApiResponse(true, ApplicationError::Ok->value, ['data' => ['messageId' => $messageId]], 'Message supprimé');
        } catch (UnauthorizedAccessException $e) {
            return new ApiResponse(false, ApplicationError::Forbidden->value, [], $e->getMessage());
        } catch (PDOException $e) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], $e->getMessage());
        } catch (Throwable $e) {
            return new ApiResponse(false, ApplicationError::Error->value, [], $e->getMessage());
        }
    }
}
