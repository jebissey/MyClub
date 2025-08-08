<?php

namespace app\models;

class AuthResult
{
    private bool $success;
    private ?object $user;
    private string $error;

    private function __construct(bool $success, ?object $user = null, string $error = '')
    {
        $this->success = $success;
        $this->user = $user;
        $this->error = $error;
    }

    public static function success(object $user): self
    {
        return new self(true, $user);
    }

    public static function error(string $message): self
    {
        return new self(false, null, $message);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getUser(): ?object
    {
        return $this->user;
    }

    public function getError(): string
    {
        return $this->error;
    }
}