<?php

declare(strict_types=1);

namespace test\CodingStandards;

use test\CodingStandards\ValueObjects\ClassInfo;

final class CodingStandardsChecker
{
    // Ajuster ici si la structure réelle diffère de ces hypothèses.
    private const VALUE_OBJECTS_SUBPATH = 'valueObjects';
    private const VIEW_MODELS_SUBPATH = 'viewModels';
    private const APIS_SUBPATH = 'apis';
    private const MODULES_SUBPATH = 'modules';

    // Méthodes publiques exemptées de la règle "doit finir par un appel terminal".
    private const RENDER_EXEMPT_METHODS = ['__construct', '__destruct', 'render'];

    // Appels considérés comme une fin légitime de méthode de contrôleur, en plus
    // de render(...) : redirect (PRG après save/delete) et envoi de fichier/flux.
    private const TERMINAL_CALL_PATTERNS = [
        'render(',
        'redirect(',
        'readfile(',
        'fpassthru(',
        'sendFile(',
        'download(',
        'stream(',
    ];

    /** @var list<ClassInfo> */
    private array $classes;

    public function __construct(private readonly string $appDir)
    {
        $this->classes = (new ClassScanner())->scan($appDir);
    }

    /**
     * @return array<string, list<string>>
     */
    public function check(): array
    {
        $violations = [];

        $this->addIfNotEmpty($violations, 'ValueObjects non final readonly', $this->checkValueObjects());
        $this->addIfNotEmpty($violations, 'ViewModels non final readonly ou mal suffixés', $this->checkViewModels());
        $this->addIfNotEmpty($violations, 'Classes non final (et non héritées)', $this->checkFinalClasses());
        $this->addIfNotEmpty($violations, 'Contrôleurs mal placés/suffixés ou méthodes sans render', $this->checkControllers());
        $this->addIfNotEmpty($violations, 'Classes JSON mal placées/suffixées', $this->checkApis());

        return $violations;
    }

    /**
     * @param array<string, list<string>> $violations
     * @param list<string> $items
     */
    private function addIfNotEmpty(array &$violations, string $label, array $items): void
    {
        if ($items !== []) {
            $violations[$label] = $items;
        }
    }

    /**
     * @return list<string>
     */
    private function checkValueObjects(): array
    {
        $issues = [];

        foreach ($this->classes as $class) {
            if ($class->kind !== 'class' || !$this->inSubpath($class, self::VALUE_OBJECTS_SUBPATH)) {
                continue;
            }

            if ($class->isAbstract) {
                // final + abstract est contradictoire en PHP : une classe de base
                // abstraite ne peut pas être final. On exige seulement readonly.
                if (!$class->isReadonly) {
                    $issues[] = "{$class->className} ({$this->rel($class)}) devrait être 'readonly' (classe de base)";
                }
                continue;
            }

            if (!$class->isFinal || !$class->isReadonly) {
                $issues[] = "{$class->className} ({$this->rel($class)}) doit être 'final readonly'";
            }
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function checkViewModels(): array
    {
        $issues = [];

        foreach ($this->classes as $class) {
            if ($class->kind !== 'class' || !$this->inSubpath($class, self::VIEW_MODELS_SUBPATH)) {
                continue;
            }

            if ($class->isAbstract) {
                if (!$class->isReadonly) {
                    $issues[] = "{$class->className} ({$this->rel($class)}) devrait être 'readonly' (classe de base)";
                }
            } elseif (!$class->isFinal || !$class->isReadonly) {
                $issues[] = "{$class->className} ({$this->rel($class)}) doit être 'final readonly'";
            }

            if (!str_ends_with($class->className, 'ViewModel')) {
                $issues[] = "{$class->className} ({$this->rel($class)}) doit être suffixé 'ViewModel'";
            }
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function checkFinalClasses(): array
    {
        $issues = [];
        $extendedNames = $this->collectExtendedClassNames();

        foreach ($this->classes as $class) {
            if ($class->kind !== 'class' || $class->isAbstract || $class->isFinal) {
                continue;
            }

            // Déjà couvertes (et déjà exigées final) par les règles ci-dessus.
            if ($this->inSubpath($class, self::VALUE_OBJECTS_SUBPATH) || $this->inSubpath($class, self::VIEW_MODELS_SUBPATH)) {
                continue;
            }

            if (isset($extendedNames[$class->className])) {
                continue;
            }

            $issues[] = "{$class->className} ({$this->rel($class)}) devrait être 'final' (aucune classe ne semble en hériter)";
        }

        return $issues;
    }

    /**
     * @return array<string, true>
     */
    private function collectExtendedClassNames(): array
    {
        $names = [];

        foreach ($this->classes as $class) {
            if ($class->extends !== null) {
                $names[$class->extends] = true;
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function checkControllers(): array
    {
        $issues = [];

        foreach ($this->classes as $class) {
            if ($class->kind !== 'class' || !$this->isModuleRootFile($class)) {
                continue;
            }

            if (!str_ends_with($class->className, 'Controller')) {
                $issues[] = "{$class->className} ({$this->rel($class)}) est à la racine d'un module : doit être suffixé 'Controller'";
                continue;
            }

            foreach ($this->findPublicMethodsRenderStatus($class) as $method) {
                if (in_array($method['name'], self::RENDER_EXEMPT_METHODS, true)) {
                    continue;
                }

                if (!$method['endsWithAcceptedTerminalCall']) {
                    $issues[] = "{$class->className}::{$method['name']}() ({$this->rel($class)}) ne se termine ni par render(...), ni par un redirect, ni par un envoi de fichier";
                }
            }
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function checkApis(): array
    {
        $issues = [];

        foreach ($this->classes as $class) {
            if ($class->kind !== 'class') {
                continue;
            }

            $looksLikeApi = $this->emitsJson($class);
            $inApisFolder = $this->inSubpath($class, self::APIS_SUBPATH);
            $suffixedApi = str_ends_with($class->className, 'Api');

            if ($looksLikeApi && !$suffixedApi) {
                $issues[] = "{$class->className} ({$this->rel($class)}) semble renvoyer du JSON : doit être suffixé 'Api'";
            }

            if ($looksLikeApi && !$inApisFolder) {
                $issues[] = "{$class->className} ({$this->rel($class)}) semble renvoyer du JSON : doit être dans app/" . self::APIS_SUBPATH;
            }

            if ($inApisFolder && !$suffixedApi) {
                $issues[] = "{$class->className} ({$this->rel($class)}) est dans app/" . self::APIS_SUBPATH . " : doit être suffixé 'Api'";
            }
        }

        return $issues;
    }

    private function emitsJson(ClassInfo $class): bool
    {
        // Signal resserré sur l'émission réelle d'une réponse JSON (Flight),
        // plutôt que json_encode() brut qui remonte aussi la sérialisation
        // interne (logs, stockage) sans rapport avec une réponse HTTP.
        return str_contains($class->sourceCode, 'Flight::json(')
            || str_contains($class->sourceCode, '->json(');
    }

    /**
     * Repère chaque méthode publique et détermine si sa dernière instruction
     * de haut niveau (au sens des tokens PHP, donc en respectant les blocs
     * if/else, try/catch, etc.) contient un appel terminal accepté : render(...),
     * un redirect, ou un envoi de fichier/flux.
     *
     * @return list<array{name: string, endsWithAcceptedTerminalCall: bool}>
     */
    private function findPublicMethodsRenderStatus(ClassInfo $class): array
    {
        $tokens = token_get_all($class->sourceCode);
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
            $j++;

            while ($j < $count && is_array($tokens[$j]) && in_array(
                $tokens[$j][0],
                [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT],
                true,
            )) {
                $j++;
            }

            if (!is_array($tokens[$j] ?? null) || $tokens[$j][0] !== T_STRING) {
                continue;
            }

            $methodName = $tokens[$j][1];
            $j++;

            // Sauter jusqu'à la parenthèse ouvrante de la liste de paramètres.
            while ($j < $count && !(!is_array($tokens[$j]) && $tokens[$j] === '(')) {
                $j++;
            }
            if ($j >= $count) {
                continue;
            }

            // Équilibrer les parenthèses pour sauter toute la liste de paramètres
            // (y compris les valeurs par défaut contenant elles-mêmes des parenthèses).
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

            // Sauter l'éventuel type de retour jusqu'à '{' (corps) ou ';' (abstrait/interface).
            $bodyStart = null;
            for (; $j < $count; $j++) {
                $t = $tokens[$j];
                if ($this->isOpenBraceToken($t)) {
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
                if ($this->isOpenBraceToken($t)) {
                    $braceDepth++;
                } elseif ($this->isCloseBraceToken($t)) {
                    $braceDepth--;
                    if ($braceDepth === 0) {
                        break;
                    }
                }
                $bodyTokens[] = $t;
            }

            $results[] = [
                'name' => $methodName,
                'endsWithAcceptedTerminalCall' => $this->lastStatementEndsWithAcceptedCall($bodyTokens),
            ];

            $i = $k;
        }

        return $results;
    }

    /**
     * @param list<mixed> $bodyTokens
     */
    private function lastStatementEndsWithAcceptedCall(array $bodyTokens): bool
    {
        $statements = [];
        $current = [];
        $braceDepth = 0;
        $parenDepth = 0;

        foreach ($bodyTokens as $t) {
            $current[] = $t;

            if ($this->isOpenBraceToken($t)) {
                $braceDepth++;
            } elseif ($this->isCloseBraceToken($t)) {
                $braceDepth--;
                if ($braceDepth === 0 && $parenDepth === 0) {
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

        if ($this->tokensContainNonWhitespace($current)) {
            $statements[] = $current;
        }

        $last = end($statements);
        if ($last === false) {
            return false;
        }

        $text = $this->tokensToText($last);

        foreach (self::TERMINAL_CALL_PATTERNS as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        // Redirect via header() brut plutôt qu'un wrapper dédié.
        return str_contains($text, 'header(') && stripos($text, 'Location') !== false;
    }

    /**
     * @param list<mixed> $tokens
     */
    private function tokensContainNonWhitespace(array $tokens): bool
    {
        foreach ($tokens as $t) {
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return true;
        }

        return false;
    }

    /**
     * @param list<mixed> $tokens
     */
    private function tokensToText(array $tokens): string
    {
        $text = '';

        foreach ($tokens as $t) {
            $text .= is_array($t) ? $t[1] : $t;
        }

        return $text;
    }

    private function isOpenBraceToken(mixed $token): bool
    {
        if (!is_array($token)) {
            return $token === '{';
        }

        return $token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES;
    }

    private function isCloseBraceToken(mixed $token): bool
    {
        return !is_array($token) && $token === '}';
    }

    private function isModuleRootFile(ClassInfo $class): bool
    {
        $relative = $this->rel($class);

        return (bool) preg_match(
            '#^' . preg_quote(self::MODULES_SUBPATH, '#') . '/[^/]+/[^/]+\.php$#',
            $relative,
        );
    }

    private function inSubpath(ClassInfo $class, string $subpath): bool
    {
        $relative = $this->rel($class);

        return $relative === $subpath
            || str_starts_with($relative, "{$subpath}/")
            || str_contains($relative, "/{$subpath}/");
    }

    private function rel(ClassInfo $class): string
    {
        return $class->relativePath($this->appDir);
    }
}