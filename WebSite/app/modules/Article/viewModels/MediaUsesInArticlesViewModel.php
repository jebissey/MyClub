<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MediaUsesInArticlesViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $articles
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $path,
        public array $articles,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
        );
    }
}
