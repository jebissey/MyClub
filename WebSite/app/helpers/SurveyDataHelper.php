<?php

namespace app\helpers;

class SurveyDataHelper extends Data
{
    public function articleHasSurvey($articleId)
    {
        return $this->fluent
            ->from('Survey')
            ->join('Article ON Survey.IdArticle = Article.Id')
            ->where('IdArticle', $articleId)
            ->where('ClosingDate <= ?', date('now'))
            ->fetch();
    }

    public function getWithCreator($articleId)
    {
        return $this->fluent->from('Survey')->join('Article ON Survey.IdArticle = Article.Id')->where('IdArticle', $articleId)->select('Article.CreatedBy')->fetch();
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
}
