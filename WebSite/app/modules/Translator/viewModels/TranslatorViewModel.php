<?php

declare(strict_types=1);

namespace app\modules\Translator\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class TranslatorViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param list<object{Id: int, Name: string, ref_value: string|null, target_value: string|null}> $i18n
     * @param list<string> $languages
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public string $title,
        public array $i18n,
        public string $referenceLang,
        public string $targetLang,
        public int $missingOnly,
        public int $missingCount,
        public array $languages,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/designer',
        );
    }
}
