<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class MessageImageDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Message columns read/filtered on in getImageInfo(), getUses(), getUsedPaths()
        $this->assertColumnsExist($pdo, 'Message', [
            'Id',
            'ImagePath',
            'EventId',
            'ArticleId',
            'GroupId',
        ]);

        // Event columns read in getUses()
        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Summary',
            'StartTime',
        ]);

        // Article columns read in getUses()
        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Title',
        ]);
    }

    public function testGetUsesEventsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            SELECT DISTINCT e.Id, e.Summary AS Title, e.StartTime
            FROM Message m
            JOIN Event e ON e.Id = m.EventId
            WHERE m.EventId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY e.StartTime DESC
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetUsesArticlesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            SELECT DISTINCT a.Id, a.Title
            FROM Message m
            JOIN Article a ON a.Id = m.ArticleId
            WHERE m.ArticleId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY a.Title ASC
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetUsesGroupsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // The Group table (reserved word) and its Name column are validated by this query.
        $sql = "
            SELECT DISTINCT g.Id, g.Name AS Title
            FROM Message m
            JOIN `Group` g ON g.Id = m.GroupId
            WHERE m.GroupId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY g.Name ASC
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetUsedPathsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "SELECT ImagePath FROM Message WHERE ImagePath IS NOT NULL";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }
}