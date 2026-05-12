<?php

declare(strict_types=1);

namespace app\apis;

use PDOException;
use InvalidArgumentException;
use Throwable;
use finfo;
use function imagecreatefromstring;
use function imagesx;
use function imagesy;
use function imagecreatetruecolor;
use function imagealphablending;
use function imagesavealpha;
use function imagecolorallocatealpha;
use function imagefilledrectangle;
use function imagecopyresampled;
use function imagejpeg;
use function imagepng;
use function imagewebp;
use function imagegif;

use app\enums\ApplicationError;
use app\exceptions\UnauthorizedAccessException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\MediaManager;
use app\helpers\NotificationSender;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\services\MessageRecipientService;
use app\valueObjects\ApiResponse;
use app\valueObjects\MessageContext;

class MessageApi extends AbstractApi
{
    const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    const MAX_BYTES    = 1 * 1024 * 1024;

    public function __construct(
        Application $application,
        private MessageDataHelper $messageDataHelper,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private MessageRecipientService $messageRecipientService,
        private NotificationSender $notificationSender,
        private MediaManager $mediaManager
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
        if (
            (!isset($data['eventId']) && !isset($data['articleId']) && !isset($data['groupId']))
            || !isset($data['text'])
        ) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }

        try {
            $articleId = isset($data['articleId']) && $data['articleId'] !== '' ? (int)$data['articleId'] : null;
            $eventId   = isset($data['eventId'])   && $data['eventId']   !== '' ? (int)$data['eventId']   : null;
            $groupId   = isset($data['groupId'])   && $data['groupId']   !== '' ? (int)$data['groupId']   : null;

            $imagePath = null;
            if (!empty($data['imageBase64'])) {
                $imagePath = $this->handleMessageImageBase64((string)$data['imageBase64'], (string)($data['imageName'] ?? ''));
                if ($imagePath === null) {
                    $this->renderJsonBadRequest('Image invalide ou trop volumineuse', __FILE__, __LINE__);
                    return;
                }
            }

            $apiResponse = $this->addMessage_(
                $articleId,
                $eventId,
                $groupId,
                $this->application->getConnectedUser()->person->Id,
                (string)$data['text'],
                $imagePath !== null ? Webapp::getBaseUrl() . $imagePath : null,
            );

            if ($apiResponse->success === true && isset($apiResponse->data['messageId'])) {
                $this->notifyMessageRecipients(
                    (int)$apiResponse->data['messageId'],
                    $articleId,
                    $eventId,
                    $groupId
                );
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

    public function deleteMessageImage(): void
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

            $messageId = (int)($data['messageId'] ?? 0);
            if ($messageId <= 0) {
                throw new InvalidArgumentException("messageId invalide");
            }

            [$year, $month, $filename] = $this->messageDataHelper->getImageInfoFromMessage($messageId);

            $result = $this->mediaManager->deleteFile($year, $month, $filename);

            $this->renderJson(
                [],
                $result['success'],
                $result['success'] ? 200 : 400,
                $result['message']
            );
        } catch (Throwable $e) {
            $this->renderJsonError(
                $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
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
        $message = $this->dataHelper->get('Message', ['Id' => (int)$data['messageId']], 'Id');
        if (!$message) {
            $this->renderJsonBadRequest('Message introuvable', __FILE__, __LINE__);
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
    private function addMessage_(?int $articleId, ?int $eventId, ?int $groupId, int $personId, string $text, ?string $imagePath = null): ApiResponse
    {
        try {
            $messageId = $this->messageDataHelper->addMessage($articleId, $eventId, $groupId, $personId, $text, $imagePath);
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

    private function notifyMessageRecipients(int $messageId, ?int $articleId, ?int $eventId, ?int $groupId): void
    {
        $articleAuthorId = null;
        $eventCreatorId = null;
        if ($articleId !== null) {
            $article = $this->dataHelper->get('Article', ['Id' => $articleId], 'CreatedBy, Title');
            $articleAuthorId = $article?->CreatedBy;
        }
        if ($eventId !== null) {
            $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'CreatedBy, Summary');
            $eventCreatorId = $event?->CreatedBy;
        }
        if ($groupId !== null) {
            $group = $this->dataHelper->get('Group', ['Id' => $groupId], 'Name');
        }

        $context = new MessageContext(
            articleId: $articleId,
            articleAuthorId: $articleAuthorId,
            eventId: $eventId,
            eventCreatorId: $eventCreatorId,
            groupId: $groupId
        );

        $personIds = $this->messageRecipientService->getRecipientsForContext($context);
        $title = 'Nouveau message';
        $body = 'Un nouveau message a été ajouté ';
        $from = '';
        $id = null;
        if ($articleId !== null) {
            $from = 'article';
            $id = $articleId;
            $body .= "à l'article {$article->Title} ({$articleId})";
        } elseif ($eventId !== null) {
            $from = 'event';
            $id = $eventId;
            $body .= "à l'événement {$event->Summary} ({$eventId})";
        } elseif ($groupId !== null) {
            $from = 'group';
            $id = $groupId;
            $body .= "au groupe {$group->Name}";
        }
        $notificationData = [
            'title' => $title,
            'body' => $body,
            'data' => [
                'url' => "/{$from}/chat/{$id}",
                'messageId' => $messageId
            ]
        ];
        $this->notificationSender->sendToRecipients($personIds, $notificationData);
    }

    private function handleMessageImageBase64(string $dataUri, string $originalName): ?string
    {
        if (!preg_match('/^data:(image\/[a-z]+);base64,(.+)$/s', $dataUri, $matches)) {
            return null;
        }
        $mime   = $matches[1];
        $binary = base64_decode($matches[2], strict: true);

        if ($binary === false || !in_array($mime, self::ALLOWED_MIME, true)) {
            return null;
        }
        if (strlen($binary) > self::MAX_BYTES) {
            return null;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($finfo->buffer($binary) !== $mime) {
            return null;
        }
        $binary = $this->resizeImageBinary($binary, $mime, 800) ?? $binary;
        $tmpPath = tempnam(sys_get_temp_dir(), 'chat_img_');
        if ($tmpPath === false || file_put_contents($tmpPath, $binary) === false) {
            return null;
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        };
        $filename = ($originalName !== '' ? pathinfo($originalName, PATHINFO_FILENAME) : bin2hex(random_bytes(8)))
            . '.' . $extension;
        $fakeFile = [
            'name'     => $filename,
            'type'     => $mime,
            'tmp_name' => $tmpPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($binary),
        ];

        try {
            $result = $this->mediaManager->uploadFile($fakeFile);
            return $result['file']['path'] ?? $result['file']['url'] ?? null;
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }
    }

    private function resizeImageBinary(string $binary, string $mime, int $maxDim): ?string
    {
        $src = imagecreatefromstring($binary);
        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        if ($w <= $maxDim && $h <= $maxDim) {
            return $binary;
        }

        $ratio = min($maxDim / $w, $maxDim / $h);
        $newW  = (int)round($w * $ratio);
        $newH  = (int)round($h * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

        ob_start();
        match ($mime) {
            'image/jpeg' => imagejpeg($dst, null, 85),
            'image/png'  => imagepng($dst, null, 6),
            'image/webp' => imagewebp($dst, null, 85),
            'image/gif'  => imagegif($dst),
        };
        $resized = ob_get_clean();

        return $resized ?: null;
    }
}
