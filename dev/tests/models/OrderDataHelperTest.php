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

        $this->assertColumnsExist($pdo, 'OrderReply', [
            'Id',
            'IdOrder',
            'IdPerson',
            'LastUpdate',
        ]);

        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'IdMember',
            'IdGroup',
        ]);
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

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM OrderReply r', $sql);
        $this->assertStringContainsString('JOIN "Order" o ON o.Id = r.IdOrder', $sql);
        $this->assertStringContainsString('JOIN Article a ON a.Id = o.IdArticle', $sql);
        $this->assertStringContainsString('JOIN Individual p ON p.Id = a.CreatedBy', $sql);
        $this->assertStringContainsString('JOIN Individual v ON v.Id = r.IdPerson', $sql);
        $this->assertStringContainsString('WHERE r.LastUpdate >= :searchFrom', $sql);
        $this->assertStringContainsString('GROUP BY o.Id', $sql);
        $this->assertStringContainsString('ORDER BY LastActivity DESC', $sql);
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
            JOIN Individual p ON p.Id = a.CreatedBy
            JOIN Individual v ON v.Id = r.IdPerson
            WHERE r.LastUpdate >= :searchFrom
            GROUP BY o.Id
            ORDER BY LastActivity DESC
        ";
    }
}