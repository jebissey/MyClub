<?php

/*
// Inclure d'abord les fichiers nécessaires
require_once __DIR__ . '/data/DatabaseConnection.php';
require_once __DIR__ . '/data/BaseTable.php';
require_once __DIR__ . '/data/Tables/Person.php';

// Code de test pour vérifier la structure de la base
$pdo = DatabaseConnection::getInstance()->getPDO();
var_dump($pdo);
$query = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
$tables = $query->fetchAll(PDO::FETCH_COLUMN);
var_dump($tables);

// Votre code original
$person = new Person();
$user = $person->getByName('Toto');
var_dump($user);
*/


require_once __DIR__ . '/lib/Database/Database.php';
require_once __DIR__ . '/lib/Database/Tables/SiteData.php';

/*
$pdo = Database::getInstance()->getPDO();

$query = "SELECT * FROM metadata LIMIT 1";
$stmt = $pdo->query($query);

// Récupérer le résultat
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Afficher le premier élément
    print_r($row);
} else {
    echo "Aucun élément trouvé dans la table metadata.";
}

echo __DIR__ . "\n";
echo getcwd() . "\n";
*/

$siteData = new SiteData();
$result = $siteData->getById(1);
var_dump($result);
?>
