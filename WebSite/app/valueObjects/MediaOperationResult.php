<?php

declare(strict_types=1);

namespace app\valueObjects;

readonly class MediaOperationResult
{
    public function __construct(
        public bool $success,
        public ?string $message = null,
        public ?string $file = null,
        public ?int $line = null,
    ) {
    }

    /**
     * @return array{success: bool, message: string|null}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}