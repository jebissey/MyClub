<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class MenuItemAuthorizationRow
{
    public function __construct(
        public ?int $IdGroup,
        public int $ForMembers,
        public int $ForAnonymous,
        public ?int $groupId,
    ) {
    }

    /**
     * @param object{IdGroup: int|string|null, ForMembers: int|string, ForAnonymous: int|string, groupId: int|string|null} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            IdGroup: $row->IdGroup !== null ? (int)$row->IdGroup : null,
            ForMembers: (int)$row->ForMembers,
            ForAnonymous: (int)$row->ForAnonymous,
            groupId: $row->groupId !== null ? (int)$row->groupId : null,
        );
    }
}
