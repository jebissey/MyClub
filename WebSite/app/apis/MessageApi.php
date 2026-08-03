<?php

declare(strict_types=1);

namespace app\apis;

use PDOException;
use InvalidArgumentException;
use Throwable;
use finfo;
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
use app\valueObjects\ArticleCreatorTitleRow;
use app\valueObjects\EventCreatorSummaryRow;
use app\valueObjects\GroupNameRow;
use app\valueObjects\MessageContext;
use app\valueObjects\MessageOwnerRow;
use app\valueObjects\UploadedFileInput;

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

class MessageApi extends AbstractApi
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_BYTES    = 1 * 1024 * 1024;

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

            $apiResponse = $this->doAddMessage(
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
            $apiResponse = $this->doDeleteMessage((int)$data['messageId'], $this->application->getConnectedUser()->person->Id);
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
                $result->success,
                $result->success ? 200 : 400,
                $result->message ?? ''
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
            $apiResponse = $this->doUpdateMessage(
                (int)$data['messageId'],
                $this->application->getConnectedUser()->person->Id,
                $data['text']
            );
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    #region Private functions
    private function doAddMessage(
        ?int $articleId,
        ?int $eventId,
        ?int $groupId,
        int $personId,
        string $text,
        ?string $imagePath = null
    ): ApiResponse {
        try {
            $messageId = $this->messageDataHelper->addMessage($articleId, $eventId, $groupId, $personId, $text, $imagePath);
            return new ApiResponse(
                $messageId !== false,
                $messageId === false ? ApplicationError::BadRequest->value : ApplicationError::Ok->value,
                ['messageId' => $messageId],
                'Message ajouté'
            );
        } catch (PDOException $e) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], $e->getMessage());
        } catch (Throwable $e) {
            return new ApiResponse(false, ApplicationError::Error->value, [], $e->getMessage());
        }
    }

    private function doDeleteMessage(int $messageId, int $personId): ApiResponse
    {
        $row = $this->dataHelper->get('Message', ['Id' => $messageId], 'PersonId');
        if ($row === false) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], "Message {$messageId} doesn't exist");
        }
        /** @var object{PersonId: int|string} $row */
        $message = MessageOwnerRow::fromStdClass($row);
        if ($message->PersonId !== $personId) {
            return new ApiResponse(
                false,
                ApplicationError::Forbidden->value,
                [],
                "Person {$personId} isn't allowed to remove message {$messageId}"
            );
        }
        try {
            $result = $this->dataHelper->delete('Message', ['Id' => $messageId]);
            if ($result > 0) {
                return new ApiResponse(true, ApplicationError::Ok->value, ['data' => ['messageId' => $messageId]], 'Message supprimé');
            }
            return new ApiResponse(false, ApplicationError::BadRequest->value);
        } catch (PDOException $e) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], $e->getMessage());
        } catch (Throwable $e) {
            return new ApiResponse(false, ApplicationError::Error->value, [], $e->getMessage());
        }
    }

    private function doUpdateMessage(int $messageId, int $personId, string $text): ApiResponse
    {
        try {
            $this->messageDataHelper->updateMessage($messageId, $personId, $text);
            return new ApiResponse(
                true,
                ApplicationError::Ok->value,
                ['data' => ['messageId' => $messageId, 'text' => $text]],
                'Message mis à jour'
            );
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
        $article = null;
        $event = null;
        $group = null;

        if ($articleId !== null) {
            $row = $this->dataHelper->get('Article', ['Id' => $articleId], 'CreatedBy, Title');
            if ($row === false) {
                $this->application->getErrorManager()->raise(
                    ApplicationError::Error,
                    "Article {$articleId} not found when notifying message recipients for message {$messageId}"
                );
                return;
            }
            /** @var object{CreatedBy: int|string, Title: string} $row */
            $article = ArticleCreatorTitleRow::fromStdClass($row);
            $articleAuthorId = $article->CreatedBy;
        }
        if ($eventId !== null) {
            $row = $this->dataHelper->get('Event', ['Id' => $eventId], 'CreatedBy, Summary');
            if ($row === false) {
                $this->application->getErrorManager()->raise(
                    ApplicationError::Error,
                    "Event {$eventId} not found when notifying message recipients for message {$messageId}"
                );
                return;
            }
            /** @var object{CreatedBy: int|string, Summary: string} $row */
            $event = EventCreatorSummaryRow::fromStdClass($row);
            $eventCreatorId = $event->CreatedBy;
        }
        if ($groupId !== null) {
            $row = $this->dataHelper->get('Group', ['Id' => $groupId], 'Name');
            if ($row === false) {
                $this->application->getErrorManager()->raise(
                    ApplicationError::Error,
                    "Group {$groupId} not found when notifying message recipients for message {$messageId}"
                );
                return;
            }
            /** @var object{Name: string} $row */
            $group = GroupNameRow::fromStdClass($row);
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

        $uploadedFile = new UploadedFileInput(
            name: $filename,
            tmpName: $tmpPath,
            size: strlen($binary),
            type: $mime,
        );

        try {
            $result = $this->mediaManager->uploadFile($uploadedFile);
            return $result->file->path;
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
        $newW  = max(1, (int)round($w * $ratio));
        $newH  = max(1, (int)round($h * $ratio));

        $dst = imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            return null;
        }

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
            }
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

        ob_start();
        match ($mime) {
            'image/jpeg' => imagejpeg($dst, null, 85),
            'image/png'  => imagepng($dst, null, 6),
            'image/webp' => imagewebp($dst, null, 85),
            'image/gif'  => imagegif($dst),
            default => ob_end_clean() && null,
        };
        $resized = ob_get_clean();

        return $resized ?: null;
    }
}
