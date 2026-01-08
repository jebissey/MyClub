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

    public function shouldNotify(MessageContext $context, int $personId, array $prefs): bool
    {
        $groupId = $context->groupId;
        $isSubscribed = isset($prefs['groupsSubscribed'][$groupId]);
        $isJoined     = isset($prefs['groupsJoined'][$groupId]);
        if ($isSubscribed || $isJoined) {
            return true;
        }
        return ($prefs['messageOnGroupNotJoined'] ?? null) === 'on';
    }
}
