<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Event\valueObjects\GuestEventRow;


final readonly class EventGuestViewModel extends LayoutViewModel
{
    /**
     * @param list<GuestEventRow> $events Replace with the element type returned by EventDataHelper::getEventsForAllOrGuest()
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $events,
        public string $navbarTemplate,
        public string $layout,
        public string $message,
        public string $messageType,
        public array $navItems,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnPlus: false,
            btnParent: null,
        );
    }
}
