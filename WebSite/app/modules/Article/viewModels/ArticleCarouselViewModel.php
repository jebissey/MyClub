<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\ArticleRow;

/**
 * Specific ViewModel for article_carousel.latte, extending the layout requirements.
 */
final readonly class ArticleCarouselViewModel extends LayoutViewModel
{
    /**
     * @param ArticleRow $article
     * @param list<stdClass> $carouselItems
     * @param array<string, mixed> $allParams
     */
    public function __construct(
        // 1. Core View Specific Properties
        public ArticleRow $article,
        public array $carouselItems,
        public ?string $page,
        // 2. Parent Layout Requirements (Forwarded to parent)
        string $navbarInkColor,
        string $navbarIconColor,
        string $navbarBgColor,
        ?string $productionSiteUrl = null,
        ?string $memberAlert = null,
        bool $btn_HistoryBack = true,
        string $btn_Parent = '/articles',
        array $allParams = []
    ) {
        parent::__construct(
            navbarInkColor: $navbarInkColor,
            navbarIconColor: $navbarIconColor,
            navbarBgColor: $navbarBgColor,
            productionSiteUrl: $productionSiteUrl,
            memberAlert: $memberAlert,
            btn_HistoryBack: $btn_HistoryBack,
            btn_Parent: $btn_Parent,
            allParams: $allParams
        );
    }
}
