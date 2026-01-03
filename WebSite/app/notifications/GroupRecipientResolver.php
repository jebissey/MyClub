<?php

declare(strict_types=1);

namespace app\notifications;

use app\interfaces\RecipientResolverInterface;
use app\valueObjects\MessageContext;

class GroupRecipientResolver implements RecipientResolverInterface
{
    public function supports(MessageContext $context): bool
    {
        return $context->isGroup();
    }

    public function shouldNotify(
        MessageContext $context,
        int $personId,
        array $prefs
    ): bool {
        $groupId = $context->groupId;

        $subscribed = $prefs['groupsSubscribed'][$groupId] ?? null;
        $joined = $prefs['groupsJoined'][$groupId] ?? null;

        return (
                ($prefs['messageOnGroupSubscribed'] ?? null) === 'on'
                && $subscribed === 'on'
            )
            || (
                ($prefs['messageOnGroupJoined'] ?? null) === 'on'
                && $joined === 'on'
            )
            || (
                ($prefs['messageOnGroupNotJoined'] ?? null) === 'on'
                && !$subscribed && !$joined
            );
    }
}
