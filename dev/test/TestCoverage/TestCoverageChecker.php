<?php

declare(strict_types=1);

namespace test\TestCoverage;

use FilesystemIterator;
use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use test\TestCoverage\ValueObjects\MissingTest;
use test\TestCoverage\ValueObjects\UncoveredColumns;

final class TestCoverageChecker
{
    /**
     * Nom de la propriété générique exposant un accès brut à la base
     * (get/gets/set/query), telle qu'utilisée par AbstractApi/AbstractController.
     * Adapter si une autre convention de nommage existe ailleurs.
     */
    private const GENERIC_HELPER_PROPERTY = 'dataHelper';

    /** @param class-string $baseClass */
    public function __construct(
        private readonly string $appDir,
        private readonly string $testsDir,
        private readonly string $baseClass,
    ) {}

    /** @return array<int, MissingTest> */
    public function findMissing(): array
    {
        $classes = $this->scanClasses($this->appDir);
        foreach ($classes as $path) {
            require_once $path;
        }

        $missing = [];
        foreach ($classes as $fqcn => $path) {
            if (!class_exists($fqcn)) {
                continue;
            }
            $rc = new ReflectionClass($fqcn);
            if (!$this->requiresTest($rc)) {
                continue;
            }
            $testPath = $this->expectedTestPath($path);
            if (!is_file($testPath)) {
                $missing[] = new MissingTest($fqcn, $testPath);
            }
        }
        return $missing;
    }

    /**
     * For every class that requires a test and has one, checks that the test
     * asserts every table and column queried in the source.
     *
     * @return array<int, UncoveredColumns>
     */
    public function findUncovered(): array
    {
        $classes = $this->scanClasses($this->appDir);
        foreach ($classes as $path) {
            require_once $path;
        }

        $extractor = new SourceQueryExtractor();
        $uncovered = [];

        foreach ($classes as $fqcn => $path) {
            if (!class_exists($fqcn) || !$this->requiresTest(new ReflectionClass($fqcn))) {
                continue;
            }
            $testPath = $this->expectedTestPath($path);
            if (!is_file($testPath)) {
                continue; // already reported by findMissing()
            }

            $source = file_get_contents($path);
            $testSource = file_get_contents($testPath);
            if ($source === false || $testSource === false) {
                continue;
            }

            $covered = $this->coveredColumns($testSource);

            foreach ($extractor->extract($source) as $table => $columns) {
                $known = $covered[strtolower($table)] ?? [];
                $absent = array_values(array_filter(
                    $columns,
                    static fn(string $c): bool => !in_array(strtolower($c), $known, true)
                ));
                // A table without any literal column still has to be asserted.
                if ($absent !== [] || ($columns === [] && !isset($covered[strtolower($table)]))) {
                    $uncovered[] = new UncoveredColumns($fqcn, $testPath, $table, $absent);
                }
            }
        }

        return $uncovered;
    }

    /** @return array<string, list<string>> lowercase table => lowercase columns asserted by the test */
    private function coveredColumns(string $testSource): array
    {
        $covered = [];
        $pattern = '/assertColumnsExist\(\s*\$\w+\s*,\s*([\'"])(\w+)\1\s*,\s*\[(.*?)\]\s*\)/s';
        if (preg_match_all($pattern, $testSource, $calls, PREG_SET_ORDER) === 0) {
            return $covered;
        }
        foreach ($calls as $call) {
            $table = strtolower($call[2]);
            $covered[$table] ??= [];
            if (preg_match_all('/([\'"])(\w+)\1/', $call[3], $cols) > 0) {
                foreach ($cols[2] as $col) {
                    $covered[$table][] = strtolower($col);
                }
            }
        }
        return $covered;
    }

    /** @return array<string, string> FQCN => chemin du fichier source */
    private function scanClasses(string $dir): array
    {
        $classes = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        /** @var SplFileInfo $file */
        foreach ($it as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }
            if (
                preg_match('/^namespace\s+([^;]+);/m', $content, $ns)
                && preg_match('/^(?:abstract\s+|final\s+)?class\s+(\w+)/m', $content, $cls)
            ) {
                $classes[trim($ns[1]) . '\\' . $cls[1]] = $file->getPathname();
            }
        }
        return $classes;
    }

    /**
     * @param ReflectionClass<object> $rc
     */
    private function requiresTest(ReflectionClass $rc): bool
    {
        if ($rc->isAbstract() || $rc->isInterface()) {
            return false;
        }

        // Cas 1 : la classe EST un data helper (sous-classe de Data) — elle
        // porte elle-même des requêtes get/gets/set/query qu'il faut tester.
        if ($rc->getName() !== $this->baseClass && $rc->isSubclassOf($this->baseClass)) {
            return true;
        }

        // Cas 2 : la classe appelle directement le data helper générique
        // ($this->dataHelper->get/gets/set/query(...)), typiquement héritée
        // d'AbstractApi/AbstractController. Injecter un data helper DÉDIÉ
        // (LoanDataHelper, PersonDataHelper, ...) sans jamais toucher à
        // $this->dataHelper ne suffit plus à déclencher l'exigence : ce
        // data helper a déjà son propre test.
        $path = $rc->getFileName();
        if ($path !== false) {
            $content = file_get_contents($path);
            if (
                $content !== false
                && preg_match(
                    '/\$this->' . preg_quote(self::GENERIC_HELPER_PROPERTY, '/') . '\s*->\s*(get|gets|set|query)\s*\(/',
                    $content
                ) === 1
            ) {
                return true;
            }
        }

        return false;
    }

    private function expectedTestPath(string $sourcePath): string
    {
        $relative = substr($sourcePath, strlen($this->appDir) + 1);
        $relative = preg_replace('/\.php$/', 'Test.php', $relative) ?? $relative;
        return $this->testsDir . '/' . $relative;
    }
}
