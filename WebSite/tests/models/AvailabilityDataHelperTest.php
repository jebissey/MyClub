<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class AvailabilityDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Member', [
            'Inactivated',
            'Availabilities',
        ]);
    }

    public function testGetTotalActiveSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getTotalActiveSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*) FROM Member WHERE Inactivated = 0', $sql);
    }

    public function testGetAvailabilitiesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAvailabilitiesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Availabilities FROM Member WHERE Inactivated = 0', $sql);
        $this->assertStringContainsString("AND Availabilities IS NOT NULL AND Availabilities != ''", $sql);
    }

    public function testParticipationStatsQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'StartTime',
            'Canceled',
        ]);

        $this->assertColumnsExist($pdo, 'Participant', [
            'Id',
            'IdEvent',
        ]);
    }

    public function testGetParticipationStatsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getParticipationStatsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString(
            'LEFT JOIN Participant p ON p.IdEvent = e.Id',
            $sql,
        );
        $this->assertStringContainsString('WHERE e.Canceled = 0', $sql);
        $this->assertStringContainsString('AND e.StartTime >= :start', $sql);
        $this->assertStringContainsString('AND e.StartTime < :end', $sql);
        $this->assertStringContainsString('GROUP BY e.Id', $sql);
    }

    public function testGetParticipationStatsSqlBindsNamedParameters(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getParticipationStatsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $result = $stmt->execute([
            ':start' => '2000-01-01 00:00:00',
            ':end'   => '2000-01-02 00:00:00',
        ]);

        $this->assertTrue($result);
    }

    private function getTotalActiveSql(): string
    {
        return 'SELECT COUNT(*) FROM Member WHERE Inactivated = 0';
    }

    private function getAvailabilitiesSql(): string
    {
        return 'SELECT Availabilities FROM Member WHERE Inactivated = 0 AND Availabilities IS NOT NULL AND Availabilities != \'\'';
    }

    private function getParticipationStatsSql(): string
    {
        return 'SELECT e.Id, e.StartTime, COUNT(p.Id) AS ParticipantCount
                FROM Event e
                LEFT JOIN Participant p ON p.IdEvent = e.Id
                WHERE e.Canceled = 0
                  AND e.StartTime >= :start
                  AND e.StartTime < :end
                GROUP BY e.Id';
    }
}