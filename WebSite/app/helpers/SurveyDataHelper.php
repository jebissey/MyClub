<?php

namespace app\helpers;

use app\interfaces\NewsProviderInterface;

class SurveyDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function articleHasSurveyNotClosed(int $articleId): object|bool
    {
        return $this->fluent
            ->from('Survey')
            ->join('Article ON Survey.IdArticle = Article.Id')
            ->where('IdArticle', $articleId)
            ->where('ClosingDate >= ?', date('now'))
            ->fetch();
    }

    public function getWithCreator(int $articleId): object|bool
    {
        return $this->fluent->from('Survey')->join('Article ON Survey.IdArticle = Article.Id')->where('IdArticle', $articleId)->select('Article.CreatedBy')->fetch();
    }

    public function getPendingSurveyResponses(): array
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

    public function getNews(ConnectedUser $connectedUser, $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) return $news;
        $sql = "
            SELECT 
                p.FirstName, 
                p.LastName, 
                s.Question, 
                s.ClosingDate, 
                s.Visibility, 
                r.LastUpdate, 
                s.IdArticle
            FROM Reply r
            JOIN Survey s ON s.Id = r.IdSurvey
            JOIN Article a ON a.Id = s.IdArticle
            JOIN Person p ON p.Id = a.CreatedBy
            WHERE r.LastUpdate >= :searchFrom
            ORDER BY r.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':searchFrom' => $searchFrom]);
        $surveys = $stmt->fetchAll();
        $news = [];
        $authorizationDataHelper = new AuthorizationDataHelper($this->application);
        foreach ($surveys as $survey) {
            if (
                $authorizationDataHelper->getArticle($survey->IdArticle, $connectedUser)
                && $authorizationDataHelper->canPersonReadSurveyResults((new ArticleDataHelper($this->application))->getWithAuthor($survey->IdArticle), $connectedUser)
            ) {
                $news[] = [
                    'type' => 'survey',
                    'id' => $survey->IdArticle,
                    'title' => $survey->Question,
                    'from' => $survey->FirstName . ' ' . $survey->LastName,
                    'date' => $survey->LastUpdate,
                    'url' => '/surveys/results/' . $survey->IdArticle
                ];
            }
        }
        return $news;
    }
}
