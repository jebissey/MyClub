<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class AuthorInfo
{
    public function __construct(
        public ?string $FirstName,
        public ?string $LastName,
        public ?string $NickName = null,
    ) {
    }

    public static function fromStdClass(object $row): ?self
    {
        if (!isset($row->FirstName) && !isset($row->LastName)) {
            return null;
        }
        return new self(
            FirstName: $row->FirstName ?? null,
            LastName: $row->LastName ?? null,
            NickName: $row->NickName ?? null,
        );
    }

    public function displayName(): string
    {
        $name = trim(($this->FirstName ?? '') . ' ' . ($this->LastName ?? ''));
        if (!empty($this->NickName)) {
            $name .= ' (' . $this->NickName . ')';
        }
        return $name;
    }
}
