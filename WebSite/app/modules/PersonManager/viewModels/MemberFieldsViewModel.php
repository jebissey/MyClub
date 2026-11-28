<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MemberFieldsViewModel extends LayoutViewModel
{
    /**
     * @param list<array{key: string, label: string, type: string}> $fields
     * @param list<array{value: string, labelKey: string}> $types
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $fields,
        public array $types,
        public string $layout,
        array $layoutParams = [],
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/personManager',
            btnPlus: false,
        );
    }
}
