<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * Specific ViewModel for article_show.latte, extending the layout requirements.
 */
final readonly class ArticleShowViewModel extends LayoutViewModel
{
    /**
     * @param stdClass $article
     * @param array<int, stdClass> $groups
     * @param stdClass|null $hasSurvey
     * @param stdClass|null $hasOrder
     * @param array<int, stdClass> $carouselItems
     * @param array<string, mixed> $navItems
     * @param array<string, mixed> $allParams
     */
    public function __construct(
        // 1. Core View Specific Properties
        public int $id,
        public stdClass $article,
        public array $groups,
        public ?stdClass $hasSurvey,
        public ?stdClass $hasOrder,
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