<?php

declare(strict_types=1);

namespace tests\models;

use PDO;
use PDOStatement;
use ReflectionMethod;
use app\helpers\Application;
use app\models\ArticleDataHelper;
use app\models\AuthorizationDataHelper;

/**
 * Schema and SQL validity tests for ArticleDataHelper.
 *
 * Ensures that every table/column referenced by the helper exists in the
 * shipped template and that the raw SQL statements can be prepared
 * against that schema.
 */
final class ArticleDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Title',
            'Content',
            'LastUpdate',
            'Timestamp',
            'CreatedBy',
            'IdGroup',
            'OnlyForMembers',
            'PublishedBy',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'FirstName',
            'LastName',
            'NickName',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        $this->assertColumnsExist($pdo, 'MenuItem', [
            'ForAnonymous',
            'Url',
        ]);

        $this->assertColumnsExist($pdo, 'Carousel', [
            'IdArticle',
            'Item',
        ]);
    }

    public function testGetArticlesForAllSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getArticlesForAllSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetArticlesForRssSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getArticlesForRssSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetAuthorsByArticleIdsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAuthorsByArticleIdsSql([1, 2, 3]);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetLatestArticleSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getLatestArticleSql([1, 2, 3]);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetWithAuthorSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getWithAuthorSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testInArticlesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getInArticlesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetNoGroupArticleIdsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getNoGroupArticleIdsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetArticleIdsForMembersSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getArticleIdsForMembersSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetArticleIdsByGroupsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getArticleIdsByGroupsSql([1, 2]);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testDoGetLatestArticlesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDoGetLatestArticlesSql([1, 2, 3], 5);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    // -------------------------------------------------------------------------
    // SQL extractors (mirror the queries from ArticleDataHelper)
    // -------------------------------------------------------------------------

    private function getArticlesForAllSql(): string
    {
        return "
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
        ";
    }

    private function getArticlesForRssSql(): string
    {
        return "
            SELECT
                Article.Id,
                Article.Title,
                Article.Content,
                Article.LastUpdate,
                Article.Timestamp AS CreationDate
            FROM Article
            WHERE Article.PublishedBy IS NOT NULL
            ORDER BY Article.LastUpdate DESC
            LIMIT 50
        ";
    }

    /** @param list<int> $articleIds */
    private function getAuthorsByArticleIdsSql(array $articleIds): string
    {
        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));

        return "
            SELECT 
                Article.Id,
                CASE 
                    WHEN Individual.NickName IS NOT NULL AND Individual.NickName != '' 
                    THEN Individual.FirstName || ' ' || Individual.LastName || ' (' || Individual.NickName || ')' 
                    ELSE Individual.FirstName || ' ' || Individual.LastName 
                END AS PersonName,
                Article.Title AS ArticleTitle
            FROM Article
            JOIN Individual ON Article.CreatedBy = Individual.Id
            WHERE Article.Id IN ($placeholders)
        ";
    }

    /** @param list<int> $articleIds */
    private function getLatestArticleSql(array $articleIds): string
    {
        $placeholders = [];
        foreach ($articleIds as $index => $id) {
            $placeholders[] = ":id$index";
        }

        return "
            SELECT Article.*, 
                Individual.FirstName, 
                Individual.LastName, 
                \"Group\".Name AS GroupName,
                Article.CreatedBy
            FROM Article
            LEFT JOIN Individual ON Individual.Id = Article.CreatedBy
            LEFT JOIN \"Group\" ON Article.IdGroup = \"Group\".Id
            WHERE Article.Id IN (" . implode(',', $placeholders) . ")
            ORDER BY Article.LastUpdate DESC
            LIMIT 1
        ";
    }

    private function getWithAuthorSql(): string
    {
        return "
            SELECT a.*, i.FirstName, i.LastName, i.NickName
            FROM Article a
            LEFT JOIN Individual i ON a.CreatedBy = i.Id
            WHERE a.Id = :id
            LIMIT 1
        ";
    }

    private function getInArticlesSql(): string
    {
        return "
            SELECT DISTINCT a.Id, a.Title
            FROM Article a
            WHERE a.Content LIKE :path

            UNION

            SELECT DISTINCT a.Id, a.Title
            FROM Article a
            INNER JOIN Carousel c ON c.IdArticle = a.Id
            WHERE c.Item LIKE :path
        ";
    }

    private function getNoGroupArticleIdsSql(): string
    {
        return "
            SELECT Article.Id 
            FROM Article 
            WHERE Article.PublishedBy IS NOT NULL 
              AND Article.IdGroup IS NULL 
              AND Article.OnlyForMembers = 0
        ";
    }

    private function getArticleIdsForMembersSql(): string
    {
        return "
            SELECT Article.Id 
            FROM Article 
            WHERE Article.PublishedBy IS NOT NULL 
              AND Article.IdGroup IS NULL 
              AND Article.OnlyForMembers = 1
        ";
    }

    /** @param list<int> $groupIds */
    private function getArticleIdsByGroupsSql(array $groupIds): string
    {
        $groups = implode(',', array_fill(0, count($groupIds), '?'));

        return "
            SELECT DISTINCT Article.Id 
            FROM Article 
            WHERE Article.PublishedBy IS NOT NULL
            AND Article.IdGroup IN ($groups)
        ";
    }

    /**
     * @param list<int> $articleIds
     */
    private function getDoGetLatestArticlesSql(array $articleIds, int $latestArticlesCount): string
    {
        $placeholders = [];
        foreach ($articleIds as $index => $id) {
            $placeholders[] = ":id$index";
        }

        return "
            SELECT Id, Title, Timestamp, LastUpdate
            FROM Article
            WHERE Id IN (" . implode(',', $placeholders) . ")
            AND PublishedBy IS NOT NULL
            ORDER BY LastUpdate DESC
            LIMIT " . $latestArticlesCount;
    }
}