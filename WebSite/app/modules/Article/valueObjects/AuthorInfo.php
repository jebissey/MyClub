<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

final readonly class AuthorInfo
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
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
        );
    }

    public function displayName(): string
    {
        $name = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
        if (!empty($this->nickName)) {
            $name .= ' (' . $this->nickName . ')';
        }
        return $name;
    }
}
