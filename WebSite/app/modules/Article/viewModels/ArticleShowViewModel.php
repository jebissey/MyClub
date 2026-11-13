<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use stdClass;
use app\modules\Common\valueObjects\ArticleRow;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\valueObjects\CarouselItemRow;
use app\modules\Common\valueObjects\ClosingVisibilityRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class ArticleShowViewModel extends LayoutViewModel
{
    /**
     * @param ArticleRow $article
     * @param list<stdClass> $groups
     * @param list<CarouselItemRow> $carouselItems
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public int $id,
        public ArticleRow $article,
        public array $groups,
        public ?ClosingVisibilityRow $hasSurvey,
        public ?ClosingVisibilityRow $hasOrder,
        public bool $userConnected,
        public array $navItems,
        public string $publishedBy,
        public bool $canReadPool,
        public bool $canReadOrder,
        public array $carouselItems,
        public int $countOfMessages,
        public bool $isCreator,
        public ?string $message,
        public ?string $messageType,
        array $layoutParams,
        ?string $btnParent = '/articles',
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: $btnParent,
            btnPlus: false,
        );
    }
}
