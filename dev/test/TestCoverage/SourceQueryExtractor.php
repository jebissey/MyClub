<?php

declare(strict_types=1);

namespace test\TestCoverage;

/**
 * Statically extracts "table => columns" pairs from calls such as
 * $this->dataHelper->gets('Table', [...conditions...], 'Col1, Col2', 'OrderCol').
 *
 * Only literal table names and literal columns are handled; anything
 * dynamic (variables, computed keys) is ignored on purpose.
 */
final class SourceQueryExtractor
{
    private const CALL_PATTERN = '/\$this->(?:dataHelper->)?(?:get|gets|set)\s*\(/';
    private const STRING_LITERAL = '/(["\'])((?:\\\\.|(?!\1).)*)\1/s';
    private const OPERATOR_PATTERN =
        '/"?(?:\w+\.)?([A-Za-z_]\w*)"?\s*(?:=|!=|<>|<=|>=|<|>|\bIS\b|\bLIKE\b|\bIN\b|\bBETWEEN\b|\bNOT\b)/i';

    /** @return array<string, list<string>> table => distinct columns */
    public function extract(string $source): array
    {
        $result = [];
        if (preg_match_all(self::CALL_PATTERN, $source, $matches, PREG_OFFSET_CAPTURE) === 0) {
            return $result;
        }

        foreach ($matches[0] as [$text, $offset]) {
            $args = $this->splitArguments($source, $offset + strlen($text));
            if ($args === []) {
                continue;
            }
            if (preg_match('/^(["\'])(\w+)\1$/', trim($args[0]), $table) !== 1) {
                continue; // dynamic table name
            }
            $tableName = $table[2];
            $result[$tableName] ??= [];

            foreach (array_slice($args, 1) as $arg) {
                foreach ($this->columnsFromArgument(trim($arg)) as $column) {
                    $result[$tableName][$column] = $column;
                }
            }
        }

        return array_map(array_values(...), $result);
    }

    /** @return list<string> */
    private function columnsFromArgument(string $arg): array
    {
        if ($arg === '' || $arg[0] === '$') {
            return [];
        }

        $columns = [];
        if ($arg[0] === '[') {
            // Array argument: the string keys are column names / SQL conditions.
            preg_match_all('/(["\'])((?:\\\\.|(?!\1).)*)\1\s*=>/s', $arg, $keys);
            foreach ($keys[2] as $key) {
                array_push($columns, ...$this->columnsFromCondition($key));
            }
            return $columns;
        }

        // String argument (possibly concatenated): comma-separated column list.
        if (preg_match_all(self::STRING_LITERAL, $arg, $literals) === 0) {
            return [];
        }
        foreach (explode(',', implode('', $literals[2])) as $piece) {
            if (str_contains($piece, '(') || trim($piece) === '*') {
                continue; // SQL function or wildcard
            }
            if (preg_match('/^\s*"?(?:\w+\.)?(\w+)"?/', $piece, $m) === 1) {
                $columns[] = $m[1];
            }
        }
        return $columns;
    }

    /** @return list<string> */
    private function columnsFromCondition(string $condition): array
    {
        if (preg_match_all(self::OPERATOR_PATTERN, $condition, $m) > 0) {
            return $m[1];
        }
        if (preg_match('/^\s*"?(?:\w+\.)?(\w+)"?\s*$/', $condition, $m) === 1) {
            return [$m[1]];
        }
        return [];
    }

    /** @return list<string> top-level arguments of the call whose "(" precedes $pos */
    private function splitArguments(string $source, int $pos): array
    {
        $args = [];
        $current = '';
        $depth = 0;
        $quote = null;
        $length = strlen($source);

        for ($i = $pos; $i < $length; $i++) {
            $char = $source[$i];

            if ($quote !== null) {
                $current .= $char;
                if ($char === '\\' && $i + 1 < $length) {
                    $current .= $source[++$i];
                } elseif ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
            } elseif ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
            } elseif ($char === ')' || $char === ']' || $char === '}') {
                if ($depth === 0) {
                    $args[] = $current;
                    return $args;
                }
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $args[] = $current;
                $current = '';
                continue;
            }
            $current .= $char;
        }

        return [];
    }
}
