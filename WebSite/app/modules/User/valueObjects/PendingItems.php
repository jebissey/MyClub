<?php

declare(strict_types=1);

namespace app\modules\User\valueObjects;

/**
 * @phpstan-type PendingSurvey object{articleTitle: string, closingDate: string, surveyQuestion: string, articleId: int}
 * @phpstan-type PendingDesign object{designName: string, designDetail: string}
 */
final readonly class PendingItems
{
    /**
     * @param list<PendingSurvey> $surveys
     * @param list<PendingDesign> $designs
     */
    public function __construct(
        public array $surveys,
        public array $designs,
        public bool $news
    ) {
    }

    /**
     * Nothing pending (anonymous visitor).
     */
    public static function none(): self
    {
        return new self([], [], false);
    }
}
