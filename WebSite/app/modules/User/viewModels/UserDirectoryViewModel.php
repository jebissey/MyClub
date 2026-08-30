<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;
use app\valueObjects\Person;

final readonly class UserDirectoryViewModel extends LayoutViewModel
{
    /**
     * @param list<stdClass> $persons
     * @param list<MenuItemRow> $navItems
     * @param list<stdClass> $groups
     * @param array<int|string, mixed> $groupCounts
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $persons,
        public array $navItems,
        public bool $loggedPersonInPresentationDirectory,
        public array $groups,
        public array $groupCounts,
        public ?int $selectedGroup,
        public int $countOfMessages,
        public bool $userIsInGroup,
        public int $countOfLocatedMembers,
        public int $numberOfPublicMembers,
        public int $totalWithPresentation,
        public int $totalPersons,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
