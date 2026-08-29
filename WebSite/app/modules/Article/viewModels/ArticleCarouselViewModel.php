<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\ArticleRow;
use stdClass;

/**
 * Specific ViewModel for article_carousel.latte, extending the layout requirements.
 */
final readonly class ArticleCarouselViewModel extends LayoutViewModel
{
    /**
     * @param list<stdClass> $carouselItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public ArticleRow $article,
        public array $carouselItems,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/articles',
        );
    }
}
