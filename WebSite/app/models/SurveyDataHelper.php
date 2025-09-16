<?php

namespace app\models;

use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\interfaces\NewsProviderInterface;

class SurveyDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application, private ArticleDataHelper $articleDataHelper)
    {
        parent::__construct($application);
    }

    public function articleHasSurveyNotClosed(int $articleId): object|bool
    {
        $sql = "
            SELECT Survey.*
            FROM Survey
            JOIN Article ON Survey.IdArticle = Article.Id
            WHERE Survey.IdArticle = :articleId
            AND Survey.ClosingDate >= datetime('now')
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $articleId]);
        $survey = $stmt->fetch();
        return $survey;
    }

    public function getWithCreator(int $articleId): object|bool
    {
        $article = $this->get('Article', ['Id' => $articleId], 'Id');
        if ($article === false) throw new QueryException("Article {$articleId} doesn't exist");

        $sql = "
            SELECT s.*, a.CreatedBy
            FROM Survey s
            INNER JOIN Article a ON s.IdArticle = a.Id
            WHERE s.IdArticle = :articleId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $articleId]);
        return $stmt->fetch();
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
                s.IdArticle,
                v.FirstName as VoterFirstName,
                v.LastName as VoterLastName
            FROM Reply r
            JOIN Survey s ON s.Id = r.IdSurvey
            JOIN Article a ON a.Id = s.IdArticle
            JOIN Person p ON p.Id = a.CreatedBy
            JOIN Person v On p.Id = r.IdPerson
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
                && $authorizationDataHelper->canPersonReadSurveyResults($this->articleDataHelper->getWithAuthor($survey->IdArticle), $connectedUser)
            ) {
                $news[] = [
                    'type' => 'survey',
                    'id' => $survey->IdArticle,
                    'title' => $survey->Question . " => ({$survey->VoterFirstName} {$survey->VoterLastName})",
                    'from' => $survey->FirstName . ' ' . $survey->LastName,
                    'date' => $survey->LastUpdate,
                    'url' => '/survey/results/' . $survey->IdArticle
                ];
            }
        }
        return $news;
    }
}
