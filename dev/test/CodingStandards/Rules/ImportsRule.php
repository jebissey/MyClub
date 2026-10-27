<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class ImportsRule implements RuleInterface
{
    public function __construct(private SourcePaths $paths)
    {
    }

    public function label(): string
    {
        return 'Fully qualified names used instead of a "use" import';
    }

    public function check(array $classes): array
    {
        $issues = [];

        foreach ($classes as $class) {
            $where = "{$class->className} ({$this->paths->rel($class)})";

            foreach ($this->fullyQualifiedUsages($class) as $name => $line) {
                $issues[] = "{$where} line {$line}: {$name} must be imported with 'use' instead of being written inline";
            }
        }

        return $issues;
    }

    /**
     * @return array<string, int> fully qualified name => line of its first occurrence
     */
    private function fullyQualifiedUsages(ClassInfo $class): array
    {
        $tokens = token_get_all($class->sourceCode);
        $count = count($tokens);
        $found = [];
        $insideUse = false;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                // A ";" closes any pending top-level "use" statement.
                if ($token === ';') {
                    $insideUse = false;
                }
                continue;
            }

            [$id, $text, $line] = $token;

            if ($id === T_USE) {
                $insideUse = true;
                continue;
            }

            if ($id !== T_NAME_FULLY_QUALIFIED) {
                continue;
            }

            // Names inside "use" statements are the import itself.
            if ($insideUse) {
                continue;
            }

            if ($this->isAllowed($text, $tokens, $i)) {
                continue;
            }

            $found[$text] ??= $line;
        }

        return $found;
    }

    /**
     * @param array<int, array{0:int,1:string,2:int}|string> $tokens
     */
    private function isAllowed(string $name, array $tokens, int $index): bool
    {
        $isGlobal = substr_count($name, '\\') === 1;

        if (!$isGlobal) {
            return false;
        }

        // Global constants such as \PHP_EOL are tolerated.
        if ($name === strtoupper($name)) {
            return true;
        }

        // Global function calls such as \count() are tolerated,
        // but "new \Foo()" is a class reference and must be imported.
        $previous = $this->neighbour($tokens, $index, -1);
        $next = $this->neighbour($tokens, $index, 1);

        $isNew = is_array($previous) && $previous[0] === T_NEW;

        return $next === '(' && !$isNew;
    }

    /**
     * @param array<int, array{0:int,1:string,2:int}|string> $tokens
     * @return array{0:int,1:string,2:int}|string|null
     */
    private function neighbour(array $tokens, int $index, int $step): array|string|null
    {
        for ($i = $index + $step; isset($tokens[$i]); $i += $step) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token;
        }

        return null;
    }
}