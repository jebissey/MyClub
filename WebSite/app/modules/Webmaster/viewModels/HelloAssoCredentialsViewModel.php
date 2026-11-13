<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemWithParentRow;

final readonly class HelloAssoCredentialsViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public ?string $helloassoClientId,
        public bool $helloassoConfigured,
        public ?string $helloassoOrgSlug,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/webmaster',
            btnPlus: false,
        );
    }
}
