<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Event\valueObjects\RegistrationEventInfo;

final readonly class RegistrationSuccessViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public RegistrationEventInfo $event,
        public int $contactId,
        public string $contactEmail,
        public ?string $contactNickName,
        public array $navItems,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/eventManager',
            btnPlus: false,
        );
    }
}
