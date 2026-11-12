<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\ArticleRow;

final readonly class PublishViewModel extends LayoutViewModel
{
    /** @param array<string, mixed> $layoutParams */
    public function __construct(
        public ArticleRow $article,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: null,
            btnPlus: false,
        );
    }
}
