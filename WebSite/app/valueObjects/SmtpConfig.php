<?php

declare(strict_types=1);

namespace app\valueObjects;

readonly class SmtpConfig
{
    public function __construct(
        public string $host,
        public string $username,
        public string $password,
        public int $port = 587,
        public string $encryption = 'tls' // tls | ssl | none
    ) {}
}

