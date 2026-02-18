<?php

declare(strict_types=1);

namespace app\interfaces;

use app\valueObjects\SmtpConfig;

interface SmtpConfigProviderInterface
{
    public function get(): ?SmtpConfig;
}
