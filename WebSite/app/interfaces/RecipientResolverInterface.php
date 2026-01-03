<?php

declare(strict_types=1);

namespace app\interfaces;

use app\valueObjects\MessageContext;

interface RecipientResolverInterface
{
    public function supports(MessageContext $context): bool;

    public function shouldNotify(
        MessageContext $context,
        int $personId,
        array $preferences
    ): bool;
}
