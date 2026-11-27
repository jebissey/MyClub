<?php

declare(strict_types=1);

namespace app\modules\User\valueObjects;

use app\modules\Common\valueObjects\PendingDesignRow;
use app\modules\Common\valueObjects\PendingSurveyRow;

final readonly class PendingItems
{
    /**
     * @param list<PendingSurveyRow> $surveys
     * @param list<PendingDesignRow> $designs
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
