#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CheckMissingTranslation.php
 *
 * Checks that all translation keys used in .latte and .php files
 * are properly defined in the SQL database (Languages table).
 *
 * Usage: php CheckMissingTranslation.php [--verbose]
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * Command-line tool that verifies translation keys consistency
 * between source files (.latte / .php) and the Languages table.
 */
final class CheckMissingTranslation
{
    private string $scanDir;
    private string $sqlFile;
    private bool $verbose;

    public function __construct()
    {
        $scanDir = realpath(MYCLUB_WEBSITE_DIR . '/app');
        $sqlFile = realpath(MYCLUB_WEBSITE_DIR . '/app/models/database/MyClub.sqlite.sql');

        if ($scanDir === false) {
            fwrite(STDERR, "Unable to resolve path: " . MYCLUB_WEBSITE_DIR . "/app\n");
            exit(1);
        }

        if ($sqlFile === false) {
            fwrite(STDERR, "Unable to resolve path: " . MYCLUB_WEBSITE_DIR . "/app/models/database/MyClub.sqlite.sql\n");
            exit(1);
        }

        $this->scanDir  = $scanDir;
        $this->sqlFile  = $sqlFile;
        $this->verbose  = false;
    }

    public function run(): int
    {
        $this->parseOptions();

        if (!is_dir($this->scanDir)) {
            $this->error("Directory not found: {$this->scanDir}");
            return 1;
        }

        if (!is_file($this->sqlFile)) {
            $this->error("SQL file not found: {$this->sqlFile}");
            return 1;
        }

        $definedKeys      = $this->extractDefinedKeys();
        $usedKeys         = [];
        $dynamicPrefixes  = [];

        $latteResult    = $this->scanLatteFiles($usedKeys, $dynamicPrefixes);
        $phpResult      = $this->scanPhpFiles($usedKeys, $dynamicPrefixes);
        $phpArrayResult = $this->scanPhpTranslationArrays($usedKeys);

        $this->printScanSummary($latteResult, $phpResult, $phpArrayResult, $usedKeys, $dynamicPrefixes);

        [$missingKeys, $orphanKeys] = $this->analyse($definedKeys, $usedKeys, $dynamicPrefixes);

        return $this->printReport($missingKeys, $orphanKeys);
    }

    // -------------------------------------------------------------------------
    // Options
    // -------------------------------------------------------------------------

    private function parseOptions(): void
    {
        global $argv;
        $this->verbose = in_array('--verbose', $argv ?? [], true);
    }

    // -------------------------------------------------------------------------
    // SQL extraction (handles single-line and multi-line INSERT)
    // -------------------------------------------------------------------------

    /**
     * @return array<string, true>
     */
    private function extractDefinedKeys(): array
    {
        $this->title("Reading SQL file...");
        $this->dim("  {$this->sqlFile}");
        echo PHP_EOL . PHP_EOL;

        $sqlContent = file_get_contents($this->sqlFile);
        if ($sqlContent === false) {
            $this->error("Unable to read the SQL file.");
            exit(1);
        }

        $definedKeys = [];

        // Primary regex – works with multi-line INSERTs
        if (preg_match_all(
            '/INSERT\s+INTO\s+["\']?Languages["\']?.*?VALUES\s*\(.*?(\d+)\s*,\s*[\'"]([^\'"]+)[\'"]/is',
            $sqlContent,
            $m
        )) {
            foreach ($m[2] as $key) {
                $definedKeys[$key] = true;
            }
        }

        // Fallback: capture remaining (id, 'key.with.dots') patterns
        if (preg_match_all(
            '/\(\s*\d+\s*,\s*[\'"]([a-zA-Z0-9_.]+)[\'"]/s',
            $sqlContent,
            $m2
        )) {
            foreach ($m2[1] as $key) {
                if (str_contains($key, '.')) {
                    $definedKeys[$key] = true;
                }
            }
        }

        $this->success(sprintf("  %d key(s) found in Languages.", count($definedKeys)));
        echo PHP_EOL . PHP_EOL;

        return $definedKeys;
    }

    // -------------------------------------------------------------------------
    // File scanning
    // -------------------------------------------------------------------------

    /**
     * @param array<string, list<array{file:string, line:int}>> $usedKeys
     * @param array<string, true>                               $dynamicPrefixes
     * @return array{files: int, matches: int}
     */
    private function scanLatteFiles(array &$usedKeys, array &$dynamicPrefixes): array
    {
        $this->title("Scanning .latte files in:");
        $this->dim("  {$this->scanDir}");
        echo PHP_EOL . PHP_EOL;

        $result = $this->scanFiles(
            $this->scanDir,
            '/\.latte$/i',
            [
                '/\{=\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\|translate\s*\}/',                  // {='key'|translate}
                '/\{\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\|translate\s*\}/',                    // {'key'|translate}
                '/\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\|translate\s*\)/',                   // ('key'|translate)
                '/=>\s*[\'"]([a-z][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+){1,})[\'"]\s*[,\]]/',  // => 'key',
            ],
            $usedKeys,
            $dynamicPrefixes
        );

        echo sprintf(
            "  %d file(s) — %d occurrence(s) found.",
            $result['files'],
            $result['matches']
        ) . PHP_EOL . PHP_EOL;

        return $result;
    }

    /**
     * @param array<string, list<array{file:string, line:int}>> $usedKeys
     * @param array<string, true>                               $dynamicPrefixes
     * @return array{files: int, matches: int}
     */
    private function scanPhpFiles(array &$usedKeys, array &$dynamicPrefixes): array
    {
        $this->title("Scanning .php files in:");
        $this->dim("  {$this->scanDir}");
        echo PHP_EOL . PHP_EOL;

        $result = $this->scanFiles(
            $this->scanDir,
            '/\.php$/i',
            [
                '/->translate\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\)/',
                '/(?:->t|\$t)\s*\)?\s*\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\)/',
                '/->get\(\s*[\'"]Languages[\'"]\s*,\s*\[\s*[\'"]Name[\'"]\s*=>\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\]/',
            ],
            $usedKeys,
            $dynamicPrefixes
        );

        echo sprintf(
            "  %d file(s) — %d occurrence(s) found.",
            $result['files'],
            $result['matches']
        ) . PHP_EOL . PHP_EOL;

        return $result;
    }

    /**
     * Recursively scans $dir for files matching $extensionRegex.
     *
     * @param string[]                                           $patterns
     * @param array<string, list<array{file:string, line:int}>> $usedKeys
     * @param array<string, true>                               $dynamicPrefixes
     * @return array{files: int, matches: int}
     */
    private function scanFiles(
        string $dir,
        string $extensionRegex,
        array  $patterns,
        array  &$usedKeys,
        array  &$dynamicPrefixes = []
    ): array {
        $fileCount  = 0;
        $matchCount = 0;

        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            ),
            $extensionRegex,
            RegexIterator::MATCH
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            $filePath = $fileInfo->getPathname();
            $fileCount++;

            $lines = file($filePath, FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                $this->warning("  ⚠ Unable to read: $filePath");
                continue;
            }

            foreach ($lines as $lineNo => $lineContent) {
                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $lineContent, $matches)) {
                        foreach ($matches[1] as $key) {
                            $matchCount++;
                            $usedKeys[$key][] = [
                                'file' => $filePath,
                                'line' => $lineNo + 1,
                            ];
                        }
                    }
                }

                // Dynamic latte prefixes: ("prefix_{$var}")|translate
                if (preg_match_all(
                    '/\(\s*[\'"]([a-zA-Z0-9_.]+_)\{\$[a-zA-Z0-9_]+\}[\'"]\s*\|translate\s*\)/',
                    $lineContent,
                    $dynMatches
                )) {
                    foreach ($dynMatches[1] as $prefix) {
                        $dynamicPrefixes[$prefix] = true;
                    }
                }
            }
        }

        return ['files' => $fileCount, 'matches' => $matchCount];
    }

    /**
     * Detects multi-line translation arrays in PHP files.
     *
     * @param array<string, list<array{file:string, line:int}>> $usedKeys
     * @return array{files: int, matches: int}
     */
    private function scanPhpTranslationArrays(array &$usedKeys): array
    {
        $this->title("Scanning PHP translation arrays (multi-line)...");

        $fileCount  = 0;
        $matchCount = 0;

        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->scanDir, RecursiveDirectoryIterator::SKIP_DOTS)
            ),
            '/\.php$/i',
            RegexIterator::MATCH
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            $filePath = $fileInfo->getPathname();
            $fileCount++;

            $content = file_get_contents($filePath);
            if ($content === false) {
                continue;
            }

            $translatePattern = '/(?:->translate|\(\s*\$this->t\s*\)|->t)\s*\(\s*'
                . '[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\.\s*\$([a-zA-Z_][a-zA-Z0-9_]*)'
                . '\s*\)/';

            if (!preg_match_all($translatePattern, $content, $tMatches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($tMatches as $tMatch) {
                $prefix  = $tMatch[1];
                $varName = $tMatch[2];

                $foreachPattern = '/foreach\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s+as\s+\$'
                    . preg_quote($varName, '/') . '\s*\)/';

                if (!preg_match($foreachPattern, $content, $fMatch)) {
                    continue;
                }

                $arrayVar = $fMatch[1];
                $arrayPattern = '/\$' . preg_quote($arrayVar, '/') . '\s*=\s*\[([^\]]+)\]/s';

                if (!preg_match($arrayPattern, $content, $aMatch)) {
                    continue;
                }

                if (!preg_match_all('/[\'"]([a-zA-Z0-9_.]+)[\'"]/', $aMatch[1], $kMatches)) {
                    continue;
                }

                foreach ($kMatches[1] as $suffix) {
                    $fullKey = $prefix . $suffix;
                    $matchCount++;
                    $usedKeys[$fullKey][] = [
                        'file' => $filePath,
                        'line' => 0,
                    ];
                }
            }
        }

        echo sprintf(
            "  %d file(s) — %d key(s) extracted from arrays.",
            $fileCount,
            $matchCount
        ) . PHP_EOL . PHP_EOL;

        return ['files' => $fileCount, 'matches' => $matchCount];
    }

    // -------------------------------------------------------------------------
    // Analysis
    // -------------------------------------------------------------------------

    /**
     * @param array<string, true>                               $definedKeys
     * @param array<string, list<array{file:string, line:int}>> $usedKeys
     * @param array<string, true>                               $dynamicPrefixes
     * @return array{0: array<string, list<array{file:string, line:int}>>, 1: array<string, true>}
     */
    private function analyse(array $definedKeys, array $usedKeys, array $dynamicPrefixes): array
    {
        $missingKeys = [];
        $orphanKeys  = [];

        foreach ($usedKeys as $key => $occurrences) {
            if (!isset($definedKeys[$key])) {
                $missingKeys[$key] = $occurrences;
            }
        }

        foreach (array_keys($definedKeys) as $key) {
            if (!isset($usedKeys[$key])) {
                // Reserved keys
                if (preg_match('/^Error\d{3}$/', $key) || preg_match('/^Help_[A-Za-z]+$/', $key)) {
                    continue;
                }
                // Covered by a dynamic prefix
                foreach (array_keys($dynamicPrefixes) as $prefix) {
                    if (str_starts_with($key, $prefix)) {
                        continue 2;
                    }
                }
                $orphanKeys[$key] = true;
            }
        }

        return [$missingKeys, $orphanKeys];
    }

    // -------------------------------------------------------------------------
    // Reporting
    // -------------------------------------------------------------------------

    /**
     * @param array{files: int, matches: int}                   $latteResult
     * @param array{files: int, matches: int}                   $phpResult
     * @param array{files: int, matches: int}                   $phpArrayResult
     * @param array<string, list<array{file:string, line:int}>> $usedKeys
     * @param array<string, true>                               $dynamicPrefixes
     */
    private function printScanSummary(
        array $latteResult,
        array $phpResult,
        array $phpArrayResult,
        array $usedKeys,
        array $dynamicPrefixes
    ): void {
        $this->dim(sprintf(
            "  Total: %d file(s) — %d occurrence(s) — %d unique key(s).",
            $latteResult['files'] + $phpResult['files'],
            $latteResult['matches'] + $phpResult['matches'] + $phpArrayResult['matches'],
            count($usedKeys)
        ));
        echo PHP_EOL . PHP_EOL;

        if (!empty($dynamicPrefixes)) {
            $this->dim(sprintf(
                "  Dynamic prefixes detected (%d): %s",
                count($dynamicPrefixes),
                implode(', ', array_keys($dynamicPrefixes))
            ));
            echo PHP_EOL . PHP_EOL;
        }
    }

    /**
     * @param array<string, list<array{file:string, line:int}>> $missingKeys
     * @param array<string, true>                               $orphanKeys
     */
    private function printReport(array $missingKeys, array $orphanKeys): int
    {
        $hasErrors = !empty($missingKeys) || !empty($orphanKeys);

        echo bold("═════════════════════════════════════════════════════════════") . PHP_EOL;

        // Missing keys
        if (empty($missingKeys)) {
            $this->success("✓ No missing keys. All translations are defined.");
        } else {
            ksort($missingKeys);
            $this->error(sprintf("✗ %d missing key(s) in Languages:", count($missingKeys)));
            echo PHP_EOL . PHP_EOL;

            foreach ($missingKeys as $key => $occurrences) {
                echo "  " . red("● ") . bold($key) . PHP_EOL;

                if ($this->verbose) {
                    foreach ($occurrences as $occ) {
                        $rel     = str_replace($this->scanDir . DIRECTORY_SEPARATOR, '', $occ['file']);
                        $lineStr = $occ['line'] > 0 ? sprintf(" (line %d)", $occ['line']) : ' (multi-line array)';
                        $this->dim(sprintf("      → %s%s", $rel, $lineStr));
                        echo PHP_EOL;
                    }
                } else {
                    $rel     = str_replace($this->scanDir . DIRECTORY_SEPARATOR, '', $occurrences[0]['file']);
                    $lineStr = $occurrences[0]['line'] > 0 ? sprintf(" (line %d)", $occurrences[0]['line']) : ' (multi-line array)';
                    $extra   = count($occurrences) > 1 ? sprintf(" (+%d other(s))", count($occurrences) - 1) : '';
                    $this->dim(sprintf("      → %s%s%s", $rel, $lineStr, $extra));
                    echo PHP_EOL;
                }

                $this->dim(sprintf(
                    "      SQL: INSERT INTO \"Languages\" VALUES (???,'%s','???','???','???');",
                    $key
                ));
                echo PHP_EOL . PHP_EOL;
            }
        }

        echo bold("─────────────────────────────────────────────────────────────") . PHP_EOL;

        // Orphan keys
        if (empty($orphanKeys)) {
            $this->success("✓ No orphan keys. All SQL translations are used.");
        } else {
            ksort($orphanKeys);
            $this->warning(sprintf(
                "⚠ %d orphan key(s) in Languages (defined but never used in .latte or .php):",
                count($orphanKeys)
            ));
            echo PHP_EOL . PHP_EOL;

            foreach (array_keys($orphanKeys) as $key) {
                echo "  " . yellow("◌ ") . $key . PHP_EOL;
            }
            echo PHP_EOL;
        }

        echo bold("═════════════════════════════════════════════════════════════") . PHP_EOL;
        echo PHP_EOL . yellow("Tip: run again with --verbose to see all occurrences.") . PHP_EOL;
        echo PHP_EOL;

        return $hasErrors ? 1 : 0;
    }

    // -------------------------------------------------------------------------
    // Console helpers
    // -------------------------------------------------------------------------

    private function title(string $text): void
    {
        echo bold("» $text") . PHP_EOL;
    }

    private function success(string $text): void
    {
        echo green($text) . PHP_EOL;
    }

    private function error(string $text): void
    {
        echo red($text) . PHP_EOL;
    }

    private function warning(string $text): void
    {
        echo yellow($text) . PHP_EOL;
    }

    private function dim(string $text): void
    {
        echo dim($text);
    }
}

// ─── Color helpers (global for simplicity) ────────────────────────────────────

function red(string $s): string
{
    return "\e[31m$s\e[0m";
}
function green(string $s): string
{
    return "\e[32m$s\e[0m";
}
function yellow(string $s): string
{
    return "\e[33m$s\e[0m";
}
function bold(string $s): string
{
    return "\e[1m$s\e[0m";
}
function dim(string $s): string
{
    return "\e[2m$s\e[0m";
}

// ─── CLI entry point ──────────────────────────────────────────────────────────

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (is_string($scriptName) && basename(__FILE__) === basename($scriptName)) {
    exit((new CheckMissingTranslation())->run());
}
