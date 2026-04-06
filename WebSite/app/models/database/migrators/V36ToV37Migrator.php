<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V36ToV37Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('dbbrowser.records.empty',
'No records found.',
'Aucun enregistrement trouvé.',
'Nie znaleziono żadnych rekordów.'),

('dbbrowser.delete.confirm',
'Are you sure you want to delete this record?',
'Voulez-vous vraiment supprimer cet enregistrement ?',
'Czy na pewno chcesz usunąć ten rekord?');
SQL;
        $pdo->exec($sql);

        return 37;
    }
}
