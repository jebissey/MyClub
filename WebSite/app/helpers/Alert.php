<?php

namespace app\helpers;

use PDO;

class Alert
{
    protected PDO $pdo;
    protected $fluent;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }

    public function getPendingSurveyResponses()
    {
        $query = "
        SELECT 
            p.Id AS PersonId, 
            p.Email, 
            a.Id AS ArticleId, 
            a.Title AS ArticleTitle, 
            s.Id AS SurveyId, 
            s.Question AS SurveyQuestion, 
            s.ClosingDate
        FROM Person p
        CROSS JOIN Survey s
        JOIN Article a ON s.IdArticle = a.Id
        LEFT JOIN Reply r ON r.IdSurvey = s.Id AND r.IdPerson = p.Id
        LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id AND pg.IdGroup = a.IdGroup
        WHERE 
            a.PublishedBy IS NOT NULL
            AND p.Inactivated = 0
            AND s.ClosingDate > date('now')
            AND (
                a.IdGroup IS NULL
                OR pg.IdGroup IS NOT NULL 
            )
            AND r.Id IS NULL
        ORDER BY s.ClosingDate, p.LastName, p.FirstName";

        return $this->pdo->query($query)->fetchAll();
    }

    public function getPendingDesignResponses()
    {
        $query = "
        SELECT 
            p.Id AS PersonId, 
            p.Email, 
            d.Id AS DesignId, 
            d.Name AS DesignName,
            d.Detail AS DesignDetail
        FROM Person p
        CROSS JOIN Design d
        LEFT JOIN DesignVote dv ON dv.IdDesign = d.Id AND dv.IdPerson = p.Id
        WHERE p.Inactivated = 0
            AND d.Status = 'UnderReview'
            AND dv.Id IS NULL
        ORDER BY d.LastUpdate";

        return $this->pdo->query($query)->fetchAll();
    }
}
