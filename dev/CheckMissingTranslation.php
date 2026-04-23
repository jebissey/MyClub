#!/usr/bin/env php
<?php

/**
 * CheckMissingTranslation.php
 *
 * Vérifie que toutes les clés de traduction utilisées dans les fichiers .latte
 * et .php sont bien définies dans la base de données SQL (table Languages).
 *
 * Usage : php CheckMissingTranslation.php [--verbose]
 */

// ─── Configuration ────────────────────────────────────────────────────────────

$scriptDir = __DIR__;              // MyClub/dev/
$rootDir   = dirname($scriptDir);  // MyClub/

$scanDir = $rootDir . '/WebSite/app';
$sqlFile = $rootDir . '/WebSite/app/models/database/MyClub.sqlite.sql';

$verbose = in_array('--verbose', $argv ?? [], true);

// ─── Couleurs console ─────────────────────────────────────────────────────────

function red(string $s): string    { return "\e[31m$s\e[0m"; }
function green(string $s): string  { return "\e[32m$s\e[0m"; }
function yellow(string $s): string { return "\e[33m$s\e[0m"; }
function bold(string $s): string   { return "\e[1m$s\e[0m"; }
function dim(string $s): string    { return "\e[2m$s\e[0m"; }

// ─── Vérifications préalables ─────────────────────────────────────────────────

if (!is_dir($scanDir)) {
    echo red("✗ Dossier introuvable : $scanDir") . PHP_EOL;
    exit(1);
}

if (!is_file($sqlFile)) {
    echo red("✗ Fichier SQL introuvable : $sqlFile") . PHP_EOL;
    exit(1);
}

// ─── Helper : scan de fichiers ────────────────────────────────────────────────

/**
 * Scanne récursivement $dir pour les fichiers dont l'extension correspond à
 * $extensionRegex, et extrait les clés de traduction via un ou plusieurs
 * patterns (groupe de capture 1 de chaque pattern).
 *
 * @param string[]                                           $patterns
 * @param array<string, list<array{file:string, line:int}>> $usedKeys  (par référence)
 * @return array{files: int, matches: int}
 */
function scanFiles(
    string $dir,
    string $extensionRegex,
    array  $patterns,
    array  &$usedKeys
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

    foreach ($iterator as $fileInfo) {
        $filePath = $fileInfo->getPathname();
        $fileCount++;

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            echo yellow("  ⚠ Impossible de lire : $filePath") . PHP_EOL;
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
        }
    }

    return ['files' => $fileCount, 'matches' => $matchCount];
}

// ─── Étape 1 : Clés définies dans le SQL ─────────────────────────────────────

echo bold("» Lecture du fichier SQL...") . PHP_EOL;
echo dim("  $sqlFile") . PHP_EOL . PHP_EOL;

$sqlContent = file_get_contents($sqlFile);
if ($sqlContent === false) {
    echo red("✗ Impossible de lire le fichier SQL.") . PHP_EOL;
    exit(1);
}

// INSERT INTO "Languages" VALUES (id,'clé','...','...','...');
$definedKeys = [];
if (preg_match_all('/INSERT\s+INTO\s+"Languages"\s+VALUES\s*\(\s*\d+\s*,\s*\'([^\']+)\'/i', $sqlContent, $m)) {
    foreach ($m[1] as $key) {
        $definedKeys[$key] = true;
    }
}

echo green(sprintf("  %d clé(s) trouvée(s) dans Languages.", count($definedKeys))) . PHP_EOL . PHP_EOL;

// ─── Étape 2 : Clés utilisées dans .latte et .php ────────────────────────────

/** @var array<string, list<array{file:string, line:int}>> $usedKeys */
$usedKeys = [];

// — .latte ─────────────────────────────────────────────────────────────────────
// {='some.key'|translate}

echo bold("» Scan des fichiers .latte dans :") . PHP_EOL;
echo dim("  $scanDir") . PHP_EOL . PHP_EOL;

$latteResult = scanFiles($scanDir, '/\.latte$/i', [
     '/\{=\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\|translate\s*\}/',               // {='some.key'|translate}
     '/\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\|translate\s*\)/',                // ('some.key'|translate)
     '/=>\s*[\'"]([a-z][a-zA-Z0-9]*(?:\.[a-zA-Z0-9]+){1,})[\'"]\s*[,\]]/',  // => 'some.key', (valeur de tableau)
 ], $usedKeys);

echo sprintf("  %d fichier(s) — %d occurrence(s) trouvée(s).",
    $latteResult['files'], $latteResult['matches']
) . PHP_EOL . PHP_EOL;

// — .php ───────────────────────────────────────────────────────────────────────
// Syntaxe 1 : ->translate('some.key')
// Syntaxe 2 : ->get('Languages', ['Name' => 'some.key'], ...)

echo bold("» Scan des fichiers .php dans :") . PHP_EOL;
echo dim("  $scanDir") . PHP_EOL . PHP_EOL;

$phpResult = scanFiles($scanDir, '/\.php$/i', [
    '/->translate\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\)/',
    '/->get\(\s*[\'"]Languages[\'"]\s*,\s*\[\s*[\'"]Name[\'"]\s*=>\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\]/',
], $usedKeys);

echo sprintf("  %d fichier(s) — %d occurrence(s) trouvée(s).",
    $phpResult['files'], $phpResult['matches']
) . PHP_EOL . PHP_EOL;

// — Résumé global ─────────────────────────────────────────────────────────────

echo dim(sprintf(
    "  Total : %d fichier(s) — %d occurrence(s) — %d clé(s) unique(s).",
    $latteResult['files'] + $phpResult['files'],
    $latteResult['matches'] + $phpResult['matches'],
    count($usedKeys)
)) . PHP_EOL . PHP_EOL;

// ─── Étape 3 : Analyse ───────────────────────────────────────────────────────

$missingKeys = []; // utilisées dans .latte/.php mais absentes du SQL
$orphanKeys  = []; // définies dans le SQL mais jamais utilisées

foreach ($usedKeys as $key => $occurrences) {
    if (!isset($definedKeys[$key])) {
        $missingKeys[$key] = $occurrences;
    }
}

foreach (array_keys($definedKeys) as $key) {
    if (!isset($usedKeys[$key])) {
        $orphanKeys[$key] = true;
    }
}

// ─── Rapport final ────────────────────────────────────────────────────────────

$hasErrors = !empty($missingKeys) || !empty($orphanKeys);

echo bold("═════════════════════════════════════════════════════════════") . PHP_EOL;

// ── Clés manquantes ──────────────────────────────────────────────────────────

if (empty($missingKeys)) {
    echo green("✓ Aucune clé manquante. Toutes les traductions sont définies.") . PHP_EOL;
} else {
    ksort($missingKeys);
    echo red(sprintf("✗ %d clé(s) manquante(s) dans Languages :", count($missingKeys))) . PHP_EOL . PHP_EOL;

    foreach ($missingKeys as $key => $occurrences) {
        echo "  " . red("● ") . bold($key) . PHP_EOL;

        if ($verbose) {
            foreach ($occurrences as $occ) {
                $rel = str_replace($scanDir . DIRECTORY_SEPARATOR, '', $occ['file']);
                echo dim(sprintf("      → %s (ligne %d)", $rel, $occ['line'])) . PHP_EOL;
            }
        } else {
            $rel   = str_replace($scanDir . DIRECTORY_SEPARATOR, '', $occurrences[0]['file']);
            $extra = count($occurrences) > 1 ? sprintf(" (+%d autre(s))", count($occurrences) - 1) : '';
            echo dim(sprintf("      → %s (ligne %d)%s", $rel, $occurrences[0]['line'], $extra)) . PHP_EOL;
        }

        echo dim(sprintf(
            "      SQL : INSERT INTO \"Languages\" VALUES (???,'%s','???','???','???');",
            $key
        )) . PHP_EOL . PHP_EOL;
    }
}

echo bold("─────────────────────────────────────────────────────────────") . PHP_EOL;

// ── Clés orphelines ──────────────────────────────────────────────────────────

if (empty($orphanKeys)) {
    echo green("✓ Aucune clé orpheline. Toutes les traductions SQL sont utilisées.") . PHP_EOL;
} else {
    ksort($orphanKeys);
    echo yellow(sprintf(
        "⚠ %d clé(s) orpheline(s) dans Languages (définies mais jamais utilisées dans .latte ni .php) :",
        count($orphanKeys)
    )) . PHP_EOL . PHP_EOL;

    foreach (array_keys($orphanKeys) as $key) {
        echo "  " . yellow("◌ ") . $key . PHP_EOL;
    }
    echo PHP_EOL;
}

echo bold("═════════════════════════════════════════════════════════════") . PHP_EOL;
echo PHP_EOL . yellow("Astuce : relancez avec --verbose pour voir toutes les occurrences.") . PHP_EOL;
echo PHP_EOL;

exit($hasErrors ? 1 : 0);