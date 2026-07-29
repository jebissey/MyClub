<?php

declare(strict_types=1);

namespace app\valueObjects;

abstract readonly class AbstractValueObject
{
    protected static function toInt(int|string|null $value): int
    {
        return (int) ($value ?? 0);
    }

    protected static function toBool(bool|int|string|null $value): bool
    {
        return match ($value) {
            true, 1, '1' => true,
            default => false,
        };
    }

    protected static function toString(?string $value): ?string
    {
        return $value;
    }
}
