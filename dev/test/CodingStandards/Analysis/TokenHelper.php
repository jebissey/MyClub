<?php

declare(strict_types=1);

namespace test\CodingStandards\Analysis;

/**
 * Pure helpers over the output of token_get_all().
 */
final class TokenHelper
{
    private const TRIVIA = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

    /**
     * @param list<mixed> $tokens
     */
    public static function skipTrivia(array $tokens, int $i, int $count): int
    {
        while ($i < $count && is_array($tokens[$i] ?? null) && in_array($tokens[$i][0], self::TRIVIA, true)) {
            $i++;
        }

        return $i;
    }

    /**
     * @param list<mixed> $tokens
     */
    public static function firstMeaningfulIndex(array $tokens): ?int
    {
        foreach ($tokens as $idx => $t) {
            if (is_array($t) && in_array($t[0], self::TRIVIA, true)) {
                continue;
            }

            return $idx;
        }

        return null;
    }

    /**
     * @param list<mixed> $tokens
     */
    public static function containsNonWhitespace(array $tokens): bool
    {
        return self::firstMeaningfulIndex($tokens) !== null;
    }

    public static function isOpenBrace(mixed $token): bool
    {
        if (!is_array($token)) {
            return $token === '{';
        }

        return $token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES;
    }

    public static function isCloseBrace(mixed $token): bool
    {
        return !is_array($token) && $token === '}';
    }

    /**
     * @param list<mixed> $tokens
     */
    public static function toText(array $tokens): string
    {
        $text = '';

        foreach ($tokens as $t) {
            if (is_array($t)) {
                $piece = $t[1] ?? null;
                if (is_string($piece)) {
                    $text .= $piece;
                }
                continue;
            }

            if (is_string($t)) {
                $text .= $t;
            }
        }

        return $text;
    }

    /**
     * Same as toText() but without comments, so that commented-out code
     * does not trigger false positives.
     *
     * @param list<mixed> $tokens
     */
    public static function toCode(array $tokens): string
    {
        return self::toText(array_values(array_filter(
            $tokens,
            static fn(mixed $t): bool => !is_array($t) || !in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true),
        )));
    }
}