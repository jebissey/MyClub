<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class UnusedPublicMembersRule implements RuleInterface
{
    /**
     * Methods called implicitly by PHP through built-in interfaces.
     */
    private const IMPLICIT_METHODS = [
        'jsonserialize', 'getiterator', 'count',
        'offsetexists', 'offsetget', 'offsetset', 'offsetunset',
    ];

    /**
     * Tokens that may sit between "public" and the declared member.
     */
    private const SKIPPABLE = [
        T_WHITESPACE,
        T_STATIC,
        T_FINAL,
        T_ABSTRACT,
        T_READONLY,
        T_STRING,
        T_ARRAY,
        T_CALLABLE,
        T_NS_SEPARATOR,
        T_NAME_QUALIFIED,
        T_NAME_FULLY_QUALIFIED,
        T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
        T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
    ];

    public function __construct(private SourcePaths $paths)
    {
    }

    public function label(): string
    {
        return 'Public methods/properties never used by another class';
    }

    /**
     * @param list<ClassInfo> $classes
     * @return list<string>
     */
    public function check(array $classes): array
    {
        /** @var array<string, array<string, true>> $usedBy member name => files using it */
        $usedBy = $this->usedByScripts();
        /** @var array<string, true> $routed "ClassName::method" pairs declared in a route */
        $routed = [];
        /** @var array<string, true> $contractNames names declared by interfaces, traits, enums... */
        $contractNames = [];

        // Factory method => class it returns (makeFfaController => FFAController).
        $factoryMap = [];
        foreach ($classes as $class) {
            foreach ($this->factoryReturnTypes($class->sourceCode) as $method => $type) {
                $factoryMap[$method] = $type;
            }
        }

        foreach ($classes as $class) {
            $tokens = token_get_all($class->sourceCode);
            $file = $this->paths->rel($class);

            foreach ($this->usedNames($tokens) as $name) {
                $usedBy[$name][$file] = true;
            }

            foreach ($this->routedMembers($class->sourceCode, $factoryMap) as $key) {
                $routed[$key] = true;
            }

            if ($class->kind !== 'class') {
                // Members declared by non-class types are contracts: implementations
                // are called through the contract, so they are never reported.
                foreach ($this->publicMembers($tokens) as $member) {
                    $contractNames[$member['name']] = true;
                }
            }
        }

        // Classes targeted by at least one route: their methods are reached
        // through routes only, so a name match from another class is meaningless.
        /** @var array<string, true> $routedClasses */
        $routedClasses = [];
        foreach (array_keys($routed) as $key) {
            $routedClasses[explode('::', $key)[0]] = true;
        }

        $issues = [];

        foreach ($classes as $class) {
            if ($class->kind !== 'class') {
                continue;
            }

            $file = $this->paths->rel($class);
            $where = "{$class->className} ({$file})";

            // A class exporting its own properties with get_object_vars($this)
            // feeds them to a consumer outside the analysed code (template engine).
            $exportsProperties = str_contains($class->sourceCode, 'get_object_vars($this)');

            foreach ($this->publicMembers(token_get_all($class->sourceCode)) as $member) {
                $name = $member['name'];

                if (str_starts_with($name, '__') || isset($contractNames[$name])) {
                    continue;
                }

                if ($member['type'] === 'method' && in_array(strtolower($name), self::IMPLICIT_METHODS, true)) {
                    continue;
                }

                if ($exportsProperties && $member['type'] === 'property') {
                    continue;
                }

                // Routed members are matched per class, so two controllers
                // sharing a method name no longer hide each other.
                if (isset($routed["{$class->className}::{$name}"])) {
                    continue;
                }

                // A method of a routed controller/API must be declared in a route.
                // Calls like $this->help() or from a parent do not count: the
                // framework never calls actions by name.
                if ($member['type'] === 'method' && isset($routedClasses[$class->className])) {
                    $issues[] = "{$where} line {$member['line']}: public method '{$name}' is not declared in any route";
                    continue;
                }

                $users = $usedBy[$name] ?? [];
                // Compare by file, not by short class name: two classes in
                // different namespaces can share the same short name.
                unset($users[$file]);

                if ($users === []) {
                    $issues[] = "{$where} line {$member['line']}: public {$member['type']} '{$name}' is never used by another class";
                }
            }
        }

        return $issues;
    }

    /**
     * Collects public methods, public properties and public promoted properties.
     *
     * @param list<array{0:int,1:string,2:int}|string> $tokens
     * @return list<array{name:string, type:string, line:int}>
     */
    private function publicMembers(array $tokens): array
    {
        $members = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_PUBLIC) {
                continue;
            }

            $line = $tokens[$i][2];

            // Skip modifiers and type declarations until we reach what is declared.
            for ($j = $i + 1; $j < $count; $j++) {
                $token = $tokens[$j];

                if (!is_array($token)) {
                    if ($token === '?' || $token === '|' || $token === '&') {
                        continue;
                    }
                    break;
                }

                if ($token[0] === T_FUNCTION) {
                    $name = $this->methodName($tokens, $j);
                    if ($name !== null) {
                        $members[] = ['name' => $name, 'type' => 'method', 'line' => $line];
                    }
                    break;
                }

                if ($token[0] === T_VARIABLE) {
                    // Covers both declared and constructor-promoted properties.
                    $members[] = ['name' => ltrim($token[1], '$'), 'type' => 'property', 'line' => $line];
                    break;
                }

                if (!in_array($token[0], self::SKIPPABLE, true)) {
                    break;
                }
            }
        }

        return $members;
    }

    /**
     * @param list<array{0:int,1:string,2:int}|string> $tokens
     */
    private function methodName(array $tokens, int $functionIndex): ?string
    {
        $count = count($tokens);

        for ($i = $functionIndex + 1; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token === '&' || (is_array($token) && in_array($token[0], [
                T_WHITESPACE,
                T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
                T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
            ], true))) {
                continue;
            }

            return is_array($token) && $token[0] === T_STRING ? $token[1] : null;
        }

        return null;
    }

    /**
     * Collects every member name that is accessed somewhere in the source:
     * ->name, ?->name, ::name, ::$name and named arguments (name: $value).
     *
     * String literals are deliberately ignored: route handlers are resolved
     * precisely, per class, by routedMembers().
     *
     * @param list<array{0:int,1:string,2:int}|string> $tokens
     * @return list<string>
     */
    private function usedNames(array $tokens): array
    {
        $names = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if (in_array($token[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
                $next = $tokens[$i + 1] ?? null;

                if (is_array($next) && $next[0] === T_STRING) {
                    $names[] = $next[1];
                } elseif (is_array($next) && $next[0] === T_VARIABLE) {
                    $names[] = ltrim($next[1], '$');
                }
                continue;
            }

            // Named argument: "(name: ..." or ", name: ...".
            // A ternary or a case label never has "(" or "," before the identifier.
            if ($token[0] === T_STRING
                && $this->neighbour($tokens, $i, 1) === ':'
                && in_array($this->neighbour($tokens, $i, -1), ['(', ','], true)
            ) {
                $names[] = $token[1];
            }
        }

        return $names;
    }

    /**
     * Returns the closest token that is neither whitespace nor a comment.
     *
     * @param list<array{0:int,1:string,2:int}|string> $tokens
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

    /**
     * Collects member names used by PHP files that declare no class,
     * interface, trait or enum (index.php, bootstrap scripts...).
     * Files declaring a type are already part of $classes.
     *
     * @return array<string, array<string, true>> member name => files using it
     */
    private function usedByScripts(): array
    {
        $usedBy = [];

        foreach ($this->paths->phpFiles() as $path) {
            if (str_contains($path, '/vendor/')) {
                continue;
            }

            $source = file_get_contents($path);
            if ($source === false) {
                continue;
            }

            $tokens = token_get_all($source);
            if ($this->declaresType($tokens)) {
                continue;
            }

            foreach ($this->usedNames($tokens) as $name) {
                $usedBy[$name][$path] = true;
            }
        }

        return $usedBy;
    }

    /**
     * @param list<array{0:int,1:string,2:int}|string> $tokens
     */
    private function declaresType(array $tokens): bool
    {
        foreach ($tokens as $i => $token) {
            if (!is_array($token) || !in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                continue;
            }

            // Foo::class is a constant access, not a declaration.
            $previous = $this->neighbour($tokens, $i, -1);
            if (is_array($previous) && $previous[0] === T_DOUBLE_COLON) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Maps "makeXxx" factory methods to their declared return type (short name).
     *
     * @return array<string, string>
     */
    private function factoryReturnTypes(string $source): array
    {
        preg_match_all(
            '/function\s+(make\w+)\s*\([^)]*\)\s*:\s*\\\\?(?:[\w\\\\]*\\\\)?(\w+)/',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        $map = [];
        foreach ($matches as $match) {
            $map[$match[1]] = $match[2];
        }

        return $map;
    }

    /**
     * Resolves route declarations to "ClassName::method" pairs.
     *
     * Matches the pattern used by app\config\routes\*:
     *   $handler = fn() => $this->apiFactory->makeEventApi();
     *   new Route('POST /api/event/sendEmails', $handler, 'sendEmails');
     *
     * @param array<string, string> $factoryMap
     * @return list<string>
     */
    private function routedMembers(string $source, array $factoryMap): array
    {
        $handlers = [];

        preg_match_all(
            '/\$(\w+)\s*=\s*(?:fn|function)\s*\(\)\s*(?:=>|\{\s*return)\s*\$this->\w+->(make\w+)\(/',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            // Fall back to the name after "make" when the factory is unknown.
            $handlers[$match[1]] = $factoryMap[$match[2]] ?? substr($match[2], 4);
        }

        $routed = [];

        preg_match_all(
            '/new\s+Route\(\s*[\'"][^\'"]*[\'"]\s*,\s*\$(\w+)\s*,\s*[\'"](\w+)[\'"]/',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            if (isset($handlers[$match[1]])) {
                $routed[] = "{$handlers[$match[1]]}::{$match[2]}";
            }
        }

        return $routed;
    }
}