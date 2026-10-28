<?php

declare(strict_types=1);

namespace app\modules\Designer\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MenuArticleViewModel extends LayoutViewModel
{
    /**
     * @param object|false|null $chosenArticle Article row restricted to the Content column.
     * @param list<stdClass> $carouselItems
     * @param list<object> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public object|false|null $chosenArticle,
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
