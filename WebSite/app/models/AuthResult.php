<?php

declare(strict_types=1);

namespace app\models;

use app\valueObjects\Person;

class AuthResult
{
    private bool $success;
    private ?Person $user;
    private string $error;

    private function __construct(bool $success, ?Person $user = null, string $error = '')
    {
        $this->success = $success;
        $this->user = $user;
        $this->error = $error;
    }

    public static function success(Person $user): self
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

    public function getUser(): ?Person
    {
        return $this->user;
    }

    public function getError(): string
    {
        return $this->error;
    }
}
