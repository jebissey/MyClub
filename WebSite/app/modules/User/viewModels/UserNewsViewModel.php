<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\valueObjects\Person;

/**
 * @phpstan-import-type NewsFeedItem from \app\modules\Common\interfaces\NewsProviderInterface
 */
final readonly class UserNewsViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemWithParentRow> $navItems
     * @param list<NewsFeedItem> $news
     * @param array<string, array{label: string, icon: string, bg: string}> $groupConfig
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $searchFrom,
        public string $searchMode,
        public array $navItems,
        public ?Person $person,
        public array $news,
        public array $groupConfig,
        string $btnParent = '/user',
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'news';

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
            btnParent: $btnParent,
            btnPlus: false,
        );
    }
}
