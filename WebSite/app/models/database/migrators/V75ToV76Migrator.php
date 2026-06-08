<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V75ToV76Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('user.statistics.message_distribution',  'Message distribution', 'Distribution des messages', 'Dystrybucja wiadomości'),
('user.statistics.chart.messages.y_axis', 'Messages',             'Messages',                  'Wiadomości'),
('user.statistics.chart.messages.x_axis', 'Members',              'Membres',                   'Członkowie');
SQL);

        return 76;
    }
}