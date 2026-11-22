<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use finfo;
use GdImage;
use app\helpers\MediaManager;
use app\models\MessageDataHelper;
use app\modules\Common\valueObjects\UploadedFileInput;

use function imagealphablending;
use function imagecolorallocatealpha;
use function imagecopyresampled;
use function imagecreatefromstring;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagegif;
use function imagejpeg;
use function imagepng;
use function imagesavealpha;
use function imagesx;
use function imagesy;
use function imagewebp;

final class MessageImageService
{
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    private const MAX_BYTES = 1 * 1024 * 1024;
    private const MAX_DIMENSION = 800;

    public function __construct(
        private readonly MediaManager $mediaManager,
        private readonly MessageDataHelper $messageDataHelper,
    ) {
    }

    /**
     * Validates, resizes and stores an image sent as a base64 data URI.
     *
     * @return string|null the stored file path, or null if the image is invalid
     */
    public function storeFromDataUri(string $dataUri, string $originalName): ?string
    {
        $decoded = $this->decodeDataUri($dataUri);
        if ($decoded === null) {
            return null;
        }
        [$mime, $binary] = $decoded;

        $binary = $this->resize($binary, $mime) ?? $binary;

        return $this->upload($binary, $mime, $originalName);
    }

    /**
     * Deletes the image attached to a message.
     *
     * @return array{success: bool, message: string}
     */
    public function deleteForMessage(int $messageId): array
    {
        [$year, $month, $filename] = $this->messageDataHelper->getImageInfoFromMessage($messageId);
        $result = $this->mediaManager->deleteFile($year, $month, $filename);

        return [
            'success' => $result->success,
            'message' => $result->message ?? '',
        ];
    }

    /** @return array{0: string, 1: string}|null [mime, binary] */
    private function decodeDataUri(string $dataUri): ?array
    {
        if (!preg_match('/^data:(image\/[a-z]+);base64,(.+)$/s', $dataUri, $matches)) {
            return null;
        }
        $mime = $matches[1];
        $binary = base64_decode($matches[2], strict: true);

        if ($binary === false || !isset(self::EXTENSIONS[$mime]) || strlen($binary) > self::MAX_BYTES) {
            return null;
        }
        // The declared MIME type must match the real content
        if ((new finfo(FILEINFO_MIME_TYPE))->buffer($binary) !== $mime) {
            return null;
        }

        return [$mime, $binary];
    }

    private function upload(string $binary, string $mime, string $originalName): ?string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'chat_img_');
        if ($tmpPath === false) {
            return null;
        }

        try {
            if (file_put_contents($tmpPath, $binary) === false) {
                return null;
            }
            $uploadedFile = new UploadedFileInput(
                name: $this->buildFilename($originalName, $mime),
                tmpName: $tmpPath,
                size: strlen($binary),
                type: $mime,
            );

            return $this->mediaManager->uploadFile($uploadedFile)->file->path;
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }
    }

    private function buildFilename(string $originalName, string $mime): string
    {
        $baseName = $originalName !== '' ? pathinfo($originalName, PATHINFO_FILENAME) : bin2hex(random_bytes(8));

        return $baseName . '.' . self::EXTENSIONS[$mime];
    }

    private function resize(string $binary, string $mime): ?string
    {
        $source = imagecreatefromstring($binary);
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width <= self::MAX_DIMENSION && $height <= self::MAX_DIMENSION) {
            return $binary;
        }

        $ratio = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $target = $this->createCanvas($newWidth, $newHeight, $mime);
        if ($target === null) {
            return null;
        }
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $this->encode($target, $mime);
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private function createCanvas(int $width, int $height, string $mime): ?GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            return null;
        }

        // Preserve transparency for formats that support it
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            }
        }

        return $canvas;
    }

    private function encode(GdImage $image, string $mime): ?string
    {
        ob_start();
        match ($mime) {
            'image/jpeg' => imagejpeg($image, null, 85),
            'image/png'  => imagepng($image, null, 6),
            'image/webp' => imagewebp($image, null, 85),
            'image/gif'  => imagegif($image),
            default      => null,
        };
        $encoded = ob_get_clean();

        return ($encoded === false || $encoded === '') ? null : $encoded;
    }
}
