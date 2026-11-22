<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class SidebarMenuRow
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $parentId,
        public readonly string $type,
        public readonly string $label,
        public readonly ?string $icon,
        public readonly ?string $url,
        public readonly ?int $groupId,
        public readonly bool $forMembers,
        public readonly bool $forAnonymous
    ) {
    }
}
