<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class MembershipDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Membership', [
            'Id',
            'PersonId',
            'Season',
            'Status',
            'Amount',
            'HelloAssoCheckoutIntentId',
            'HelloAssoOrderId',
            'PaidAt',
            'UpdatedAt',
        ]);
    }

    public function testGetAllForPersonSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetAllForPersonSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT * FROM Membership', $sql);
        $this->assertStringContainsString('WHERE PersonId = ?', $sql);
        $this->assertStringContainsString('ORDER BY Season DESC', $sql);
    }

    public function testMarkPaidByIntentIdSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getMarkPaidByIntentIdSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Id FROM Membership', $sql);
        $this->assertStringContainsString('WHERE HelloAssoCheckoutIntentId = ?', $sql);
        $this->assertStringContainsString("AND Status = 'pending'", $sql);
        $this->assertStringContainsString('LIMIT 1', $sql);
    }

    private function getGetAllForPersonSql(): string
    {
        return "SELECT * FROM Membership WHERE PersonId = ? ORDER BY Season DESC";
    }

    private function getMarkPaidByIntentIdSql(): string
    {
        return "SELECT Id FROM Membership WHERE HelloAssoCheckoutIntentId = ? AND Status = 'pending' LIMIT 1";
    }
}