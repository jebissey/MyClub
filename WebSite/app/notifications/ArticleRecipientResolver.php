<?php

declare(strict_types=1);

namespace app\notifications;

use app\interfaces\RecipientResolverInterface;
use app\valueObjects\MessageContext;

class ArticleRecipientResolver implements RecipientResolverInterface
{
    public function supports(MessageContext $context): bool
    {
        return $context->isArticle();
    }

    public function shouldNotify(
        MessageContext $context,
        int $personId,
        array $prefs
    ): bool {
        return ($prefs['messageOnArticle'] ?? null) === 'on'
            || (
                ($prefs['messageOnArticleIfAuthor'] ?? null) === 'on'
                && $context->isArticleAuthor($personId)
            )
            || ($prefs['messageOnArticleIfPost'] ?? null) === 'on';
    }
}
