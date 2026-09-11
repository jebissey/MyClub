<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class OrderDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Order', [
            'Id',
            'IdArticle',
            'Question',
            'Options',
            'ClosingDate',
            'Visibility',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Title',
            'CreatedBy',
            'PublishedBy',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
            'Inactivated',
        ]);

        $this->assertColumnsExist($pdo, 'OrderReply', [
            'Id',
            'IdOrder',
            'IdPerson',
            'LastUpdate',
        ]);

        $this->assertColumnsExist($pdo, 'PersonGroup', [
            'IdPerson',
            'IdGroup',
        ]);
    }

    public function testArticleHasOrderNotClosedSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getArticleHasOrderNotClosedSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT "Order".*', $sql);
        $this->assertStringContainsString('FROM "Order"', $sql);
        $this->assertStringContainsString('JOIN Article ON "Order".IdArticle = Article.Id', $sql);
        $this->assertStringContainsString('WHERE "Order".IdArticle = :articleId', $sql);
        $this->assertStringContainsString('AND "Order".ClosingDate >= datetime(\'now\')', $sql);
        $this->assertStringContainsString('LIMIT 1', $sql);
    }

    public function testGetWithCreatorSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetWithCreatorSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT o.Id, o.Question, o.Options, o.IdArticle, o.ClosingDate, o.Visibility, a.CreatedBy', $sql);
        $this->assertStringContainsString('FROM "Order" o', $sql);
        $this->assertStringContainsString('INNER JOIN Article a ON o.IdArticle = a.Id', $sql);
        $this->assertStringContainsString('WHERE o.IdArticle = :articleId', $sql);
    }

    public function testGetPendingOrderResponsesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetPendingOrderResponsesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Person p', $sql);
        $this->assertStringContainsString('CROSS JOIN "Order" o', $sql);
        $this->assertStringContainsString('JOIN Article a ON o.IdArticle = a.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN OrderReply r ON r.IdOrder = o.Id AND r.IdPerson = p.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id AND pg.IdGroup = a.IdGroup', $sql);
        $this->assertStringContainsString('a.PublishedBy IS NOT NULL', $sql);
        $this->assertStringContainsString('p.Inactivated = 0', $sql);
        $this->assertStringContainsString("o.ClosingDate > date('now')", $sql);
        $this->assertStringContainsString('r.Id IS NULL', $sql);
    }

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM OrderReply r', $sql);
        $this->assertStringContainsString('JOIN "Order" o ON o.Id = r.IdOrder', $sql);
        $this->assertStringContainsString('JOIN Article a ON a.Id = o.IdArticle', $sql);
        $this->assertStringContainsString('JOIN Person p ON p.Id = a.CreatedBy', $sql);
        $this->assertStringContainsString('JOIN Person v ON v.Id = r.IdPerson', $sql);
        $this->assertStringContainsString('WHERE r.LastUpdate >= :searchFrom', $sql);
        $this->assertStringContainsString('GROUP BY o.Id', $sql);
        $this->assertStringContainsString('ORDER BY LastActivity DESC', $sql);
    }

    private function getArticleHasOrderNotClosedSql(): string
    {
        return "
            SELECT \"Order\".*
            FROM \"Order\"
            JOIN Article ON \"Order\".IdArticle = Article.Id
            WHERE \"Order\".IdArticle = :articleId
            AND \"Order\".ClosingDate >= datetime('now')
            LIMIT 1
        ";
    }

    private function getGetWithCreatorSql(): string
    {
        return "
            SELECT o.Id, o.Question, o.Options, o.IdArticle, o.ClosingDate, o.Visibility, a.CreatedBy
            FROM \"Order\" o
            INNER JOIN Article a ON o.IdArticle = a.Id
            WHERE o.IdArticle = :articleId
        ";
    }

    private function getGetPendingOrderResponsesSql(): string
    {
        return "
        SELECT 
            p.Id AS PersonId, 
            p.Email, 
            a.Id AS ArticleId, 
            a.Title AS ArticleTitle, 
            o.Id AS OrderId, 
            o.Question AS OrderQuestion, 
            o.ClosingDate
        FROM Person p
        CROSS JOIN \"Order\" o
        JOIN Article a ON o.IdArticle = a.Id
        LEFT JOIN OrderReply r ON r.IdOrder = o.Id AND r.IdPerson = p.Id
        LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id AND pg.IdGroup = a.IdGroup
        WHERE 
            a.PublishedBy IS NOT NULL
            AND p.Inactivated = 0
            AND o.ClosingDate > date('now')
            AND (
                a.IdGroup IS NULL
                OR pg.IdGroup IS NOT NULL 
            )
            AND r.Id IS NULL
        ORDER BY o.ClosingDate, p.LastName, p.FirstName";
    }

    private function getGetNewsSql(): string
    {
        return "
            SELECT 
                p.FirstName,
                p.LastName,
                o.Question,
                o.ClosingDate,
                o.Visibility,
                o.IdArticle,
                MAX(r.LastUpdate) AS LastActivity,
                GROUP_CONCAT(
                    v.FirstName || ' ' || v.LastName || ' (' ||
                    strftime('%d/%m/%Y', r.LastUpdate) || ')',
                    ', '
                ) AS Orderers
            FROM OrderReply r
            JOIN \"Order\" o ON o.Id = r.IdOrder
            JOIN Article a ON a.Id = o.IdArticle
            JOIN Person p ON p.Id = a.CreatedBy
            JOIN Person v ON v.Id = r.IdPerson
            WHERE r.LastUpdate >= :searchFrom
            GROUP BY o.Id
            ORDER BY LastActivity DESC
        ";
    }
}