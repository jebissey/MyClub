<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\LocatedMemberRow;

final readonly class UserEditPresentationViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public LocatedMemberRow $person,
        public string $presentation,
        public string $validationMsg,
        public int $maxZoom,
        public array $navItems,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/user/directory'
        );
    }
}
