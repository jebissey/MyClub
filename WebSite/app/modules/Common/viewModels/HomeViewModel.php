<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

use app\modules\Common\valueObjects\ArticleRow;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\valueObjects\SidebarMenuItem;

final readonly class HomeViewModel extends LayoutViewModel
{
    /**
     * @param list<object{id: int, title: string, lastUpdate: string}> $latestArticles
     * @param list<MenuItemWithParentRow> $navItems
     * @param list<SidebarMenuItem> $sidebarMenu
     * @param list<object{articleTitle: string, closingDate: string, surveyQuestion: string, articleId: int}> $pendingSurveys
     * @param list<object{designName: string, designDetail: string}> $pendingDesigns
     * @param list<object{item: string}> $carouselItems
     * @param object{Title: string, Content: string}|null $footerArticle
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public ?ArticleRow $latestArticle,
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
            btnHistoryBack: false,
            btnPlus: false,
            btnParent: null,
        );
    }
}
