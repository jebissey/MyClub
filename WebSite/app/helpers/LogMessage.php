<?php

declare(strict_types=1);

namespace app\helpers;

final class LogMessage
{
    private static ?self $instance = null;

    /** @var array<int, string> */
    private array $messages = [];

    private function __construct(
        private readonly ?string $code,
        string $message,
        private readonly string $separator,
    ) {
        if ($message !== '') {
            $this->messages[] = $message;
        }
    }

    public static function getInstance(?string $code, string $message = '', string $separator = " --- "): LogMessage
    {
        if (self::$instance === null) {
            self::$instance = new self($code, $message, $separator);
        }
        return self::$instance;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return implode($this->separator, $this->messages);
    }

    public function setMessage(string $message): self
    {
        $this->messages[] = $message;
        return $this;
    }
}
