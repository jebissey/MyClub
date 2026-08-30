<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class RegistrationUserGroupsViewModel extends LayoutViewModel
{
    /**
     * @param array<int, stdClass> $currentGroups
     * @param array<int, stdClass> $availableGroups
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $currentGroups,
        public array $availableGroups,
        public int $personId,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
        );
    }
}
