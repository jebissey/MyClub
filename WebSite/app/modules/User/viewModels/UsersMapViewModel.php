<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class UsersMapViewModel extends LayoutViewModel
{
    /**
     * @param array<int, array{
     *     id: int,
     *     name: string,
     *     nickname: ?string,
     *     avatar: ?string,
     *     useGravatar: bool,
     *     email: string,
     *     lat: string,
     *     lng: string,
     *     userImg: string,
     *     myPublicData: ?string
     * }> $locationData
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $locationData,
        public int $membersCount,
        public array $navItems,
        public string $title,
        public bool $isPublic,
        public int $maxZoom,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
        );
    }
}
