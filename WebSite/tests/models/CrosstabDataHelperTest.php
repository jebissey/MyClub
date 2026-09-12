<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class CrosstabDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'FirstName',
            'LastName',
            'NickName',
        ]);

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'CreatedBy',
            'IdEventType',
            'LastUpdate',
        ]);

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'Participant', [
            'Id',
            'IdEvent',
        ]);

        $logPdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);

        $this->assertColumnsExist($logPdo, 'Log', [
            'Uri',
            'Who',
            'CreatedAt',
        ]);
    }

    public function testGetEventsQuerySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getEventsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Person p', $sql);
        $this->assertStringContainsString('JOIN Event e ON p.Id = e.CreatedBy', $sql);
        $this->assertStringContainsString('JOIN EventType et ON e.IdEventType = et.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN Participant part ON part.IdEvent = e.Id', $sql);
        $this->assertStringContainsString('AS columnForCrosstab', $sql);
        $this->assertStringContainsString('et.Name AS rowForCrosstab', $sql);
        $this->assertStringContainsString('COUNT(DISTINCT e.Id) AS countForCrosstab', $sql);
        $this->assertStringContainsString('COUNT(part.Id) AS count2ForCrosstab', $sql);
        $this->assertStringContainsString('WHERE e.LastUpdate BETWEEN :start AND :end', $sql);
        $this->assertStringContainsString('GROUP BY p.Id, et.Id', $sql);
        $this->assertStringContainsString('ORDER BY p.LastName, p.FirstName', $sql);

        $stmt->execute([':start' => '2026-01-01', ':end' => '2026-12-31']);
        $this->assertIsArray($stmt->fetchAll());
    }

    public function testGetPersonsBaseQuerySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getPersonsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('LOWER(Who) AS Who', $sql);
        $this->assertStringContainsString('COUNT(*) as count', $sql);
        $this->assertStringContainsString("WHERE date(CreatedAt) = date('now')", $sql);
        $this->assertStringContainsString('GROUP BY Uri, LOWER(Who)', $sql);

        $stmt->execute();
        $this->assertIsArray($stmt->fetchAll());
    }

    public function testGetPersonsQueryWithUriFilterSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getPersonsSql(uriFilter: true);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('AND Uri LIKE :uriFilter', $sql);

        $stmt->execute([':uriFilter' => '%/events%']);
        $this->assertIsArray($stmt->fetchAll());
    }

    public function testGetPersonsQueryWithEmailFilterSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getPersonsSql(emailFilter: true);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('AND Who LIKE :emailFilter', $sql);

        $stmt->execute([':emailFilter' => '%example.com%']);
        $this->assertIsArray($stmt->fetchAll());
    }

    public function testGetPersonsQueryWithBothFiltersSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getPersonsSql(uriFilter: true, emailFilter: true);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('AND Uri LIKE :uriFilter', $sql);
        $this->assertStringContainsString('AND Who LIKE :emailFilter', $sql);

        $stmt->execute([
            ':uriFilter'   => '%/events%',
            ':emailFilter' => '%example.com%',
        ]);
        $this->assertIsArray($stmt->fetchAll());
    }

    public function testGetPersonsQueryWithDefaultDateConditionSqlIsValidAgainstTemplateSchema(): void
    {
        // Period::default() -> dateConditions() renvoie '1=1' (aucune restriction de date).
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getPersonsSql(dateCondition: '1=1');

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('WHERE 1=1', $sql);

        $stmt->execute();
        $this->assertIsArray($stmt->fetchAll());
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from CrosstabDataHelper)
    // -------------------------------------------------------------------------

    private function getEventsSql(): string
    {
        return "
            SELECT 
                p.FirstName || ' ' || p.LastName || 
                CASE 
                    WHEN p.NickName IS NOT NULL AND p.NickName != '' THEN ' (' || p.NickName || ')'
                    ELSE ''
                END AS columnForCrosstab,
                et.Name AS rowForCrosstab,
                COUNT(DISTINCT e.Id) AS countForCrosstab,
                COUNT(part.Id) AS count2ForCrosstab
            FROM Person p
            JOIN Event e ON p.Id = e.CreatedBy
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN Participant part ON part.IdEvent = e.Id
            WHERE e.LastUpdate BETWEEN :start AND :end
            GROUP BY p.Id, et.Id
            ORDER BY p.LastName, p.FirstName
        ";
    }

    private function getPersonsSql(
        bool $uriFilter = false,
        bool $emailFilter = false,
        string $dateCondition = "date(CreatedAt) = date('now')",
    ): string {
        $sql = '
            SELECT 
                Uri, 
                LOWER(Who) AS Who, 
                COUNT(*) as count   
            FROM Log
            WHERE ' . $dateCondition . '
        ';

        if ($uriFilter) {
            $sql .= ' AND Uri LIKE :uriFilter';
        }
        if ($emailFilter) {
            $sql .= ' AND Who LIKE :emailFilter';
        }

        $sql .= ' GROUP BY Uri, LOWER(Who)';

        return $sql;
    }
}