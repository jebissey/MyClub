<?php

declare(strict_types=1);

namespace app\models;

use \Envms\FluentPDO\Queries\Select;

use app\helpers\Application;
use app\helpers\ConnectedUser;

class ArticleTableDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getQuery(ConnectedUser $connectedUser, int $spotlightArticleId): Select
    {
        $stmt = $this->application->getPdo()->prepare("
            SELECT name 
            FROM sqlite_master 
            WHERE type = 'view' 
            AND name = 'article_list_view'
        ");
        $stmt->execute();
        $exists = $stmt->fetchColumn();
        if (!$exists) {
            $sql = "
                CREATE VIEW article_list_view AS
                SELECT 
                    Article.Id,
                    Article.CreatedBy,
                    Article.Title,
                    Article.LastUpdate,
                    Article.PublishedBy,
                    Article.OnlyForMembers,
                    Article.IdGroup,
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
                    'Group'.Name AS GroupName
                FROM Article
                INNER JOIN Person ON Article.CreatedBy = Person.Id
                LEFT JOIN Survey ON Article.Id = Survey.IdArticle
                LEFT JOIN 'Group' ON 'Group'.Id = Article.IdGroup
                ";
            $this->application->getPdo()->exec($sql);
        }

        $query = $this->fluent->from('article_list_view')
            ->select(null)
            ->select('Id, CreatedBy, Title, LastUpdate, PersonName, GroupName, Pool, PoolDetail, ForMembers, Messages')
            ->select('CASE 
                WHEN PublishedBy IS NULL THEN "non" 
                ELSE 
                    CASE 
                        WHEN Id = ' . $spotlightArticleId . ' THEN "oui 📌"
                        ELSE Published
                    END
                END AS Published');

        if ($connectedUser->person ?? false) {
            if (!$connectedUser->isEditor()) {
                $query = $query->where('(CreatedBy = ' . $connectedUser->person->Id . '
                    OR (PublishedBy IS NOT NULL 
                        AND (IdGroup IS NULL OR IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ' . $connectedUser->person->Id . '))
                    ))');
            }
        } else $query = $query->where('(IdGroup IS NULL AND OnlyForMembers = 0 AND PublishedBy IS NOT NULL)');
        $query = $query->orderBy('LastUpdate DESC');
        return $query;
    }
}
