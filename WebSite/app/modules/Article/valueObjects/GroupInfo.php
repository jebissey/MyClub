<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

final readonly class GroupInfo
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    public static function fromStdClass(object $row): ?self
    {
        if (!isset($row->IdGroup) || $row->IdGroup == null || !isset($row->GroupName)) {
            return null;
        }
        return new self(
            id: (int)$row->IdGroup,
            name: $row->GroupName,
        );
    }
}
