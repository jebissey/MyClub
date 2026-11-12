<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Common\valueObjects\Person;

/**
 * @phpstan-import-type MessageGroupItem from \app\models\MessageDataHelper
 */
final readonly class UserMessagesViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param list<MessageGroupItem> $messages
     * @param array<string, array{label: string, icon: string, bg: string, url: string}> $groupConfig
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $searchFrom,
        public string $searchMode,
        public array $navItems,
        public ?Person $person,
        public array $messages,
        public array $groupConfig,
        string $btnParent = '/user',
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'messages';

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
            btnParent: $btnParent,
            btnPlus: false,
        );
    }
}
