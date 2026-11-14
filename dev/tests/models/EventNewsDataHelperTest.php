<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class EventNewsDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Summary',
            'LastUpdate',
            'IdEventType',
        ]);

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'IdGroup',
            'IdMember',
        ]);
    }

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT e.Id, e.Summary, e.LastUpdate', $sql);
        $this->assertStringContainsString('FROM Event e', $sql);
        $this->assertStringContainsString('JOIN EventType et ON e.IdEventType = et.Id', $sql);
        $this->assertStringContainsString('AND mg.IdMember = :personId', $sql);
        $this->assertStringContainsString('WHERE e.LastUpdate >= :searchFrom', $sql);
        $this->assertStringContainsString('ORDER BY e.LastUpdate DESC', $sql);
    }

    private function getNewsSql(): string
    {
        return "
            SELECT e.Id, e.Summary, e.LastUpdate
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN MemberGroup mg
                ON et.IdGroup = mg.IdGroup
               AND mg.IdMember = :personId
            WHERE e.LastUpdate >= :searchFrom
              AND (
                  et.IdGroup IS NULL
                  OR mg.IdMember IS NOT NULL
              )
            ORDER BY e.LastUpdate DESC
        ";
    }
}