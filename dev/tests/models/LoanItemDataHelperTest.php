<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LoanItemDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'LoanItem', [
            'Id',
            'Name',
            'Description',
            'Type',
            'Quantity',
            'IsActive',
            'UpdatedAt',
        ]);

        $this->assertColumnsExist($pdo, 'LoanRecord', [
            'ItemId',
            'Status',
        ]);

        $this->assertColumnsExist($pdo, 'LoanReservation', [
            'ItemId',
            'Status',
        ]);
    }

    public function testSelectSqlsAreValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        foreach ([
            "SELECT Id, Name, Description, Type, Quantity, IsActive FROM LoanItem ORDER BY IsActive DESC, Name ASC",
            "SELECT Id, Name, Description, Type, Quantity, IsActive FROM LoanItem WHERE IsActive = 1 AND (Type = :type OR Type = 'both') ORDER BY Name ASC",
            "SELECT Id, Name, Description, Type, Quantity, IsActive FROM LoanItem WHERE Id = :id",
            "SELECT Quantity FROM LoanItem WHERE Id = :id",
        ] as $sql) {
            $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($sql), $sql);
        }
    }

    public function testWriteSqlsAreValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        foreach ([
            "UPDATE LoanItem SET Name=:name, Description=:desc, Type=:type, Quantity=:qty, IsActive=:active, UpdatedAt=datetime('now') WHERE Id=:id",
            "INSERT INTO LoanItem (Name, Description, Type, Quantity, IsActive) VALUES (:name, :desc, :type, :qty, :active)",
            "SELECT COUNT(*) FROM LoanRecord WHERE ItemId=:id AND Status='active'",
            "SELECT COUNT(*) FROM LoanReservation WHERE ItemId=:id AND Status='active'",
            "DELETE FROM LoanItem WHERE Id=:id",
        ] as $sql) {
            $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($sql), $sql);
        }
    }
}