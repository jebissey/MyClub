<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V102ToV103Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Articles page: new "Carousel" column and filter (number of photos in the gallery)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('article.label.carousel',
'Gallery',
'Galerie',
'Galeria');

-- Generic yes/no values displayed in tables, and "all" option of yes/no filters
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('table.value.yes',
'yes',
'oui',
'tak'),

('table.value.no',
'no',
'non',
'nie'),

('table.value.all',
'All',
'Tous',
'Wszystkie');

-- Rebuild article_list_view: Carousel is 'non' without gallery, 'oui (N)' otherwise
DROP VIEW IF EXISTS "main"."article_list_view";
CREATE VIEW article_list_view AS
    SELECT
        Article.Id,
        Article.CreatedBy,
        Article.Title,
        Article.LastUpdate,
        Article.PublishedBy,
        Article.OnlyForMembers,
        Article.IdGroup,
        Article.Content,
        (
            SELECT COUNT(*)
            FROM Message
            WHERE Message.ArticleId = Article.Id
        ) AS Messages,
        CASE
            WHEN COALESCE(CarouselTotal.Total, 0) = 0 THEN 'non'
            ELSE 'oui (' || CarouselTotal.Total || ')'
        END AS Carousel,
        CASE
            WHEN Article.PublishedBy IS NULL THEN 'non'
            ELSE 'oui'
        END AS Published,
        CASE
            WHEN Article.OnlyForMembers = 1 THEN 'oui'
            ELSE 'non'
        END AS ForMembers,
        CASE
            WHEN Survey.IdArticle IS NULL THEN 'non'
            ELSE 'oui'
        END AS Pool,
        CASE
            WHEN Survey.IdArticle IS NULL THEN ''
            ELSE (
                CASE
                    WHEN Survey.ClosingDate < CURRENT_DATE THEN 'clos'
                    ELSE strftime('%d/%m/%Y', Survey.ClosingDate)
                END
                || ' (' || COALESCE((SELECT COUNT(*) FROM Reply WHERE Reply.IdSurvey = Survey.Id), 0) || ') '
                || CASE Survey.Visibility
                    WHEN 'all' THEN '👁️‍🗨️👥'
                    WHEN 'allAfterClosing' THEN '👁️‍🗨️👥📅'
                    WHEN 'voters' THEN '👁️‍🗨️🗳️'
                    WHEN 'votersAfterClosing' THEN '👁️‍🗨️🗳️📅'
                    WHEN 'redactor' THEN '👁️‍🗨️📝'
                    ELSE ''
                END
            )
        END AS PoolDetail,
        CASE
            WHEN Individual.NickName != '' AND Individual.NickName IS NOT NULL
            THEN Individual.FirstName || ' ' || Individual.LastName || ' (' || Individual.NickName || ')'
            ELSE Individual.FirstName || ' ' || Individual.LastName
        END AS PersonName,
        "Group".Name AS GroupName,
        CASE
            WHEN EXISTS (
                SELECT 1 FROM MenuItem
                WHERE MenuItem.Url = '/menu/show/article/' || Article.Id
            ) THEN 'oui'
            ELSE 'non'
        END AS Menu
    FROM Article
    INNER JOIN Member ON Article.CreatedBy = Member.Id
    INNER JOIN Individual ON Member.Id = Individual.Id
    LEFT JOIN Survey ON Article.Id = Survey.IdArticle
    LEFT JOIN "Group" ON "Group".Id = Article.IdGroup
    LEFT JOIN (
        SELECT IdArticle, COUNT(*) AS Total
        FROM Carousel
        GROUP BY IdArticle
    ) AS CarouselTotal ON CarouselTotal.IdArticle = Article.Id;
SQL);

        return 103;
    }
}
