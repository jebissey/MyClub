<?php

declare(strict_types=1);

namespace app\helpers;

use RuntimeException;

class To
{
    public static function float(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }
        throw new RuntimeException('Expected float, got ' . get_debug_type($value));
    }

    public static function int(mixed $value, int $default = 0): int
    {
        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : $default);
    }

    public static function str(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string)$value : $default;
    }
}
