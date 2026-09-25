<?php

declare(strict_types=1);

namespace test\CodingStandards;

use test\CodingStandards\ValueObjects\ClassInfo;

final class ClassScanner
{
    /**
     * @return list<ClassInfo>
     */
    public function scan(string $dir): array
    {
        $classes = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            foreach ($this->parseFile($file->getPathname()) as $classInfo) {
                $classes[] = $classInfo;
            }
        }

        return $classes;
    }

    /**
     * @return list<ClassInfo>
     */
    private function parseFile(string $path): array
    {
        $source = file_get_contents($path);
        if ($source === false) {
            return [];
        }

        $tokens = token_get_all($source);
        $count = count($tokens);
        $results = [];

        $pendingFinal = false;
        $pendingAbstract = false;
        $pendingReadonly = false;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            [$id] = $token;

            switch ($id) {
                case T_FINAL:
                    $pendingFinal = true;
                    break;

                case T_ABSTRACT:
                    $pendingAbstract = true;
                    break;

                case T_READONLY:
                    $pendingReadonly = true;
                    break;

                case T_CLASS:
                case T_INTERFACE:
                case T_TRAIT:
                case T_ENUM:
                    $prevMeaningful = $this->previousMeaningfulToken($tokens, $i);

                    if ($id === T_CLASS && $prevMeaningful === T_DOUBLE_COLON) {
                        // "Foo::class" (résolution de nom de classe), pas une déclaration.
                        break;
                    }

                    if ($id === T_CLASS && $prevMeaningful === T_NEW) {
                        // Classe anonyme "new class ...": on ignore, on garde les
                        // modificateurs en attente pour la prochaine vraie déclaration.
                        break;
                    }

                    $kind = match ($id) {
                        T_CLASS => 'class',
                        T_INTERFACE => 'interface',
                        T_TRAIT => 'trait',
                        T_ENUM => 'enum',
                        default => 'class',
                    };

                    $name = null;
                    $extends = null;

                    for ($j = $i + 1; $j < $count; $j++) {
                        $t = $tokens[$j];

                        if (!is_array($t) && $t === '{') {
                            break;
                        }

                        if (is_array($t) && $t[0] === T_STRING && $name === null) {
                            $name = $t[1];
                            continue;
                        }

                        if (is_array($t) && $t[0] === T_EXTENDS) {
                            for ($k = $j + 1; $k < $count; $k++) {
                                $t2 = $tokens[$k];
                                if (is_array($t2) && $t2[0] === T_STRING) {
                                    $extends = $t2[1];
                                    break;
                                }
                                if (!is_array($t2) && $t2 === '{') {
                                    break;
                                }
                            }
                        }
                    }

                    if ($name !== null) {
                        $results[] = new ClassInfo(
                            filePath: $path,
                            className: $name,
                            kind: $kind,
                            isFinal: $pendingFinal,
                            isAbstract: $pendingAbstract,
                            isReadonly: $pendingReadonly,
                            extends: $extends,
                            sourceCode: $source,
                        );
                    }

                    $pendingFinal = $pendingAbstract = $pendingReadonly = false;
                    break;

                case T_FUNCTION:
                case T_VARIABLE:
                case T_CONST:
                    // "readonly" sur une propriété, pas sur la classe : on efface
                    // les modificateurs en attente pour ne pas les reporter à tort
                    // sur une déclaration de classe suivante.
                    $pendingFinal = $pendingAbstract = $pendingReadonly = false;
                    break;
            }
        }

        return $results;
    }

    private function previousMeaningfulToken(array $tokens, int $index): int|string|null
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $t = $tokens[$i];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return is_array($t) ? $t[0] : $t;
        }

        return null;
    }
}