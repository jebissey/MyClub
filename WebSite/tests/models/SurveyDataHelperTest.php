<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class SurveyDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Survey', [
            'Id',
            'IdArticle',
            'Question',
            'Options',
            'ClosingDate',
            'Visibility',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'CreatedBy',
            'PublishedBy',
            'IdGroup',
            'Title',
        ]);

        $this->assertColumnsExist($pdo, 'Reply', [
            'Id',
            'IdPerson',
            'IdSurvey',
            'Answers',
            'LastUpdate',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Inactivated',
        ]);

        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'Id',
            'IdMember',
            'IdGroup',
        ]);
    }

    public function testArticleHasSurveyNotClosedReliesOnExistingColumns(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Survey', [
            'Id',
            'IdArticle',
            'ClosingDate',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
        ]);
    }

    public function testGetWithCreatorReliesOnExistingColumns(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Survey', [
            'Id',
            'Question',
            'Options',
            'IdArticle',
            'ClosingDate',
            'Visibility',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'CreatedBy',
        ]);
    }

    public function testGetRepliesForActivePersonsReliesOnExistingColumns(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Reply', [
            'Id',
            'IdPerson',
            'IdSurvey',
            'Answers',
            'LastUpdate',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Inactivated',
        ]);
    }

    public function testGetPendingSurveyResponsesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPendingSurveyResponsesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Individual i', $sql);
        $this->assertStringContainsString('INNER JOIN Member m ON m.Id = i.Id', $sql);
        $this->assertStringContainsString('CROSS JOIN Survey s', $sql);
        $this->assertStringContainsString('JOIN Article a ON s.IdArticle = a.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN Reply r ON r.IdSurvey = s.Id AND r.IdPerson = i.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN MemberGroup mg ON mg.IdMember = i.Id AND mg.IdGroup = a.IdGroup', $sql);
        $this->assertStringContainsString('a.PublishedBy IS NOT NULL', $sql);
        $this->assertStringContainsString('m.Inactivated = 0', $sql);
        $this->assertStringContainsString("s.ClosingDate > date('now')", $sql);
        $this->assertStringContainsString('r.Id IS NULL', $sql);
    }

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Reply r', $sql);
        $this->assertStringContainsString('JOIN Survey s ON s.Id = r.IdSurvey', $sql);
        $this->assertStringContainsString('JOIN Article a ON a.Id = s.IdArticle', $sql);
        $this->assertStringContainsString('JOIN Individual p ON p.Id = a.CreatedBy', $sql);
        $this->assertStringContainsString('JOIN Individual v ON v.Id = r.IdPerson', $sql);
        $this->assertStringContainsString('WHERE r.LastUpdate >= :searchFrom', $sql);
        $this->assertStringContainsString('GROUP BY s.Id', $sql);
    }

    public function testArticleHasSurveyNotClosedSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getArticleHasSurveyNotClosedSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Survey.*', $sql);
        $this->assertStringContainsString('FROM Survey', $sql);
        $this->assertStringContainsString('JOIN Article ON Survey.IdArticle = Article.Id', $sql);
        $this->assertStringContainsString('WHERE Survey.IdArticle = :articleId', $sql);
        $this->assertStringContainsString("AND Survey.ClosingDate >= datetime('now')", $sql);
    }

    public function testGetWithCreatorSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getWithCreatorSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Survey s', $sql);
        $this->assertStringContainsString('INNER JOIN Article a ON s.IdArticle = a.Id', $sql);
        $this->assertStringContainsString('WHERE s.IdArticle = :articleId', $sql);
    }

    public function testGetRepliesForActivePersonsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getRepliesForActivePersonsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Reply r', $sql);
        $this->assertStringContainsString('JOIN Individual i ON r.IdPerson = i.Id', $sql);
        $this->assertStringContainsString('INNER JOIN Member m ON m.Id = i.Id', $sql);
        $this->assertStringContainsString('WHERE r.IdSurvey = :surveyId and m.Inactivated = 0', $sql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from SurveyDataHelper)
    // -------------------------------------------------------------------------

    private function getArticleHasSurveyNotClosedSql(): string
    {
        return "
            SELECT Survey.*
            FROM Survey
            JOIN Article ON Survey.IdArticle = Article.Id
            WHERE Survey.IdArticle = :articleId
            AND Survey.ClosingDate >= datetime('now')
            LIMIT 1
";
    }

    private function getWithCreatorSql(): string
    {
        return "
            SELECT s.Id, s.Question, s.Options, s.IdArticle, s.ClosingDate, s.Visibility, a.CreatedBy
            FROM Survey s
            INNER JOIN Article a ON s.IdArticle = a.Id
            WHERE s.IdArticle = :articleId
";
    }

    private function getRepliesForActivePersonsSql(): string
    {
        return "
            SELECT r.Id, r.IdPerson, r.IdSurvey, r.Answers, r.LastUpdate
            FROM Reply r
            JOIN Individual i ON r.IdPerson = i.Id
            INNER JOIN Member m ON m.Id = i.Id
            WHERE r.IdSurvey = :surveyId and m.Inactivated = 0
";
    }

    private function getPendingSurveyResponsesSql(): string
    {
        return "
            SELECT 
                i.Id AS PersonId, 
                i.Email, 
                a.Id AS ArticleId, 
                a.Title AS ArticleTitle, 
                s.Id AS SurveyId, 
                s.Question AS SurveyQuestion, 
                s.ClosingDate
            FROM Individual i
            INNER JOIN Member m ON m.Id = i.Id
            CROSS JOIN Survey s
            JOIN Article a ON s.IdArticle = a.Id
            LEFT JOIN Reply r ON r.IdSurvey = s.Id AND r.IdPerson = i.Id
            LEFT JOIN MemberGroup mg ON mg.IdMember = i.Id AND mg.IdGroup = a.IdGroup
            WHERE 
                a.PublishedBy IS NOT NULL
                AND m.Inactivated = 0
                AND s.ClosingDate > date('now')
                AND (
                    a.IdGroup IS NULL
                    OR mg.IdGroup IS NOT NULL 
                )
                AND r.Id IS NULL
            ORDER BY s.ClosingDate, i.LastName, i.FirstName";
    }

    private function getNewsSql(): string
    {
        return "
            SELECT 
                p.FirstName,
                p.LastName,
                s.Question,
                s.ClosingDate,
                s.Visibility,
                s.IdArticle,
                MAX(r.LastUpdate) AS LastActivity,
                GROUP_CONCAT(
                    v.FirstName || ' ' || v.LastName || ' (' ||
                    strftime('%d/%m/%Y', r.LastUpdate) || ')',
                    ', '
                ) AS Voters
            FROM Reply r
            JOIN Survey s ON s.Id = r.IdSurvey
            JOIN Article a ON a.Id = s.IdArticle
            JOIN Individual p ON p.Id = a.CreatedBy
            JOIN Individual v ON v.Id = r.IdPerson
            WHERE r.LastUpdate >= :searchFrom
            GROUP BY s.Id
            ORDER BY LastActivity DESC
";
    }
}