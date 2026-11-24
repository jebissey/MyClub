<?php

declare(strict_types=1);

namespace tests\models;

use PDO;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Checks consistency between the translation keys used in source files
 * (.php, .latte, .js) and the keys defined in the Languages table.
 *
 * - Missing keys: used in the code but not defined in Languages.
 * - Orphan keys: defined in Languages but referenced nowhere.
 *
 * Dynamically built keys ('prefix.' . $var, "prefix_{$var}") cannot be resolved
 * statically. Every string literal ending with '.' or '_' (or followed by an
 * interpolation) is treated as a prefix: any defined key starting with such a
 * prefix is considered used.
 */
class TranslationKeysTest extends TestCase
{
    private const SOURCE_EXTENSIONS = ['php', 'latte', 'js'];

    /**
     * Files whose string literals must not count as "usage" of a key
     * (database migrators insert/delete Languages rows by name).
     */
    private const LITERAL_EXCLUDED_FILE_FRAGMENTS = ['Migrator'];

    /**
     * Defined keys resolved by mechanisms a static scan cannot see.
     */
    private const ORPHAN_IGNORE_PATTERNS = [
        '/^Error\d{3}$/',
        '/^Help_[A-Za-z]+$/',
    ];

    /** Patterns extracting explicit keys from .latte files (group 1 = key). */
    private const LATTE_PATTERNS = [
        '/[\'"]([A-Za-z0-9_.]+)[\'"]\s*\|\s*translate\b/',
        '/=>\s*[\'"]([a-z][A-Za-z0-9_]*(?:\.[A-Za-z0-9_]+)+)[\'"]\s*[,\]]/',
    ];

    /** Patterns extracting explicit keys from .php files (group 1 = key). */
    private const PHP_PATTERNS = [
        '/->translate\(\s*[\'"]([A-Za-z0-9_.]+)[\'"]\s*[,)]/',
        '/(?:->t|\$t)\s*\)?\s*\(\s*[\'"]([A-Za-z0-9_.]+)[\'"]\s*[,)]/',
        '/->get\(\s*[\'"]Languages[\'"]\s*,\s*\[\s*[\'"]Name[\'"]\s*=>\s*[\'"]([A-Za-z0-9_.]+)[\'"]\s*\]/',
    ];

    /**
     * Plural variants: 'karaoke.clients_connected' is resolved as
     * 'karaoke.clients_connected_one' / '_other' depending on a count.
     */
    private const PLURAL_SUFFIXES = [
        '_zero',
        '_one',
        '_two',
        '_few',
        '_many',
        '_other',
    ];

    private const LITERAL_PATTERN = '/[\'"]([A-Za-z0-9_.]+)[\'"]/';

    /** "prefix.$var" or "prefix_{$var}" inside a string. */
    private const INTERPOLATED_PREFIX_PATTERN =
        '/[\'"]([A-Za-z][A-Za-z0-9_.]*[._])(?=\{?\$)/';

    /** @var array{used: array<string, list<array{file: string, line: int}>>, literals: array<string, true>, prefixes: array<string, true>}|null */
    private static ?array $scan = null;

    /** @var array<string, true>|null */
    private static ?array $definedKeys = null;

    public function testNoTranslationKeyIsMissingFromLanguages(): void
    {
        $defined = $this->getDefinedKeys();
        $missing = [];

        foreach ($this->scanSources()['used'] as $key => $occurrences) {
            if (!$this->isDefined($key, $defined)) {
                $missing[$key] = $occurrences;
            }
        }

        ksort($missing);

        $this->assertEmpty($missing, $this->formatMissing($missing));
    }

    public function testNoTranslationKeyIsOrphan(): void
    {
        $scan = $this->scanSources();
        $orphans = [];

        foreach (array_keys($this->getDefinedKeys()) as $key) {
            if (!$this->isKeyReferenced($key, $scan['literals'], $scan['prefixes'])) {
                $orphans[] = $key;
            }
        }

        sort($orphans);

        $this->assertEmpty($orphans, $this->formatOrphans($orphans, $scan['prefixes']));
    }

    // -------------------------------------------------------------------------
    // Defined keys (existing SQLite database)
    // -------------------------------------------------------------------------

    /**
     * @return array<string, true>
     */
    private function getDefinedKeys(): array
    {
        if (self::$definedKeys !== null) {
            return self::$definedKeys;
        }

        $databaseFile = $this->getAppDir() . '/models/database/MyClub.sqlite';

        $this->assertFileExists(
            $databaseFile,
            "Database file not found: $databaseFile"
        );

        $pdo = new PDO('sqlite:' . $databaseFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $statement = $pdo->query('SELECT Name FROM Languages');

        $this->assertNotFalse($statement);

        $keys = [];

        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $name) {
            if (is_string($name)) {
                $keys[$name] = true;
            }
        }

        return self::$definedKeys = $keys;
    }

    // -------------------------------------------------------------------------
    // Source scanning
    // -------------------------------------------------------------------------

    /**
     * @return array{
     *     used: array<string, list<array{file: string, line: int}>>,
     *     literals: array<string, true>,
     *     prefixes: array<string, true>
     * }
     */
    private function scanSources(): array
    {
        if (self::$scan !== null) {
            return self::$scan;
        }

        $used = [];
        $literals = [];
        $prefixes = [];

        foreach ($this->listSourceFiles() as $path) {
            $content = file_get_contents($path);

            if ($content === false) {
                continue;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            $this->collectUsedKeys($path, $content, $extension, $used);

            if (!$this->isExcludedFromLiterals($path)) {
                $this->collectLiteralsAndPrefixes($content, $literals, $prefixes);
            }
        }

        return self::$scan = [
            'used' => $used,
            'literals' => $literals,
            'prefixes' => $prefixes,
        ];
    }

    /**
     * @return list<string>
     */
    private function listSourceFiles(): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->getAppDir(),
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            $extension = strtolower($fileInfo->getExtension());

            if (
                in_array($extension, self::SOURCE_EXTENSIONS, true)
                && !str_ends_with($fileInfo->getFilename(), '.min.js')
            ) {
                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Collects keys explicitly passed to a translation call. Whole-file matching,
     * so calls split over several lines are found too.
     *
     * @param array<string, list<array{file: string, line: int}>> $used
     */
    private function collectUsedKeys(
        string $path,
        string $content,
        string $extension,
        array &$used
    ): void {
        $patterns = match ($extension) {
            'latte' => self::LATTE_PATTERNS,
            'php' => self::PHP_PATTERNS,
            default => [],
        };

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches as $match) {
                [$key, $offset] = $match[1];

                $used[$key][] = [
                    'file' => $path,
                    'line' => substr_count(substr($content, 0, $offset), "\n") + 1,
                ];
            }
        }
    }

    /**
     * @param array<string, true> $literals
     * @param array<string, true> $prefixes
     */
    private function collectLiteralsAndPrefixes(
        string $content,
        array &$literals,
        array &$prefixes
    ): void {
        if (preg_match_all(self::LITERAL_PATTERN, $content, $matches)) {
            foreach ($matches[1] as $token) {
                $literals[$token] = true;

                if (
                    preg_match('/^[A-Za-z].*[._]$/', $token) === 1
                    && !str_contains($token, '..')
                ) {
                    $prefixes[$token] = true;
                }
            }
        }

        if (preg_match_all(self::INTERPOLATED_PREFIX_PATTERN, $content, $matches)) {
            foreach ($matches[1] as $prefix) {
                $prefixes[$prefix] = true;
            }
        }
    }

    private function isExcludedFromLiterals(string $path): bool
    {
        $filename = basename($path);

        foreach (self::LITERAL_EXCLUDED_FILE_FRAGMENTS as $fragment) {
            if (str_contains($filename, $fragment)) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Analysis
    // -------------------------------------------------------------------------

    /**
     * @param array<string, true> $defined
     */
    private function isDefined(string $key, array $defined): bool
    {
        if (isset($defined[$key])) {
            return true;
        }

        foreach (self::PLURAL_SUFFIXES as $suffix) {
            if (isset($defined[$key . $suffix])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, true> $literals
     * @param array<string, true> $prefixes
     */
    private function isKeyReferenced(
        string $key,
        array $literals,
        array $prefixes
    ): bool {
        if (isset($literals[$key])) {
            return true;
        }

        foreach (self::PLURAL_SUFFIXES as $suffix) {
            if (
                str_ends_with($key, $suffix)
                && isset($literals[substr($key, 0, -strlen($suffix))])
            ) {
                return true;
            }
        }

        foreach (self::ORPHAN_IGNORE_PATTERNS as $pattern) {
            if (preg_match($pattern, $key) === 1) {
                return true;
            }
        }

        foreach (array_keys($prefixes) as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Reporting
    // -------------------------------------------------------------------------

    /**
     * @param array<string, list<array{file: string, line: int}>> $missing
     */
    private function formatMissing(array $missing): string
    {
        $lines = [
            sprintf(
                '%d translation key(s) used in the code but missing from Languages:',
                count($missing)
            ),
        ];

        foreach ($missing as $key => $occurrences) {
            $first = $occurrences[0];

            $extra = count($occurrences) > 1
                ? sprintf(' (+%d other(s))', count($occurrences) - 1)
                : '';

            $lines[] = sprintf(
                '  - %s  [%s:%d%s]',
                $key,
                $this->relativePath($first['file']),
                $first['line'],
                $extra
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<string> $orphans
     * @param array<string, true> $prefixes
     */
    private function formatOrphans(array $orphans, array $prefixes): string
    {
        $prefixList = array_keys($prefixes);

        sort($prefixList);

        $lines = [
            sprintf(
                '%d key(s) defined in Languages but referenced nowhere:',
                count($orphans)
            ),
        ];

        foreach ($orphans as $key) {
            $lines[] = '  - ' . $key;
        }

        $lines[] = '';
        $lines[] = 'Dynamic prefixes taken into account ('
            . count($prefixList)
            . '): '
            . implode(', ', $prefixList);

        return implode("\n", $lines);
    }

    private function relativePath(string $path): string
    {
        return str_replace(
            $this->getAppDir() . DIRECTORY_SEPARATOR,
            '',
            $path
        );
    }

    private function getAppDir(): string
    {
        // This file lives in <root>/dev/tests/models/,
        // the application in <root>/WebSite/app.

        $appDir = realpath(dirname(__DIR__, 3) . '/WebSite/app');

        $this->assertNotFalse(
            $appDir,
            'Unable to resolve the app directory.'
        );

        return $appDir;
    }
}
