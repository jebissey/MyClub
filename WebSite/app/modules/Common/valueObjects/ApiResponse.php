<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class ApiResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public bool $success,
        public int $responseCode,
        public array $data = [],
        public ?string $message = null
    ) {
    }
}
