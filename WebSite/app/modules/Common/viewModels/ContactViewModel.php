<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

use app\modules\Common\valueObjects\MenuItemRow;

final readonly class ContactViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param object{Id: int, Summary: string, StartTime: string, Audience: string}|null $event
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public ?object $event,
        public string $turnstileSiteKey,
        public string $name,
        public string $email,
        public bool $identityReadOnly,
        public string $layout,
        ?string $page = null,
        array $layoutParams = [],
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        // Keep the page computed from the layout params when none is given.
        if ($page !== null) {
            $baseArgs['page'] = $page;
        }

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
        );
    }
}
