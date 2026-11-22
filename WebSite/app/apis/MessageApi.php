<?php

declare(strict_types=1);

namespace app\apis;

use PDOException;
use Throwable;
use app\enums\ApplicationError;
use app\exceptions\UnauthorizedAccessException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\LanguagesDataHelper;
use app\models\MessageWriteDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\services\MessageImageService;
use app\modules\Common\services\MessageNotificationService;
use app\modules\Common\valueObjects\ApiResponse;
use app\modules\Common\valueObjects\MessageTarget;

final class MessageApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        LanguagesDataHelper $languagesDataHelper,
        PersonDataHelper $personDataHelper,
        private readonly MessageWriteDataHelper $messageWriteDataHelper,
        private readonly MessageImageService $messageImageService,
        private readonly MessageNotificationService $messageNotificationService,
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function addMessage(): void
    {
        if (!$this->isConnectedPost()) {
            return;
        }

        $data = $this->getJsonInput();
        if (!isset($data['text'])) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }

        try {
            $target = MessageTarget::fromIds(
                $this->optionalId($data, 'articleId'),
                $this->optionalId($data, 'eventId'),
                $this->optionalId($data, 'groupId'),
            );
            if ($target === null) {
                $this->renderJsonBadRequest('Exactly one target is required', __FILE__, __LINE__);
                return;
            }

            $imageUrl = null;
            if (!empty($data['imageBase64'])) {
                $imagePath = $this->messageImageService->storeFromDataUri(
                    To::str($data['imageBase64']),
                    To::str($data['imageName'] ?? '')
                );
                if ($imagePath === null) {
                    $this->renderJsonBadRequest('Image invalide ou trop volumineuse', __FILE__, __LINE__);
                    return;
                }
                $imageUrl = WebApp::getBaseUrl() . $imagePath;
            }

            $apiResponse = $this->doAddMessage($target, $this->currentPersonId(), To::str($data['text']), $imageUrl);

            if ($apiResponse->success === true && isset($apiResponse->data['messageId'])) {
                $this->messageNotificationService->notifyRecipients($target, To::int($apiResponse->data['messageId']));
            }

            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function deleteMessage(): void
    {
        if (!$this->isConnectedPost()) {
            return;
        }

        try {
            $data = $this->getJsonInput();
            $apiResponse = $this->doDeleteMessage(To::int($data['messageId']), $this->currentPersonId());
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function deleteMessageImage(): void
    {
        if (!$this->isConnectedPost()) {
            return;
        }

        try {
            $messageId = To::int($this->getJsonInput()['messageId'] ?? 0);
            if ($messageId <= 0) {
                $this->renderJsonBadRequest('messageId invalide', __FILE__, __LINE__);
                return;
            }

            $failure = $this->ownershipFailure($messageId, $this->currentPersonId());
            if ($failure !== null) {
                $this->renderJson($failure->data, $failure->success, $failure->responseCode);
                return;
            }

            $result = $this->messageImageService->deleteForMessage($messageId);

            $this->renderJson([], $result['success'], $result['success'] ? 200 : 400, $result['message']);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function updateMessage(): void
    {
        if (!$this->isConnectedPost()) {
            return;
        }

        $data = $this->getJsonInput();
        if (!isset($data['messageId']) || !isset($data['text'])) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }
        $messageId = To::int($data['messageId']);

        try {
            if (!$this->dataHelper->get('Message', ['Id' => $messageId], 'Id')) {
                $this->renderJsonBadRequest('Message introuvable', __FILE__, __LINE__);
                return;
            }
            $apiResponse = $this->doUpdateMessage($messageId, $this->currentPersonId(), To::str($data['text']));
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    #region Private functions
    private function isConnectedPost(): bool
    {
        return $this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__);
    }

    private function currentPersonId(): int
    {
        return $this->application->getConnectedUser(true)->person->id;
    }

    /** @param array<string, mixed> $data */
    private function optionalId(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return $value === null || $value === '' ? null : To::int($value);
    }

    private function doAddMessage(MessageTarget $target, int $personId, string $text, ?string $imageUrl): ApiResponse
    {
        try {
            $messageId = $this->messageWriteDataHelper->addMessage($target, $personId, $text, $imageUrl);
            return new ApiResponse(
                $messageId !== false,
                $messageId === false ? ApplicationError::BadRequest->value : ApplicationError::Ok->value,
                ['messageId' => $messageId],
                'Message ajouté'
            );
        } catch (Throwable $e) {
            return $this->toFailure($e);
        }
    }

    private function doDeleteMessage(int $messageId, int $personId): ApiResponse
    {
        $failure = $this->ownershipFailure($messageId, $personId);
        if ($failure !== null) {
            return $failure;
        }

        try {
            if ($this->dataHelper->delete('Message', ['Id' => $messageId]) > 0) {
                return new ApiResponse(true, ApplicationError::Ok->value, ['data' => ['messageId' => $messageId]], 'Message supprimé');
            }
            return new ApiResponse(false, ApplicationError::BadRequest->value);
        } catch (Throwable $e) {
            return $this->toFailure($e);
        }
    }

    /**
     * Returns a failure response if the message doesn't exist or isn't owned by the person, null otherwise.
     */
    private function ownershipFailure(int $messageId, int $personId): ?ApiResponse
    {
        $row = $this->dataHelper->get('Message', ['Id' => $messageId], 'PersonId');
        if ($row === false) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], "Message {$messageId} doesn't exist");
        }
        /** @var object{PersonId: int|string} $row */
        if ((int) $row->PersonId !== $personId) {
            return new ApiResponse(
                false,
                ApplicationError::Forbidden->value,
                [],
                "Person {$personId} isn't allowed to modify message {$messageId}"
            );
        }

        return null;
    }

    private function doUpdateMessage(int $messageId, int $personId, string $text): ApiResponse
    {
        try {
            $this->messageWriteDataHelper->updateMessage($messageId, $personId, $text);
            return new ApiResponse(
                true,
                ApplicationError::Ok->value,
                ['data' => ['messageId' => $messageId, 'text' => $text]],
                'Message mis à jour'
            );
        } catch (Throwable $e) {
            return $this->toFailure($e);
        }
    }

    private function toFailure(Throwable $e): ApiResponse
    {
        $error = match (true) {
            $e instanceof UnauthorizedAccessException => ApplicationError::Forbidden,
            $e instanceof PDOException                => ApplicationError::BadRequest,
            default                                   => ApplicationError::Error,
        };

        return new ApiResponse(false, $error->value, [], $e->getMessage());
    }
    #endregion
}
