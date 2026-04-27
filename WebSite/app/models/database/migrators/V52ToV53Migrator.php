<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V52ToV53Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("DROP VIEW IF EXISTS article_list_view");

        $pdo->exec("
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
                    ELSE 
                        (
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
                    WHEN Person.NickName != '' THEN Person.FirstName || ' ' || Person.LastName || ' (' || Person.NickName || ')' 
                    ELSE Person.FirstName || ' ' || Person.LastName 
                END AS PersonName,
                'Group'.Name AS GroupName,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM MenuItem
                        WHERE MenuItem.Url = '/menu/show/article/' || Article.Id
                    ) THEN 'oui'
                    ELSE 'non'
                END AS Menu
            FROM Article
            INNER JOIN Person ON Article.CreatedBy = Person.Id
            LEFT JOIN Survey ON Article.Id = Survey.IdArticle
            LEFT JOIN 'Group' ON 'Group'.Id = Article.IdGroup
        ");

        $pdo->exec("
            INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
            VALUES
            ('navbar.designer.loan', 'Loan', 'Prêt', 'Pożyczka'),
            ('period.day',     'Day',     'Jour',      'Dzień'),
            ('period.week',    'Week',    'Semaine',   'Tydzień'),
            ('period.month',   'Month',   'Mois',      'Miesiąc'),
            ('period.quarter', 'Quarter', 'Trimestre', 'Kwartał'),
            ('period.year',    'Year',    'An',        'Rok')
        ");

        return 53;
    }
}
