<?php

declare(strict_types=1);

namespace app\modules\Designer\viewModels;

use app\modules\Common\valueObjects\CarouselItemRow;
use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MenuArticleViewModel extends LayoutViewModel
{
    /**
     * @param string $content HTML of the chosen article (empty when the article does not exist)
     * @param list<CarouselItemRow> $carouselItems
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $content,
        public array $carouselItems,
        public array $navItems,
        public bool $hasAuthorization,
        array $layoutParams,
        ?string $btnParent = '/',
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: $btnParent,
        );
    }
}
