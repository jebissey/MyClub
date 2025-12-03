<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V5ToV6Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("ALTER TABLE Person ADD COLUMN Notifications TEXT");
        $pdo->exec('
            CREATE TABLE "Kanban" (
                "Id"	INTEGER,
                "Title"	TEXT NOT NULL,
                "Detail"	TEXT NOT NULL,
                PRIMARY KEY("Id")
            )'
        );
        $pdo->exec('
            CREATE TABLE "KanbanStatus" (
                "Id"	INTEGER,
                "IdKanban"	INTEGER NOT NULL,
                "IdPerson"	INTEGER NOT NULL,
                "What"	TEXT NOT NULL,
                "Remark"	TEXT NOT NULL,
                "LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
                PRIMARY KEY("Id"),
                FOREIGN KEY("IdKanban") REFERENCES "Kanban"("Id"),
                FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
            )'
        );
        $pdo->exec("INSERT INTO 'Authorization' VALUES (10,'KanbanDesigner')");

        return 6;
    }
}
