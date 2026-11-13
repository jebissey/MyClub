<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\DirectoryGroupRow;
use app\modules\Common\valueObjects\DirectoryPersonRow;
use app\modules\Common\valueObjects\MenuItemWithParentRow;

final readonly class UserDirectoryViewModel extends LayoutViewModel
{
    /**
     * @param list<DirectoryPersonRow> $persons
     * @param list<MenuItemWithParentRow> $navItems
     * @param list<DirectoryGroupRow> $groups
     * @param array<int, array{name: string, total: int, withPresentation: int}> $groupCounts
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
        public bool $filterMyGroups,
        public bool $filterSelfEnroll,
        public bool $filterWithAuth,
        public string $allGroupsUrl,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'directory';

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
            btnPlus: false,
            btnParent: '/',
        );
    }
}
