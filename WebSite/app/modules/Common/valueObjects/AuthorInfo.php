<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class AuthorInfo
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $displayName,
        public ?string $nickName = null,
    ) {
    }

    public static function fromStdClass(object $row): ?self
    {
        if (!isset($row->FirstName) && !isset($row->LastName)) {
            return null;
        }
        return new self(
            firstName: $row->FirstName ?? null,
            lastName: $row->LastName ?? null,
            nickName: $row->NickName ?? null,
            displayName: self::getDisplayName($row),
        );
    }

    private static function getDisplayName(object $row): string
    {
        $name = trim(($row->firstName ?? '') . ' ' . ($row->lastName ?? ''));
        if (!empty($row->nickName)) {
            $name .= ' (' . $row->nickName . ')';
        }
        return $name;
    }
}
