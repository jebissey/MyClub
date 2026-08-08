<?php

declare(strict_types=1);

namespace app\modules\Notifications;

use app\interfaces\RecipientResolverInterface;
use app\valueObjects\MessageContext;

class GroupRecipientResolver implements RecipientResolverInterface
{
    public function supports(MessageContext $context): bool
    {
        return $context->isGroup();
    }

    /**
     * @param array{
     *     newArticle?: string,
     *     updatedArticle?: string,
     *     newPollVote?: string,
     *     messageOnArticle?: string,
     *     messageOnEvent?: string,
     *     groupsSubscribed?: array<int, string>,
     *     groupsJoined?: array<int, string>,
     *     messageOnGroupNotJoined?: string
     * } $prefs
     */
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
