<?php

declare(strict_types=1);

namespace app\helpers;

use RuntimeException;

final class To
{
    public static function bool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return $default;
    }

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
        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * SUM() returns an int or a float depending on the stored values.
     * Keeping the original type avoids turning counters into floats.
     */
    public static function number(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return is_string($value) && is_numeric($value) ? $value + 0 : 0;
    }


    /**
     * Strict variants: throw instead of returning a default, for data
     * that must be valid (database rows, identifiers, dates).
     */
    public static function requireInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        throw new RuntimeException('Expected int, got ' . get_debug_type($value));
    }

    public static function requireStr(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        throw new RuntimeException('Expected string, got ' . get_debug_type($value));
    }

    public static function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : self::requireInt($value);
    }

    public static function nullableStr(mixed $value): ?string
    {
        return $value === null ? null : self::requireStr($value);
    }
}
