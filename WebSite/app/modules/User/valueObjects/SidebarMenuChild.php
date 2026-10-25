<?php

declare(strict_types=1);

namespace app\modules\User\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class SidebarMenuChild extends AbstractValueObject
{
    public function __construct(
        public string $label,
        public ?string $url,
    ) {
    }
}
