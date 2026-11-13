<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\PersonManager\valueObjects\GroupEditRow;

final readonly class GroupEditViewModel extends LayoutViewModel
{
    /**
     * @param list<IdNameRow> $availableAuthorizations
     * @param list<int> $currentAuthorizations
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public GroupEditRow $group,
        public array $availableAuthorizations,
        public array $currentAuthorizations,
        public string $layout,
        public ?string $error = null,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/',
            btnPlus: false,
        );
    }
}
