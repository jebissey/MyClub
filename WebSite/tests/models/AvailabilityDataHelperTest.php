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

    private function getTotalActiveSql(): string
    {
        return 'SELECT COUNT(*) FROM Member WHERE Inactivated = 0';
    }

    private function getAvailabilitiesSql(): string
    {
        return 'SELECT Availabilities FROM Member WHERE Inactivated = 0 AND Availabilities IS NOT NULL AND Availabilities != \'\'';
    }
}