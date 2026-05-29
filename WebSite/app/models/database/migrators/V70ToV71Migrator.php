<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V70ToV71Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("
            CREATE VIEW public_article_list_view AS            
			SELECT
                Id,
                LastUpdate,
                Title,
                CASE
                    WHEN Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name = 'Home_FeaturedArticleId' AND Value != '0'
                    ) THEN 'Home_Featured'

                    WHEN Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name = 'Home_FooterArticleId' AND Value != '0'
                    ) THEN 'Home_Footer'

                    WHEN Id IN (
                        SELECT CAST(REPLACE(Url, '/menu/show/article/', '') AS INTEGER)
                        FROM MenuItem
                        WHERE ForAnonymous = 1
                        AND Url LIKE '/menu/show/article/%'
                    ) THEN 'Menu'

                    ELSE 'Public'
                END AS ReferenceSource
            FROM Article
            WHERE
                (
                    (IdGroup IS NULL AND OnlyForMembers = 0 AND PublishedBy IS NOT NULL)
                    OR Id IN (
                        SELECT CAST(REPLACE(Url, '/menu/show/article/', '') AS INTEGER)
                        FROM MenuItem
                        WHERE ForAnonymous = 1
                        AND Url LIKE '/menu/show/article/%'
                    )
                    OR Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name IN ('Home_FeaturedArticleId', 'Home_FooterArticleId')
                        AND Value != '0'
                    )
                )
            ORDER BY LastUpdate DESC            
        ");

        return 71;
    }
}
