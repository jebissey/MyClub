<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V97ToV98Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<'SQL'
-- Game Manager: page title and sidebar menu
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('gameManager.title',
 'Game manager',
 'Gestion des jeux',
 'Zarządzanie grami'),

('gameManager.sidebar.heading',
 'Categories',
 'Catégories',
 'Kategorie'),

('gameManager.sidebar.arcade',
 'Arcade games',
 'Jeux d''arcade',
 'Gry zręcznościowe'),

('gameManager.sidebar.puzzle',
 'Puzzle games',
 'Jeux de réflexion',
 'Gry logiczne'),

('gameManager.sidebar.memory',
 'Memory games',
 'Jeux de mémoire',
 'Gry pamięciowe'),

('gameManager.sidebar.other',
 'Other',
 'Autres',
 'Inne'),

-- Game Manager: sidebar entries
('gameManager.sidebar.arcade.leapfrog',
 'Leapfrog',
 'Leapfrog',
 'Leapfrog'),

('gameManager.sidebar.puzzle.makethetarget',
 'Make the target',
 'Make the target',
 'Make the target'),

('gameManager.sidebar.other.astronomy',
 'Astronomy',
 'Astronomie',
 'Astronomia');
SQL);

        return 98;
    }
}
