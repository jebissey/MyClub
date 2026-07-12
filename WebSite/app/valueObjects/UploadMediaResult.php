<?php

declare(strict_types=1);

namespace app\valueObjects;

readonly class UploadMediaResult
{
    public function __construct(
        public UploadedMedia $file,
    ) {
    }
}
