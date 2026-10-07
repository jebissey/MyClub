<?php

declare(strict_types=1);

namespace test\CodingStandards\Analysis;

/**
 * Determines whether the public methods of a class end with an accepted
 * terminal call: render(...), a redirect, a file/stream send, a raise(), or
 * a direct echo.
 */
final class TerminalCallAnalyzer
{
    // Each word is a PREFIX: "render" also matches renderInfo(), "raise" also
    // matches raiseBadRequest()/raiseForbidden(), etc.
    private const TERMINAL_CALL_BASE_WORDS = [
        'render',
        'redirect',
        'readfile',
        'fpassthru',
        'sendFile',
        'download',
        'stream',
        'raise',
    ];

    /**
     * Finds every public method and checks whether its last top-level
     * statement (token-wise, so if/else and try/catch blocks are respected)
     * ends with an accepted terminal call.
     *
     * @return list<array{name: string, endsWithAcceptedTerminalCall: bool}>
     */
    public function analyzePublicMethods(string $sourceCode): array
    {
        $tokens = token_get_all($sourceCode);
        $count = count($tokens);
        $results = [];

        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_PUBLIC) {
                continue;
            }

            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && in_array(
                $tokens[$j][0],
                [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_STATIC, T_ABSTRACT, T_FINAL],
                true,
            )) {
                $j++;
            }

            if (!is_array($tokens[$j] ?? null) || $tokens[$j][0] !== T_FUNCTION) {
                continue;
            }

            $j = TokenHelper::skipTrivia($tokens, $j + 1, $count);

            $nameToken = $tokens[$j] ?? null;
            if (!is_array($nameToken) || $nameToken[0] !== T_STRING) {
                continue;
            }

            $methodName = $nameToken[1];
            $j++;

            // Skip to the opening parenthesis of the parameter list.
            while ($j < $count && !(!is_array($tokens[$j]) && $tokens[$j] === '(')) {
                $j++;
            }
            if ($j >= $count) {
                continue;
            }

            // Balance parentheses to skip the whole parameter list (default
            // values may contain parentheses themselves).
            $parenDepth = 0;
            for (; $j < $count; $j++) {
                $t = $tokens[$j];
                if (!is_array($t) && $t === '(') {
                    $parenDepth++;
                } elseif (!is_array($t) && $t === ')') {
                    $parenDepth--;
                    if ($parenDepth === 0) {
                        $j++;
                        break;
                    }
                }
            }

            // Skip the optional return type up to '{' (body) or ';' (abstract/interface).
            $bodyStart = null;
            for (; $j < $count; $j++) {
                $t = $tokens[$j];
                if (TokenHelper::isOpenBrace($t)) {
                    $bodyStart = $j;
                    break;
                }
                if (!is_array($t) && $t === ';') {
                    break;
                }
            }

            if ($bodyStart === null) {
                continue;
            }

            $braceDepth = 1;
            $bodyTokens = [];
            $k = $bodyStart + 1;
            for (; $k < $count; $k++) {
                $t = $tokens[$k];
                if (TokenHelper::isOpenBrace($t)) {
                    $braceDepth++;
                } elseif (TokenHelper::isCloseBrace($t)) {
                    $braceDepth--;
                    if ($braceDepth === 0) {
                        break;
                    }
                }
                $bodyTokens[] = $t;
            }

            $results[] = [
                'name' => $methodName,
                'endsWithAcceptedTerminalCall' => $this->blockEndsAcceptably($bodyTokens),
            ];

            $i = $k;
        }

        return $results;
    }

    /**
     * @param list<mixed> $blockTokens
     */
    private function blockEndsAcceptably(array $blockTokens): bool
    {
        $statements = $this->splitTopLevelStatements($blockTokens);
        $last = end($statements);

        return $last !== false && $this->statementEndsAcceptably($last);
    }

    /**
     * @param list<mixed> $tokens
     * @return list<list<mixed>>
     */
    private function splitTopLevelStatements(array $tokens): array
    {
        $statements = [];
        $current = [];
        $braceDepth = 0;
        $parenDepth = 0;
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            $current[] = $t;

            if (TokenHelper::isOpenBrace($t)) {
                $braceDepth++;
            } elseif (TokenHelper::isCloseBrace($t)) {
                $braceDepth--;
                if ($braceDepth === 0 && $parenDepth === 0) {
                    // Do not close the statement if else/elseif/catch/finally
                    // follows: this '}' only ends one branch of the if/try.
                    if ($this->nextMeaningfulIsContinuation($tokens, $i + 1, $count)) {
                        continue;
                    }
                    $statements[] = $current;
                    $current = [];
                }
            } elseif (!is_array($t) && $t === '(') {
                $parenDepth++;
            } elseif (!is_array($t) && $t === ')') {
                $parenDepth--;
            } elseif (!is_array($t) && $t === ';' && $braceDepth === 0 && $parenDepth === 0) {
                $statements[] = $current;
                $current = [];
            }
        }

        if (TokenHelper::containsNonWhitespace($current)) {
            $statements[] = $current;
        }

        return $statements;
    }

    /**
     * @param list<mixed> $tokens
     */
    private function nextMeaningfulIsContinuation(array $tokens, int $i, int $count): bool
    {
        $i = TokenHelper::skipTrivia($tokens, $i, $count);
        $token = $tokens[$i] ?? null;

        if (!is_array($token)) {
            return false;
        }

        return in_array($token[0], [T_ELSE, T_ELSEIF, T_CATCH, T_FINALLY], true);
    }

    /**
     * An "if" without "else" is treated as a legitimate guard clause (failure
     * is assumed to be handled elsewhere, e.g. via raise() in a method called
     * in the condition): only the if branch is checked. When an "else" or
     * "elseif" exists, every present branch must end properly.
     *
     * @param list<mixed> $statementTokens
     */
    private function statementEndsAcceptably(array $statementTokens): bool
    {
        $firstIndex = TokenHelper::firstMeaningfulIndex($statementTokens);

        if ($firstIndex !== null && is_array($statementTokens[$firstIndex])) {
            if ($statementTokens[$firstIndex][0] === T_IF) {
                return $this->ifChainEndsAcceptably($statementTokens, $firstIndex);
            }

            if ($statementTokens[$firstIndex][0] === T_TRY) {
                return $this->tryChainEndsAcceptably($statementTokens, $firstIndex);
            }
        }

        $text = TokenHelper::toText($statementTokens);

        if ($this->matchesTerminalCall($text)) {
            return true;
        }

        // Redirect through a raw header() call instead of a dedicated wrapper.
        if (str_contains($text, 'header(') && stripos($text, 'Location') !== false) {
            return true;
        }

        // Direct echo of a hand-built stream (RSS, sitemap, etc.).
        if ($this->containsEchoToken($statementTokens)) {
            return true;
        }

        // Pure delegation to another method of the same class: the target
        // method is responsible for ending properly and is checked separately
        // when it is public.
        return $this->isSelfDelegatingCall(trim($text));
    }

    /**
     * @param list<mixed> $tokens
     */
    private function ifChainEndsAcceptably(array $tokens, int $ifIndex): bool
    {
        $count = count($tokens);
        $i = $ifIndex;

        while (true) {
            // $tokens[$i] is T_IF or T_ELSEIF here.
            $i = TokenHelper::skipTrivia($tokens, $i + 1, $count);

            if (!(!is_array($tokens[$i] ?? null) && ($tokens[$i] ?? null) === '(')) {
                return false; // Unexpected structure: stay cautious.
            }

            $i = $this->skipParenthesized($tokens, $i, $count);
            $i = TokenHelper::skipTrivia($tokens, $i, $count);

            [$bodyTokens, $i] = $this->extractBody($tokens, $i, $count);

            if (!$this->blockEndsAcceptably($bodyTokens)) {
                return false;
            }

            $i = TokenHelper::skipTrivia($tokens, $i, $count);
            $token = $tokens[$i] ?? null;

            if (is_array($token) && $token[0] === T_ELSEIF) {
                continue;
            }

            if (is_array($token) && $token[0] === T_ELSE) {
                $i = TokenHelper::skipTrivia($tokens, $i + 1, $count);
                $next = $tokens[$i] ?? null;

                if (is_array($next) && $next[0] === T_IF) {
                    // "else if" written as two words: loop like an elseif.
                    continue;
                }

                [$elseBodyTokens] = $this->extractBody($tokens, $i, $count);

                return $this->blockEndsAcceptably($elseBodyTokens);
            }

            // No else: guard clause accepted without requiring a final branch.
            return true;
        }
    }

    /**
     * Same logic as if/elseif/else: every present branch (try, each catch)
     * must end properly. When a finally exists it governs the real end of
     * the statement (it always runs after try/catch), so only it is checked.
     *
     * @param list<mixed> $tokens
     */
    private function tryChainEndsAcceptably(array $tokens, int $tryIndex): bool
    {
        $count = count($tokens);
        $i = TokenHelper::skipTrivia($tokens, $tryIndex + 1, $count);

        [$tryBody, $i] = $this->extractBody($tokens, $i, $count);
        $tryOk = $this->blockEndsAcceptably($tryBody);

        $i = TokenHelper::skipTrivia($tokens, $i, $count);
        $catchOk = true;

        while (is_array($tokens[$i] ?? null) && $tokens[$i][0] === T_CATCH) {
            $i = TokenHelper::skipTrivia($tokens, $i + 1, $count);

            if (!(!is_array($tokens[$i] ?? null) && ($tokens[$i] ?? null) === '(')) {
                return false; // Unexpected structure: stay cautious.
            }

            $i = $this->skipParenthesized($tokens, $i, $count);
            $i = TokenHelper::skipTrivia($tokens, $i, $count);

            [$catchBody, $i] = $this->extractBody($tokens, $i, $count);
            if (!$this->blockEndsAcceptably($catchBody)) {
                $catchOk = false;
            }

            $i = TokenHelper::skipTrivia($tokens, $i, $count);
        }

        if (is_array($tokens[$i] ?? null) && $tokens[$i][0] === T_FINALLY) {
            $i = TokenHelper::skipTrivia($tokens, $i + 1, $count);
            [$finallyBody] = $this->extractBody($tokens, $i, $count);

            return $this->blockEndsAcceptably($finallyBody);
        }

        return $tryOk && $catchOk;
    }

    /**
     * Returns the index just after the parenthesis group starting at $i.
     *
     * @param list<mixed> $tokens
     */
    private function skipParenthesized(array $tokens, int $i, int $count): int
    {
        $parenDepth = 0;

        for (; $i < $count; $i++) {
            $t = $tokens[$i];
            if (!is_array($t) && $t === '(') {
                $parenDepth++;
            } elseif (!is_array($t) && $t === ')') {
                $parenDepth--;
                if ($parenDepth === 0) {
                    return $i + 1;
                }
            }
        }

        return $i;
    }

    /**
     * @param list<mixed> $tokens
     * @return array{0: list<mixed>, 1: int}
     */
    private function extractBody(array $tokens, int $i, int $count): array
    {
        if (TokenHelper::isOpenBrace($tokens[$i] ?? null)) {
            $depth = 1;
            $body = [];
            $i++;
            for (; $i < $count; $i++) {
                $t = $tokens[$i];
                if (TokenHelper::isOpenBrace($t)) {
                    $depth++;
                } elseif (TokenHelper::isCloseBrace($t)) {
                    $depth--;
                    if ($depth === 0) {
                        $i++;
                        break;
                    }
                }
                $body[] = $t;
            }

            return [$body, $i];
        }

        // Brace-less body (rare with PSR-12, but handled anyway).
        $body = [];
        $parenDepth = 0;
        for (; $i < $count; $i++) {
            $t = $tokens[$i];
            $body[] = $t;
            if (!is_array($t) && $t === '(') {
                $parenDepth++;
            } elseif (!is_array($t) && $t === ')') {
                $parenDepth--;
            } elseif (!is_array($t) && $t === ';' && $parenDepth === 0) {
                $i++;
                break;
            }
        }

        return [$body, $i];
    }

    private function isSelfDelegatingCall(string $text): bool
    {
        return (bool) preg_match('/^\$this\s*->\s*\w+\s*\(.*\)\s*;?\s*$/s', $text);
    }

    private function matchesTerminalCall(string $text): bool
    {
        $alternation = implode('|', array_map(
            static fn(string $w): string => preg_quote($w, '/'),
            self::TERMINAL_CALL_BASE_WORDS,
        ));

        return (bool) preg_match('/\b(?:' . $alternation . ')\w*\s*\(/', $text);
    }

    /**
     * @param list<mixed> $tokens
     */
    private function containsEchoToken(array $tokens): bool
    {
        foreach ($tokens as $t) {
            if (is_array($t) && $t[0] === T_ECHO) {
                return true;
            }
        }

        return false;
    }
}