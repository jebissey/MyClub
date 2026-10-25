<?php

declare(strict_types=1);

namespace app\modules\User\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;
use app\modules\User\valueObjects\SidebarMenuChild;

final readonly class SidebarMenuItem extends AbstractValueObject
{
    /**
     * @param list<SidebarMenuChild> $children
     */
    public function __construct(
        public string $type,
        public ?string $label = null,
        public ?string $icon = null,
        public ?string $url = null,
        public array $children = [],
    ) {
    }
}
