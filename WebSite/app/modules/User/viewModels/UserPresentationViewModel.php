<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\LocatedMemberRow;
use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Common\valueObjects\Person;

final readonly class UserPresentationViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public LocatedMemberRow $person,
        public Person $loggedPerson,
        public string $presentation,
        public array $navItems,
        public string $personImg,
        public int $maxZoom,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/user/directory',
        );
    }
}
