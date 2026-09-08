<?php

declare(strict_types=1);

namespace app\modules\Common\interfaces;

use app\modules\Common\valueObjects\SmtpConfig;

interface SmtpConfigProviderInterface
{
    public function get(): ?SmtpConfig;
}
