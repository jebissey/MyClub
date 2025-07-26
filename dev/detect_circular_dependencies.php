<?php

$root = __DIR__ . '/../WebSite/app';

function getPhpFiles($dir)
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($rii as $file) {
        if (!$file->isDir() && pathinfo($file, PATHINFO_EXTENSION) === 'php') $files[] = $file->getPathname();
    }
    return $files;
}

function parseFileDependencies($filePath, $root)
{
    $content = file_get_contents($filePath);
    $dependencies = [];

    // Normalize slashes
    $normalizedPath = str_replace($root . '/', '', $filePath);

    // Match `use Some\Namespace\ClassName;`
    if (preg_match_all('/use\s+([a-zA-Z0-9_\\\\]+)\s*;/', $content, $matches)) {
        foreach ($matches[1] as $match) {
            $dependencies[] = $match;
        }
    }

    // Match new ClassName() or \Fully\Qualified\ClassName
    if (preg_match_all('/new\s+(\\\?[a-zA-Z0-9_\\\\]+)/', $content, $matches2)) {
        foreach ($matches2[1] as $match) {
            $dependencies[] = ltrim($match, '\\');
        }
    }

    // Also match static calls like ClassName::method()
    if (preg_match_all('/([a-zA-Z0-9_\\\\]+)::/', $content, $matches3)) {
        foreach ($matches3[1] as $match) {
            if (!in_array($match, $dependencies)) {
                $dependencies[] = $match;
            }
        }
    }

    return [$normalizedPath, array_unique($dependencies)];
}

function buildDependencyGraph($files, $root)
{
    $graph = [];

    foreach ($files as $file) {
        list($filename, $deps) = parseFileDependencies($file, $root);
        $graph[$filename] = [];

        foreach ($deps as $dep) {
            $graph[$filename][] = $dep;
        }
    }

    return $graph;
}

function detectCycles($graph)
{
    $visited = [];
    $recStack = [];
    $cycles = [];

    foreach (array_keys($graph) as $node) {
        dfs($node, $graph, $visited, $recStack, [], $cycles);
    }

    return $cycles;
}

function dfs($node, $graph, &$visited, &$recStack, $path, &$cycles)
{
    if (isset($recStack[$node]) && $recStack[$node]) {
        $cycleStart = array_search($node, $path);
        if ($cycleStart !== false) {
            $cycle = array_slice($path, $cycleStart);
            $cycles[] = $cycle;
        }
        return;
    }

    if (isset($visited[$node])) return;

    $visited[$node] = true;
    $recStack[$node] = true;
    $path[] = $node;

    foreach ($graph[$node] ?? [] as $neighbor) {
        dfs($neighbor, $graph, $visited, $recStack, $path, $cycles);
    }

    $recStack[$node] = false;
}

// --- Main ---
echo "🔍 Analyse des dépendances...\n";

$files = getPhpFiles($root);
$graph = buildDependencyGraph($files, $root);
$cycles = detectCycles($graph);

if (empty($cycles)) {
    echo "✅ Aucune dépendance circulaire détectée.\n";
} else {
    echo "⚠️ Dépendances circulaires détectées :\n";
    foreach ($cycles as $cycle) {
        echo " - " . implode(' → ', $cycle) . " → " . $cycle[0] . "\n";
    }
}
