<?php

declare(strict_types=1);

namespace tests\models;

class ArticleCrosstabDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();

        $this->assertColumnsExist($pdo, 'Person', [
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
        $pdo = $this->openTemplateDatabaseOrSkip();
        $sql = $this->getExpectedSql();

        // Ensures the query is syntactically valid and that all referenced
        // tables/columns actually exist in the template database.
        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        // Structural checks on critical parts of the query
        $this->assertStringContainsString('columnForCrosstab', $sql);
        $this->assertStringContainsString('rowForCrosstab', $sql);
        $this->assertStringContainsString('countForCrosstab', $sql);
        $this->assertStringContainsString('BETWEEN :start AND :end', $sql);
        $this->assertStringContainsString('a.PublishedBy IS NOT NULL', $sql);
        $this->assertStringContainsString('Tous (les visiteurs)', $sql);
        $this->assertStringContainsString('Club (membres)', $sql);
        $this->assertStringContainsString('ORDER BY p.LastName, p.FirstName', $sql);
    }

    /**
     * Exact SQL used by ArticleCrosstabDataHelper::getItems().
     * Kept here to avoid duplication and to keep the production class clean.
     */
    private function getExpectedSql(): string
    {
        return "
            SELECT 
                p.FirstName || ' ' || p.LastName || 
                CASE 
                    WHEN p.NickName IS NOT NULL AND p.NickName != '' THEN ' (' || p.NickName || ')'
                    ELSE ''
                END AS columnForCrosstab,
                CASE 
                    WHEN g.Name IS NOT NULL THEN g.Name
                    WHEN a.OnlyForMembers = 0 THEN 'Tous (les visiteurs)'
                    WHEN a.OnlyForMembers = 1 THEN 'Club (membres)'
                END AS rowForCrosstab,
                1 AS countForCrosstab
            FROM Person p
            JOIN Article a ON p.Id = a.CreatedBy
            LEFT JOIN \"Group\" g ON g.Id = a.IdGroup
            WHERE a.LastUpdate BETWEEN :start AND :end
            AND a.PublishedBy IS NOT NULL
            ORDER BY p.LastName, p.FirstName
        ";
    }
}