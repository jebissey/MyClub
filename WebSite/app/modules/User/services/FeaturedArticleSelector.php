<?php

declare(strict_types=1);

namespace app\modules\User\services;

use app\models\ArticleDataHelper;

final class FeaturedArticleSelector
{
    public function __construct(private readonly ArticleDataHelper $articleDataHelper)
    {
    }

    /**
     * Returns the id of the article to feature on the home page, or 0 if none applies.
     * The explicitly featured article wins over the active spotlight article.
     */
    public function selectId(string $userEmail, int $featuredArticleId): int
    {
        if (
            $featuredArticleId > 0
            && $this->articleDataHelper->isUserAllowedToReadArticle($userEmail, $featuredArticleId)
        ) {
            return $featuredArticleId;
        }

        $spotlight = $this->articleDataHelper->getSpotlightArticle();
        if ($spotlight === null) {
            return 0;
        }

        /** @var array{articleId: int, spotlightUntil: string} $spotlight */
        $articleId = (int) $spotlight['articleId'];
        $isStillActive = strtotime($spotlight['spotlightUntil']) >= strtotime(date('Y-m-d'));

        return $isStillActive && $this->articleDataHelper->isUserAllowedToReadArticle($userEmail, $articleId)
            ? $articleId
            : 0;
    }
}
