<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Event\valueObjects\NeedTypeRow;
use app\modules\Event\valueObjects\NeedWithTypeRow;

final readonly class EventNeedsViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemWithParentRow> $navItems
     * @param list<NeedTypeRow> $needTypes
     * @param list<NeedWithTypeRow> $needs Replace with the element type returned by NeedDataHelper::getNeedsAndTheirTypes()
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $navItems,
        public array $needTypes,
        public array $needs,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/designer',
            btnPlus: false
        );
    }
}
