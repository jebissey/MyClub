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

// ─── Helper : scan ligne par ligne ───────────────────────────────────────────

/**
 * Scanne récursivement $dir pour les fichiers dont l'extension correspond à
 * $extensionRegex, et extrait :
 *   - les clés de traduction statiques via $patterns (groupe capture 1)
 *   - les préfixes de clés dynamiques ("prefix_{$var}")|translate  (latte)
 *
 * @param string[]                                           $patterns
 * @param array<string, list<array{file:string, line:int}>> $usedKeys        (par référence)
 * @param array<string, true>                               $dynamicPrefixes (par référence)
 * @return array{files: int, matches: int}
 */
function scanFiles(
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

    foreach ($iterator as $fileInfo) {
        $filePath = $fileInfo->getPathname();
        $fileCount++;

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            echo yellow("  ⚠ Impossible de lire : $filePath") . PHP_EOL;
            continue;
        }

        foreach ($lines as $lineNo => $lineContent) {
            // ── Clés statiques ─────────────────────────────────────────────
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

            // ── Préfixes dynamiques latte : ("prefix_{$var}")|translate ────
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

// ─── Helper : scan des tableaux de traduction PHP ────────────────────────────

/**
 * Détecte dans les fichiers PHP le pattern :
 *
 *   $keys = ['key1', 'key2', ...];
 *   foreach ($keys as $k) {
 *       $trans[$k] = ($this->t)('prefix.' . $k);
 *   }
 *
 * Extrait toutes les clés complètes (préfixe + literal) et les enregistre
 * dans $usedKeys.
 *
 * @param array<string, list<array{file:string, line:int}>> $usedKeys (par référence)
 * @return array{files: int, matches: int}
 */
function scanPhpTranslationArrays(string $dir, array &$usedKeys): array
{
    $fileCount  = 0;
    $matchCount = 0;

    $iterator = new RegexIterator(
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        ),
        '/\.php$/i',
        RegexIterator::MATCH
    );

    foreach ($iterator as $fileInfo) {
        $filePath = $fileInfo->getPathname();
        $fileCount++;

        $content = file_get_contents($filePath);
        if ($content === false) {
            continue;
        }

        // ── Étape 1 : trouver les appels translate avec concaténation ─────
        // ($this->t)('prefix.' . $varName)  ou  ->translate('prefix.' . $varName)
        // Capture : [1] => préfixe  [2] => nom de variable
        $translatePattern = '/(?:->translate|\(\s*\$this->t\s*\)|->t)\s*\(\s*'
            . '[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\.\s*\$([a-zA-Z_][a-zA-Z0-9_]*)'
            . '\s*\)/';

        if (!preg_match_all($translatePattern, $content, $tMatches, PREG_SET_ORDER)) {
            continue;
        }

        // ── Étape 2 : pour chaque (préfixe, variable), extraire le tableau ─
        foreach ($tMatches as $tMatch) {
            $prefix  = $tMatch[1]; // ex: 'loan.'
            $varName = $tMatch[2]; // ex: 'k'  -> on cherche l'array $keys

            // Chercher le nom de la variable tableau via : foreach ($arrayVar as $varName)
            $foreachPattern = '/foreach\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s+as\s+\$'
                . preg_quote($varName, '/') . '\s*\)/';
            if (!preg_match($foreachPattern, $content, $fMatch)) {
                continue;
            }
            $arrayVar = $fMatch[1]; // ex: 'keys'

            // Extraire le contenu du tableau $arrayVar = [ ... ]
            $arrayPattern = '/\$' . preg_quote($arrayVar, '/') . '\s*=\s*\[([^\]]+)\]/s';
            if (!preg_match($arrayPattern, $content, $aMatch)) {
                continue;
            }
            $arrayBody = $aMatch[1];

            // Extraire tous les string literals du tableau
            if (!preg_match_all('/[\'"]([a-zA-Z0-9_.]+)[\'"]/', $arrayBody, $kMatches)) {
                continue;
            }

            foreach ($kMatches[1] as $suffix) {
                $fullKey = $prefix . $suffix;
                $matchCount++;
                $usedKeys[$fullKey][] = [
                    'file' => $filePath,
                    'line' => 0, // ligne exacte non triviale en mode multi-lignes
                ];
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

/** @var array<string, true> $dynamicPrefixes */
$dynamicPrefixes = [];

// — .latte ─────────────────────────────────────────────────────────────────────
echo bold("» Scan des fichiers .latte dans :") . PHP_EOL;
echo dim("  $scanDir") . PHP_EOL . PHP_EOL;

$latteResult = scanFiles($scanDir, '/\.latte$/i', [
    '/\{=\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\|translate\s*\}/',                  // {='some.key'|translate}
    '/\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\|translate\s*\)/',                   // ('some.key'|translate)
    '/=>\s*[\'"]([a-z][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+){1,})[\'"]\s*[,\]]/',  // => 'some.key', (valeur de tableau)
], $usedKeys, $dynamicPrefixes);

echo sprintf("  %d fichier(s) — %d occurrence(s) trouvée(s).",
    $latteResult['files'], $latteResult['matches']
) . PHP_EOL . PHP_EOL;

// — .php (patterns ligne par ligne) ───────────────────────────────────────────
echo bold("» Scan des fichiers .php dans :") . PHP_EOL;
echo dim("  $scanDir") . PHP_EOL . PHP_EOL;

$phpResult = scanFiles($scanDir, '/\.php$/i', [
    // $this->languagesDataHelper->translate('key')
    '/->translate\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\)/',

    // $this->t('key') ou ($this->t)('key')
    '/(?:->t|\$t)\s*\)?\s*\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\)/',

    // $this->db->get('Languages', ['Name' => 'key'])
    '/->get\(\s*[\'"]Languages[\'"]\s*,\s*\[\s*[\'"]Name[\'"]\s*=>\s*[\'"]([a-zA-Z0-9_.]+)[\'"]\s*\]/',
], $usedKeys, $dynamicPrefixes);

echo sprintf("  %d fichier(s) — %d occurrence(s) trouvée(s).",
    $phpResult['files'], $phpResult['matches']
) . PHP_EOL . PHP_EOL;

// — .php (tableaux de traduction multi-lignes) ─────────────────────────────────
echo bold("» Scan des tableaux de traduction PHP (multi-lignes)...") . PHP_EOL;

$phpArrayResult = scanPhpTranslationArrays($scanDir, $usedKeys);

echo sprintf("  %d fichier(s) — %d clé(s) extraite(s) depuis les tableaux.",
    $phpArrayResult['files'], $phpArrayResult['matches']
) . PHP_EOL . PHP_EOL;

// — Résumé global ─────────────────────────────────────────────────────────────

echo dim(sprintf(
    "  Total : %d fichier(s) — %d occurrence(s) — %d clé(s) unique(s).",
    $latteResult['files'] + $phpResult['files'],
    $latteResult['matches'] + $phpResult['matches'] + $phpArrayResult['matches'],
    count($usedKeys)
)) . PHP_EOL . PHP_EOL;

if (!empty($dynamicPrefixes)) {
    echo dim(sprintf(
        "  Préfixes dynamiques détectés (%d) : %s",
        count($dynamicPrefixes),
        implode(', ', array_keys($dynamicPrefixes))
    )) . PHP_EOL . PHP_EOL;
}

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
        // Clés réservées : Error + 3 chiffres, Help_ + lettres
        if (preg_match('/^Error\d{3}$/', $key) || preg_match('/^Help_[A-Za-z]+$/', $key)) {
            continue;
        }
        // Clé couverte par un préfixe dynamique latte (ex: "label_{$var}")
        foreach (array_keys($dynamicPrefixes) as $prefix) {
            if (str_starts_with($key, $prefix)) {
                continue 2;
            }
        }
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
                $rel     = str_replace($scanDir . DIRECTORY_SEPARATOR, '', $occ['file']);
                $lineStr = $occ['line'] > 0 ? sprintf(" (ligne %d)", $occ['line']) : ' (tableau multi-lignes)';
                echo dim(sprintf("      → %s%s", $rel, $lineStr)) . PHP_EOL;
            }
        } else {
            $rel     = str_replace($scanDir . DIRECTORY_SEPARATOR, '', $occurrences[0]['file']);
            $lineStr = $occurrences[0]['line'] > 0 ? sprintf(" (ligne %d)", $occurrences[0]['line']) : ' (tableau multi-lignes)';
            $extra   = count($occurrences) > 1 ? sprintf(" (+%d autre(s))", count($occurrences) - 1) : '';
            echo dim(sprintf("      → %s%s%s", $rel, $lineStr, $extra)) . PHP_EOL;
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