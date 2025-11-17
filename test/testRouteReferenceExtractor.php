<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use test\Infrastructure\RouteReferenceExtractor;

$extractor = new RouteReferenceExtractor();

// Extraire toutes les références
$references = $extractor->extractReferences(__DIR__ . '/../WebSite');

// Grouper par route
$grouped = $extractor->groupByRoute($references);

// Filtrer par type
$latteRefs = $extractor->filterByFileType($references, 'latte');
$jsRefs = $extractor->filterByFileType($references, 'js');

// Afficher les résultats
foreach ($references as $ref) {
    echo sprintf(
        "[%s] %s:%d - Route: %s (Type: %s)\n",
        $ref->fileType,
        $ref->getRelativePath(__DIR__),
        $ref->lineNumber,
        $ref->route,
        $ref->patternType
    );
}
echo "=======================\n";
$groupedReferences = $extractor->groupByRoute($references);

foreach ($groupedReferences as $route => $refs) {
    echo "\n📍 Route: $route\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($refs as $ref) {
        echo sprintf(
            "  [%s] %s:%d (Type: %s)\n",
            $ref->fileType,
            $ref->getRelativePath(dirname(__DIR__)), // Ajustez le chemin de base
            $ref->lineNumber,
            $ref->patternType
        );
        
        // Optionnel : afficher le contexte
        // echo "  Context: " . substr($ref->context, 0, 60) . "...\n";
    }
    
    echo "  Total: " . count($refs) . " occurrence(s)\n";
}

echo "\n=======================\n";
echo "Total routes trouvées: " . count($groupedReferences) . "\n";
echo "Total références: " . count($references) . "\n";
