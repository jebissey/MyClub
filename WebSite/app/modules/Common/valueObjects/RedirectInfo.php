<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\enums\ApplicationError;

/**
 * Where to send the visitor, and what to log about it.
 */
final readonly class RedirectInfo
{
    public function __construct(
        public string $url,
        public ?ApplicationError $status = null,
        public ?string $message = null,
    ) {
    }
}
