<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class UploadedMedia
{
    public function __construct(
        public string $name,
        public string $path,
        public string $url,
        public int $size,
        public string $type,
    ) {
    }
}
