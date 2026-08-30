<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class GroupEditViewModel extends LayoutViewModel
{
    /**
     * @param list<int> $currentAuthorizations
     * @param list<stdClass> $availableAuthorizations
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public object $group,
        public array $availableAuthorizations,
        public array $currentAuthorizations,
        public string $layout,
        public ?string $error = null,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
        );
    }
}
