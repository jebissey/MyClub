<?php

declare(strict_types=1);

namespace app\valueObjects;

use InvalidArgumentException;

readonly class EmailMessage
{
    public function __construct(
        public string $from,
        public string $to,
        public string $subject,
        public string $body,
        public bool $isHtml = false,
        public array $cc = [],
        public array $bcc = [],
        public ?string $replyTo = null
    ) {
        self::assertValidEmail($this->from);
        self::assertValidEmail($this->to);

        foreach ($this->cc as $email) {
            self::assertValidEmail($email);
        }

        foreach ($this->bcc as $email) {
            self::assertValidEmail($email);
        }

        if ($this->replyTo !== null) {
            self::assertValidEmail($this->replyTo);
        }

        self::assertNoHeaderInjection($this->subject);
    }

    #region Private functions
    private static function assertValidEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$email}");
        }

        self::assertNoHeaderInjection($email);
    }

    private static function assertNoHeaderInjection(string $value): void
    {
        if (preg_match("/[\r\n]/", $value)) {
            throw new InvalidArgumentException('Header injection detected');
        }
    }
}
