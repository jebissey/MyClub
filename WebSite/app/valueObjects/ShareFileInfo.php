<?php

declare(strict_types=1);

namespace app\valueObjects;

use JsonSerializable;
use stdClass;

final readonly class ShareFileInfo implements JsonSerializable
{
    public function __construct(
        public bool $success,
        public stdClass|false $data = false,
        public ?string $message = null,
    ) {
    }

    /**
     * @return array{success: bool, data: stdClass|false, message?: string}
     */
    public function jsonSerialize(): array
    {
        $result = [
            'success' => $this->success,
            'data' => $this->data,
        ];

        if ($this->message !== null) {
            $result['message'] = $this->message;
        }

        return $result;
    }
}
