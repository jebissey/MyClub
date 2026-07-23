<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class GroupInfo
{
    public function __construct(
        public int $Id,
        public string $Name,
    ) {
    }

    public static function fromStdClass(object $row): ?self
    {
        if (!isset($row->IdGroup) || $row->IdGroup == null || !isset($row->GroupName)) {
            return null;
        }
        return new self(
            Id: (int)$row->IdGroup,
            Name: $row->GroupName,
        );
    }
}
