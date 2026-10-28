<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class MenuItemAuthorizationRow
{
    public function __construct(
        public ?int $idGroup,
        public int $forMembers,
        public int $forAnonymous,
        public ?int $groupId,
    ) {
    }

    /**
     * @param object{IdGroup: int|string|null, ForMembers: int|string, ForAnonymous: int|string, groupId: int|string|null} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            idGroup: $row->IdGroup !== null ? (int)$row->IdGroup : null,
            forMembers: (int)$row->ForMembers,
            forAnonymous: (int)$row->ForAnonymous,
            groupId: $row->groupId !== null ? (int)$row->groupId : null,
        );
    }
}
