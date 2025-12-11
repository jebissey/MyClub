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
            CREATE TABLE "KanbanCard" (
                "Id"	INTEGER,
                "Title"	TEXT NOT NULL,
                "Detail"	TEXT NOT NULL,
                "IdKanbanCardType"	INTEGER NOT NULL,
                PRIMARY KEY("Id"),
                FOREIGN KEY("IdKanbanCardType") REFERENCES "KanbanCardType"("Id")
            )'
        );
        $pdo->exec('
            CREATE TABLE "KanbanCardStatus" (
                "Id"	INTEGER,
                "IdKanbanCard"	INTEGER NOT NULL,
                "What"	TEXT NOT NULL,
                "Remark"	TEXT NOT NULL,
                "LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
                PRIMARY KEY("Id"),
                FOREIGN KEY("IdKanbanCard") REFERENCES "KanbanCard"("Id")
            )'
        );
        $pdo->exec('
            CREATE TABLE "KanbanCardType" (
                "Id"	INTEGER,
                "Label"	TEXT NOT NULL,
                "Detail"	TEXT NOT NULL,
                "IdKanbanProject"	INTEGER NOT NULL,
                PRIMARY KEY("Id"),
                FOREIGN KEY("IdKanbanProject") REFERENCES "KanbanProject"("Id")
            )'
        );
        $pdo->exec('
            CREATE TABLE "KanbanProject" (
                "Id"	INTEGER,
                "Title"	TEXT NOT NULL,
                "Detail"	TEXT NOT NULL,
                "IdPerson"	INTEGER NOT NULL,
                PRIMARY KEY("Id"),
                FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
            )'
        );
        $pdo->exec("INSERT INTO 'Authorization' VALUES (10,'KanbanDesigner')");

        return 6;
    }
}
