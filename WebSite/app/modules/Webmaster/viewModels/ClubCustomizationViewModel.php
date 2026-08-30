<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class ClubCustomizationViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array{clubName: string, clubShortName: string, themeColor: string, background: string} $settings
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public array $settings,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'clubCustomization';

        parent::__construct(
            ...$baseArgs,
            btn_HistoryBack: true,
            btn_Parent: '/webmaster',
        );
    }
}
