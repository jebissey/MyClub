<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

final readonly class HomeViewModel extends LayoutViewModel
{
    /**
     * @param list<object> $latestArticles
     * @param list<mixed> $navItems
     * @param list<mixed> $sidebarMenu
     * @param list<object> $pendingSurveys
     * @param list<object> $pendingDesigns
     * @param list<object> $carouselItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public ?object $latestArticle,
        public array $latestArticles,
        public int $latestArticlesCount,
        public string $homeHeader,
        public string $homeFooter,
        public array $navItems,
        public array $sidebarMenu,
        public string $publishedBy,
        public bool $latestArticleHasSurvey,
        public array $pendingSurveys,
        public array $pendingDesigns,
        public bool $news,
        public array $carouselItems,
        public int $homeParagraphsCount,
        public ?object $footerArticle,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
        );
    }
}
