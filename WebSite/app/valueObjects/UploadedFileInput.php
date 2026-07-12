<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class UploadedFileInput
{
    public function __construct(
        public string $name,
        public string $tmpName,
        public int $size,
        public string $type,
    ) {
    }

    /**
     * @param array{name: string, tmp_name: string, size: int|string, type: string} $file
     */
    public static function fromArray(array $file): self
    {
        return new self(
            name: (string)$file['name'],
            tmpName: (string)$file['tmp_name'],
            size: (int)$file['size'],
            type: (string)$file['type'],
        );
    }
}
