<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class UploadMediaResult
{
    public function __construct(
        public UploadedMedia $file,
    ) {
    }
}
