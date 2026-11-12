<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Event\valueObjects\EventAudienceRow;

final readonly class RegistrationSuccessViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public EventAudienceRow $event,
        public int $contactId,
        public string $contactEmail,
        public ?string $contactNickName,
        public array $navItems,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
