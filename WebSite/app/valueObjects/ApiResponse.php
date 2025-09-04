<?php

namespace app\valueObjects;

readonly class ApiResponse
{
    public function __construct(
        public bool $success,
        public int $responseCode,
        public array $data = [],
        public ?string $message = null
    ) {}
}
