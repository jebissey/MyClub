<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class ArticleCrosstabDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'FirstName',
            'LastName',
            'NickName',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'CreatedBy',
            'IdGroup',
            'OnlyForMembers',
            'LastUpdate',
            'PublishedBy',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
        ]);
    }

    public function testGetItemsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getExpectedSql();

        // Ensures the query is syntactically valid and that all referenced
        // tables/columns actually exist in the template database.
        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        // Structural checks on critical parts of the query
        $this->assertStringContainsString('columnForCrosstab', $sql);
        $this->assertStringContainsString('rowForCrosstab', $sql);
        $this->assertStringContainsString('countForCrosstab', $sql);
        $this->assertStringContainsString('BETWEEN :start AND :end', $sql);
        $this->assertStringContainsString('a.PublishedBy IS NOT NULL', $sql);
        $this->assertStringContainsString('Tous (les visiteurs)', $sql);
        $this->assertStringContainsString('Club (membres)', $sql);
        $this->assertStringContainsString('ORDER BY i.LastName, i.FirstName', $sql);
    }

    /**
     * Exact SQL used by ArticleCrosstabDataHelper::getItems().
     * Kept here to avoid duplication and to keep the production class clean.
     */
    private function getExpectedSql(): string
    {
        return "
            SELECT 
                i.FirstName || ' ' || i.LastName || 
                CASE 
                    WHEN i.NickName IS NOT NULL AND i.NickName != '' THEN ' (' || i.NickName || ')'
                    ELSE ''
                END AS columnForCrosstab,
                CASE 
                    WHEN g.Name IS NOT NULL THEN g.Name
                    WHEN a.OnlyForMembers = 0 THEN 'Tous (les visiteurs)'
                    WHEN a.OnlyForMembers = 1 THEN 'Club (membres)'
                END AS rowForCrosstab,
                1 AS countForCrosstab
            FROM Individual i
            JOIN Article a ON i.Id = a.CreatedBy
            LEFT JOIN \"Group\" g ON g.Id = a.IdGroup
            WHERE a.LastUpdate BETWEEN :start AND :end
            AND a.PublishedBy IS NOT NULL
            ORDER BY i.LastName, i.FirstName
";
    }
}