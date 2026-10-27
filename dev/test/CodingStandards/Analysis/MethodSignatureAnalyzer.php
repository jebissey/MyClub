<?php

declare(strict_types=1);

namespace test\CodingStandards\Analysis;

use PhpToken;
use test\CodingStandards\ValueObjects\MethodSignature;
use test\CodingStandards\ValueObjects\ParameterInfo;

final class MethodSignatureAnalyzer
{
    /**
     * Extracts every named function or method (closures are ignored).
     *
     * @return list<MethodSignature>
     */
    public function analyze(string $sourceCode): array
    {
        $tokens = array_values(array_filter(
            PhpToken::tokenize($sourceCode),
            static fn (PhpToken $token): bool => !$token->isIgnorable(),
        ));
        $count = count($tokens);
        $methods = [];

        for ($i = 0; $i < $count; $i++) {
            if (!$tokens[$i]->is(T_FUNCTION)) {
                continue;
            }

            // Skip the optional by-reference marker: "function &name()".
            $nameIndex = $i + 1;
            if (isset($tokens[$nameIndex]) && $tokens[$nameIndex]->is('&')) {
                $nameIndex++;
            }

            // No name means a closure.
            if (!isset($tokens[$nameIndex]) || !$tokens[$nameIndex]->is(T_STRING)) {
                continue;
            }

            $open = $nameIndex + 1;
            if (!isset($tokens[$open]) || !$tokens[$open]->is('(')) {
                continue;
            }

            $close = $this->matchingIndex($tokens, $open);
            if ($close === null) {
                continue;
            }

            // The return type contains no braces, so the first "{" or ";" ends the signature.
            $cursor = $close + 1;
            while ($cursor < $count && !$tokens[$cursor]->is(['{', ';'])) {
                $cursor++;
            }

            $hasBody = $cursor < $count && $tokens[$cursor]->is('{');
            $body = [];

            if ($hasBody) {
                $end = $this->matchingIndex($tokens, $cursor);
                if ($end === null) {
                    continue;
                }
                $body = array_slice($tokens, $cursor + 1, $end - $cursor - 1);
            }

            $parameters = [];
            $groups = $this->splitParameters(array_slice($tokens, $open + 1, $close - $open - 1));
            foreach ($groups as $group) {
                $parameter = $this->buildParameter($group, $body);
                if ($parameter !== null) {
                    $parameters[] = $parameter;
                }
            }

            $methods[] = new MethodSignature($tokens[$nameIndex]->text, $tokens[$i]->line, $hasBody, $parameters);
        }

        return $methods;
    }

    /**
     * @param list<PhpToken> $group Tokens of a single parameter declaration.
     * @param list<PhpToken> $body
     */
    private function buildParameter(array $group, array $body): ?ParameterInfo
    {
        $type = '';
        $name = null;
        $depth = 0;

        foreach ($group as $token) {
            if ($this->opensGroup($token)) {
                $depth++;
                continue;
            }
            if ($this->closesGroup($token)) {
                $depth--;
                continue;
            }
            // Attributes and default values live at depth > 0 or after the variable.
            if ($depth > 0) {
                continue;
            }
            if ($token->is(T_VARIABLE)) {
                $name = ltrim($token->text, '$');
                break;
            }
            if ($token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_ARRAY, T_CALLABLE, T_STATIC, '?', '|'])) {
                $type .= $token->text;
            }
        }

        if ($name === null) {
            return null;
        }

        [$properties, $usedAsWhole] = $this->analyzeUsage($name, $body);

        return new ParameterInfo($name, $type === '' ? null : $type, $properties, $usedAsWhole);
    }

    /**
     * A parameter is "used as a whole" when it appears without a plain property
     * access: passed along, returned, compared, or used for a method call.
     *
     * @param list<PhpToken> $body
     * @return array{0: list<string>, 1: bool}
     */
    private function analyzeUsage(string $name, array $body): array
    {
        $variable = '$' . $name;
        /** @var array<string, true> $properties */
        $properties = [];
        $usedAsWhole = false;

        foreach ($body as $index => $token) {
            if (!$token->is(T_VARIABLE) || $token->text !== $variable) {
                continue;
            }

            $operator = $body[$index + 1] ?? null;
            $member = $body[$index + 2] ?? null;
            $after = $body[$index + 3] ?? null;

            $isMemberAccess = $operator !== null
                && $operator->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])
                && $member !== null
                && $member->is(T_STRING);

            if (!$isMemberAccess || ($after !== null && $after->is('('))) {
                $usedAsWhole = true;
                continue;
            }

            $properties[$member->text] = true;
        }

        return [array_keys($properties), $usedAsWhole];
    }

    /**
     * @param list<PhpToken> $tokens Tokens between the parentheses of a signature.
     * @return list<list<PhpToken>>
     */
    private function splitParameters(array $tokens): array
    {
        $groups = [];
        $current = [];
        $depth = 0;

        foreach ($tokens as $token) {
            if ($this->opensGroup($token)) {
                $depth++;
            } elseif ($this->closesGroup($token)) {
                $depth--;
            }

            if ($depth === 0 && $token->is(',')) {
                $groups[] = $current;
                $current = [];
                continue;
            }

            $current[] = $token;
        }

        if ($current !== []) {
            $groups[] = $current;
        }

        return $groups;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function matchingIndex(array $tokens, int $start): ?int
    {
        $depth = 0;

        for ($i = $start, $count = count($tokens); $i < $count; $i++) {
            if ($this->opensGroup($tokens[$i])) {
                $depth++;
            } elseif ($this->closesGroup($tokens[$i]) && --$depth === 0) {
                return $i;
            }
        }

        return null;
    }

    private function opensGroup(PhpToken $token): bool
    {
        return $token->is(['(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, T_ATTRIBUTE]);
    }

    private function closesGroup(PhpToken $token): bool
    {
        return $token->is([')', ']', '}']);
    }
}
