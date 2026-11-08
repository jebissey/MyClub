<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\valueObjects\EventAttributeRow;
use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class EventTypeEditViewModel extends LayoutViewModel
{
    /**
     * @param list<IdNameRow> $groups
     * @param list<EventAttributeRow> $attributes
     * @param list<int> $existingAttributesIds
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $name,
        public ?int $idGroup,
        public array $groups,
        public array $attributes,
        public array $existingAttributesIds,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
