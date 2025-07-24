<?php
// Lecture du fichier dependencies.txt
$rawData = '';
$dependenciesFile = __DIR__ . '/dependencies.txt';

if (file_exists($dependenciesFile)) {
    $rawData = file_get_contents($dependenciesFile);
} else {
    // Données de fallback si le fichier n'existe pas
    $rawData = '../WebSite/app/apis/ArticleApi.php depends on: Exception, app\controllers\BaseController
../WebSite/app/apis/CarouselApi.php depends on: Exception, app\controllers\BaseController
../WebSite/app/helpers/TranslationManager.php depends on: DateTime, IntlDateFormatter, PDO';
}

// Fonction pour parser les données
function parseData($data) {
    $lines = array_filter(explode("\n", trim($data)));
    $dependencies = [];
    
    foreach ($lines as $line) {
        if (preg_match('/^(.+?) depends on: (.+)$/', $line, $matches)) {
            $file = str_replace('../WebSite/', '', $matches[1]);
            $deps = array_map('trim', explode(',', $matches[2]));
            $deps = array_filter($deps, function($dep) {
                return $dep && $dep !== '= []';
            });
            $dependencies[$file] = $deps;
        }
    }
    
    return $dependencies;
}

// Fonction pour classer les dépendances
function classifyDependencies($allDeps) {
    $external = [];
    $internal = [];
    
    foreach ($allDeps as $dep) {
        // Nettoyer les alias
        $cleanDep = preg_replace('/ as .+$/', '', $dep);
        
        if (strpos($cleanDep, 'app\\') === 0) {
            $internal[] = $cleanDep;
        } else {
            $external[] = $cleanDep;
        }
    }
    
    return [
        'external' => array_values(array_unique($external)),
        'internal' => array_values(array_unique($internal))
    ];
}

// Traitement des données
$dependencies = parseData($rawData);
$files = array_keys($dependencies);
sort($files);

// Collecter toutes les dépendances uniques
$allDeps = [];
foreach ($dependencies as $deps) {
    $allDeps = array_merge($allDeps, $deps);
}

$classified = classifyDependencies($allDeps);
sort($classified['external']);
sort($classified['internal']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau Croisé Dynamique des Dépendances</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .info {
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        
        .table-container {
            overflow-x: auto;
            max-height: 80vh;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .file-name {
            text-align: left;
            background-color: #f8f9fa;
            font-weight: bold;
            position: sticky;
            left: 0;
            z-index: 5;
            min-width: 200px;
        }
        
        .group-header {
            background-color: #e9ecef;
            font-weight: bold;
            color: #495057;
        }
        
        .external-group {
            background-color: #fff3cd;
        }
        
        .internal-group {
            background-color: #d4edda;
        }
        
        .dependency-yes {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        
        .dependency-no {
            background-color: #f8f9fa;
        }
        
        .dep-header {
            transform: rotate(-90deg);
            min-width: 30px;
            font-size: 10px;
            height: 150px;
            white-space: nowrap;
        }
        
        .legend {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .legend-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 10px;
        }
        
        .legend-color {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 5px;
            vertical-align: middle;
            border: 1px solid #ccc;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tableau Croisé Dynamique des Dépendances</h1>
        
        <?php if (!file_exists($dependenciesFile)): ?>
        <div class="info">
            <strong>Info:</strong> Le fichier dependencies.txt n'a pas été trouvé. Utilisation des données de démonstration.
            <br>Pour utiliser vos propres données, exécutez : <code>./depends.cmd > dependencies.txt</code>
        </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?= count($files) ?></div>
                <div>Fichiers</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= count($classified['external']) ?></div>
                <div>Dépendances Externes</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= count($classified['internal']) ?></div>
                <div>Dépendances Internes</div>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="file-name">Fichier</th>
                        <?php if (!empty($classified['external'])): ?>
                        <th class="group-header external-group" colspan="<?= count($classified['external']) ?>">
                            Dépendances Externes
                        </th>
                        <?php endif; ?>
                        <?php if (!empty($classified['internal'])): ?>
                        <th class="group-header internal-group" colspan="<?= count($classified['internal']) ?>">
                            Dépendances Internes
                        </th>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <th class="file-name"></th>
                        <?php foreach ($classified['external'] as $dep): ?>
                        <th class="external-group dep-header"><?= htmlspecialchars($dep) ?></th>
                        <?php endforeach; ?>
                        <?php foreach ($classified['internal'] as $dep): ?>
                        <th class="internal-group dep-header"><?= htmlspecialchars(str_replace('app\\', '', $dep)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td class="file-name"><?= htmlspecialchars($file) ?></td>
                        <?php
                        $fileDeps = $dependencies[$file] ?? [];
                        
                        // Dépendances externes
                        foreach ($classified['external'] as $dep) {
                            $cleanDep = preg_replace('/ as .+$/', '', $dep);
                            $hasDep = false;
                            foreach ($fileDeps as $fileDep) {
                                if (preg_replace('/ as .+$/', '', $fileDep) === $cleanDep) {
                                    $hasDep = true;
                                    break;
                                }
                            }
                            echo '<td class="' . ($hasDep ? 'dependency-yes' : 'dependency-no') . '">';
                            echo $hasDep ? '●' : '';
                            echo '</td>';
                        }
                        
                        // Dépendances internes
                        foreach ($classified['internal'] as $dep) {
                            $hasDep = in_array($dep, $fileDeps);
                            echo '<td class="' . ($hasDep ? 'dependency-yes' : 'dependency-no') . '">';
                            echo $hasDep ? '●' : '';
                            echo '</td>';
                        }
                        ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="legend">
            <h3>Légende:</h3>
            <div class="legend-item">
                <span class="legend-color dependency-yes"></span>
                Dépendance présente
            </div>
            <div class="legend-item">
                <span class="legend-color external-group"></span>
                Dépendances externes
            </div>
            <div class="legend-item">
                <span class="legend-color internal-group"></span>
                Dépendances internes
            </div>
        </div>
    </div>
</body>
</html>
