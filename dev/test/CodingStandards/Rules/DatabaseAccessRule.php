<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use PhpToken;
use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

/**
 * Database access (PDO calls and raw SQL) is only allowed in DataHelpers, the base Data class and migrators.
 */
final readonly class DatabaseAccessRule implements RuleInterface
{
    private const SQL_PATTERN = '/^\s*(SELECT\s.+\sFROM\s|INSERT\s+(OR\s+\w+\s+)?INTO\s|UPDATE\s+\S+\s+SET\s|DELETE\s+FROM\s)/is';
    private const PDO_METHODS = ['prepare', 'query', 'exec'];

    /** Variable names that are considered PDO handles in this project */
    private const PDO_RECEIVERS = ['pdo', 'this->pdo',];

    public function __construct(private SourcePaths $paths) {}

    public function label(): string
    {
        return 'Database access outside DataHelpers';
    }

    /**
     * @param list<ClassInfo> $classes
     * @return list<string>
     */
    public function check(array $classes): array
    {
        $issues = [];

        foreach ($classes as $class) {
            if ($this->isAllowed($class)) {
                continue;
            }
            foreach ($this->findViolations($class) as $line => $reason) {
                $issues[] = sprintf('%s:%d %s', $this->paths->rel($class), $line, $reason);
            }
        }

        return $issues;
    }

    private function isAllowed(ClassInfo $class): bool
    {
        $short = $this->shortName($class->className);

        return $short === 'Data'
            || $short === 'Database'                       // models/Database.php
            || $short === 'Application'                    // helpers/Application.php
            || $short === 'DatabaseMigratorInterface'      // modules/Common/interfaces/...
            || ($class->extends !== null && $this->shortName($class->extends) === 'Data')
            || $this->paths->inSubpath($class, SourcePaths::MIGRATORS);
    }

    private function shortName(string $name): string
    {
        $position = strrpos($name, '\\');

        return $position === false ? $name : substr($name, $position + 1);
    }

    /**
     * @return array<int, string> reason indexed by line number
     */
    private function findViolations(ClassInfo $class): array
    {
        // Tokenizing skips comments and docblocks, which avoids false positives
        $tokens = array_values(PhpToken::tokenize($class->sourceCode));
        $violations = [];

        foreach ($tokens as $i => $token) {
            // Direct PDO type usage
            if (
                $token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])
                && ltrim($token->text, '\\') === 'PDO'
            ) {
                $violations[$token->line] = 'direct PDO usage';
            }

            // Method calls: only when the receiver looks like a PDO handle
            if ($token->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])) {
                $method = $this->nextSignificant($tokens, $i);
                $paren  = $method === null ? null : $this->nextSignificant($tokens, $method);

                if (
                    $method !== null && $paren !== null
                    && in_array($tokens[$method]->text, self::PDO_METHODS, true)
                    && $tokens[$paren]->text === '('
                    && $this->isLikelyPdoReceiver($tokens, $i)
                ) {
                    $violations[$token->line] = sprintf('call to ->%s()', $tokens[$method]->text);
                }
            }

            // Raw SQL strings
            if (
                $token->is([T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE])
                && preg_match(self::SQL_PATTERN, trim($token->text, '\'"')) === 1
            ) {
                $violations[$token->line] = 'raw SQL statement';
            }
        }

        return $violations;
    }

    /**
     * Returns true when the expression before the -> is a known PDO variable.
     *
     * Handles:
     *   $pdo->query(...)
     *   $this->pdo->query(...)
     *   $db->query(...)
     *   etc.
     *
     * @param list<PhpToken> $tokens
     */
    private function isLikelyPdoReceiver(array $tokens, int $arrowIndex): bool
    {
        $prev = $this->previousSignificant($tokens, $arrowIndex);
        if ($prev === null) {
            return false;
        }

        $text = $tokens[$prev]->text;

        // $pdo, $db, $database, ...
        if ($tokens[$prev]->is(T_VARIABLE)) {
            $name = ltrim($text, '$');
            return in_array(strtolower($name), self::PDO_RECEIVERS, true);
        }

        // $this->pdo  (we are standing on the second ->)
        // tokens look like: $this  ->  pdo  ->  query
        if (
            $tokens[$prev]->is(T_STRING) && strtolower($text) === 'pdo'
            || in_array(strtolower($text), ['db', 'database', 'connection', 'conn'], true)
        ) {
            // look further left for $this
            $prev2 = $this->previousSignificant($tokens, $prev);
            if ($prev2 !== null && $tokens[$prev2]->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])) {
                $prev3 = $this->previousSignificant($tokens, $prev2);
                if ($prev3 !== null && $tokens[$prev3]->is(T_VARIABLE) && $tokens[$prev3]->text === '$this') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function nextSignificant(array $tokens, int $index): ?int
    {
        for ($j = $index + 1, $n = count($tokens); $j < $n; $j++) {
            if (!$tokens[$j]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                return $j;
            }
        }

        return null;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function previousSignificant(array $tokens, int $index): ?int
    {
        for ($j = $index - 1; $j >= 0; $j--) {
            if (!$tokens[$j]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                return $j;
            }
        }

        return null;
    }
}
