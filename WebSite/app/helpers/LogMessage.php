<?php
declare(strict_types=1);

namespace app\helpers;

class LogMessage
{
    private static $instance = null;
    private ?string $code;
    private array $messages = [];
    private string $separator;

    private function __construct(?string $code, string $message, string $separator)
    {
        $this->code = $code;
        $this->separator = $separator;
        if ($message !== '') {
            $this->messages[] = $message;
        }
    }

    public static function getInstance(?string $code, string $message = '', string $separator = "\n"): LogMessage
    {
        if (self::$instance === null) self::$instance = new self($code, $message, $separator);
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

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->messages = [$message];
        return $this;
    }

}