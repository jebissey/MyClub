<?php

declare(strict_types=1);

namespace test\CodingStandards\Analysis;

/**
 * @phpstan-import-type PropertyData from PropertyAnalysis
 */
final class PropertyAnalyzer
{
    public function analyze(string $sourceCode): PropertyAnalysis
    {
        $tokens = token_get_all($sourceCode);
        $properties = $this->collectProperties($tokens);

        if ($properties === []) {
            return new PropertyAnalysis([], '', '', false);
        }

        [, $tokensOutsideConstructor] = $this->splitConstructor($tokens);
        $fullCode = TokenHelper::toCode($tokens);

        // Conservative: "with*" methods that clone $this and tweak the clone
        // make any reassignment analysis unreliable.
        $usesClone = preg_match('/\bclone\s*\(?\s*\$this\b/', $fullCode) === 1;

        return new PropertyAnalysis(
            $properties,
            $fullCode,
            TokenHelper::toCode($tokensOutsideConstructor),
            $usesClone,
        );
    }

    public function isPropertyReassigned(string $code, string $name): bool
    {
        $prop = '\$this\s*->\s*' . preg_quote($name, '/') . '\b';

        $patterns = [
            // $this->p = ..., $this->p .= ..., $this->p[] = ..., $this->p['k'] ??= ...
            '/' . $prop . '\s*(?:\[[^\]]*\]\s*)*(?:=(?![=>])|[-+*\/.%|&^]=|\*\*=|\?\?=|<<=|>>=)/',
            // ++$this->p, $this->p--
            '/(?:\+\+|--)\s*' . $prop . '/',
            '/' . $prop . '\s*(?:\+\+|--)/',
            // unset($this->p)
            '/\bunset\s*\(\s*' . $prop . '/',
            // &$this->p (reference), without matching "&&"
            '/(?<!&)&\s*' . $prop . '/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $code) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lists the properties declared at class level, including constructor
     * promoted ones.
     *
     * @param list<mixed> $tokens
     * @return list<PropertyData>
     */
    private function collectProperties(array $tokens): array
    {
        $properties = [];
        $count = count($tokens);
        $braceDepth = 0;
        $parenDepth = 0;
        $modifierTokens = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_READONLY, T_STATIC, T_FINAL, T_ABSTRACT];
        $trivia = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];

            if (TokenHelper::isOpenBrace($t)) {
                $braceDepth++;
                continue;
            }
            if (TokenHelper::isCloseBrace($t)) {
                $braceDepth--;
                continue;
            }
            if (!is_array($t)) {
                if ($t === '(') {
                    $parenDepth++;
                } elseif ($t === ')') {
                    $parenDepth--;
                }
                continue;
            }

            // Members live at depth 1 (inside the class body, outside method bodies).
            if ($braceDepth !== 1 || !in_array($t[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_READONLY], true)) {
                continue;
            }

            $visibility = 'public';
            $readonly = false;
            $static = false;
            $j = $i;

            while ($j < $count && is_array($tokens[$j]) && in_array($tokens[$j][0], [...$modifierTokens, ...$trivia], true)) {
                match ($tokens[$j][0]) {
                    T_PRIVATE => $visibility = 'private',
                    T_PROTECTED => $visibility = 'protected',
                    T_READONLY => $readonly = true,
                    T_STATIC => $static = true,
                    default => null,
                };
                $j++;
            }

            // Walk the type (if any) up to the variable; bail out on methods/constants.
            $typed = false;
            $isProperty = true;
            for (; $j < $count; $j++) {
                $u = $tokens[$j];

                if (is_array($u)) {
                    if ($u[0] === T_VARIABLE) {
                        break;
                    }
                    if (in_array($u[0], [T_FUNCTION, T_CONST], true)) {
                        $isProperty = false;
                        break;
                    }
                    if (!in_array($u[0], $trivia, true)) {
                        $typed = true;
                    }
                    continue;
                }

                if ($u === '?' || $u === '|') {
                    $typed = true;
                } elseif ($u !== '&') {
                    $isProperty = false;
                    break;
                }
            }

            if (!$isProperty || $j >= $count) {
                $i = max($i, $j);
                continue;
            }

            $variableToken = $tokens[$j];
            if (!is_array($variableToken) || !is_string($variableToken[1]) || !is_int($variableToken[2])) {
                $i = $j;
                continue;
            }

            $promoted = $parenDepth > 0;
            $hasDefault = false;

            if (!$promoted) {
                // For promoted properties the default belongs to the parameter,
                // not to the property, so only declared properties are checked.
                $k = TokenHelper::skipTrivia($tokens, $j + 1, $count);
                $hasDefault = ($tokens[$k] ?? null) !== ';';
            }

            $properties[] = [
                'name' => ltrim($variableToken[1], '$'),
                'line' => $variableToken[2],
                'visibility' => $visibility,
                'readonly' => $readonly,
                'static' => $static,
                'typed' => $typed,
                'promoted' => $promoted,
                'hasDefault' => $hasDefault,
            ];

            $i = $j;
        }

        return $properties;
    }

    /**
     * Splits the tokens into the constructor (signature + body) and everything else.
     *
     * @param list<mixed> $tokens
     * @return array{0: list<mixed>, 1: list<mixed>}
     */
    private function splitConstructor(array $tokens): array
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
                continue;
            }

            $j = TokenHelper::skipTrivia($tokens, $i + 1, $count);
            $nameToken = $tokens[$j] ?? null;
            if (
                !is_array($nameToken)
                || $nameToken[0] !== T_STRING
                || !is_string($nameToken[1])
                || strtolower($nameToken[1]) !== '__construct'
            ) {
                continue;
            }

            $parenDepth = 0;
            $bodyStart = null;
            for (; $j < $count; $j++) {
                $t = $tokens[$j];
                if ($t === '(') {
                    $parenDepth++;
                } elseif ($t === ')') {
                    $parenDepth--;
                } elseif ($parenDepth === 0 && TokenHelper::isOpenBrace($t)) {
                    $bodyStart = $j;
                    break;
                } elseif ($parenDepth === 0 && $t === ';') {
                    break;
                }
            }

            if ($bodyStart === null) {
                return [[], $tokens];
            }

            $depth = 0;
            $end = $bodyStart;
            for ($k = $bodyStart; $k < $count; $k++) {
                if (TokenHelper::isOpenBrace($tokens[$k])) {
                    $depth++;
                } elseif (TokenHelper::isCloseBrace($tokens[$k])) {
                    $depth--;
                    if ($depth === 0) {
                        $end = $k;
                        break;
                    }
                }
            }

            return [
                array_slice($tokens, $i, $end - $i + 1),
                array_merge(array_slice($tokens, 0, $i), array_slice($tokens, $end + 1)),
            ];
        }

        return [[], $tokens];
    }
}