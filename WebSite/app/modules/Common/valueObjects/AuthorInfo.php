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
            firstName: self::stringOrNull($row->FirstName ?? null),
            lastName: self::stringOrNull($row->LastName ?? null),
            nickName: self::stringOrNull($row->NickName ?? null),
            displayName: self::getDisplayName($row),
        );
    }

    private static function getDisplayName(object $row): string
    {
        $firstName = self::stringOrNull($row->FirstName ?? null) ?? '';
        $lastName  = self::stringOrNull($row->LastName ?? null) ?? '';
        $nickName  = self::stringOrNull($row->NickName ?? null);

        $name = trim($firstName . ' ' . $lastName);

        if ($nickName !== null && $nickName !== '') {
            $name .= ' (' . $nickName . ')';
        }

        return $name;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
