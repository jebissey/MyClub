<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\ArticleRow;

/**
 * Specific ViewModel for article_show.latte, extending the layout requirements.
 */
final readonly class ArticleShowViewModel extends LayoutViewModel
{
    /**
     * @param ArticleRow $article
     * @param list<stdClass> $groups
     * @param object|null $hasSurvey
     * @param object|null $hasOrder
     * @param list<stdClass> $carouselItems
     * @param list<object> $navItems
     * @param array<string, mixed> $allParams
     */
    public function __construct(
        // 1. Core View Specific Properties
        public int $id,
        public ArticleRow $article,
        public array $groups,
        public ?object $hasSurvey,
        public ?object $hasOrder,
        public bool $userConnected,
        public ?string $userEmail,
        public array $navItems,
        public string $publishedBy,
        public bool $canReadPool,
        public bool $canReadOrder,
        public array $carouselItems,
        public ?string $page,
        public int $countOfMessages,
        public bool $isCreator,
        public bool $isMember,
        public bool $isEditor,
        public ?string $message,
        public ?string $messageType,
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
