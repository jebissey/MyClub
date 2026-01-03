<?php

declare(strict_types=1);

namespace app\notifications;

use app\interfaces\RecipientResolverInterface;
use app\valueObjects\MessageContext;

class EventRecipientResolver implements RecipientResolverInterface
{
    public function supports(MessageContext $context): bool
    {
        return $context->isEvent();
    }

    public function shouldNotify(
        MessageContext $context,
        int $personId,
        array $prefs
    ): bool {
        return ($prefs['messageOnEvent'] ?? null) === 'on'
            || (
                ($prefs['messageOnEventIfCreator'] ?? null) === 'on'
                && $context->isEventCreator($personId)
            );
    }
}
