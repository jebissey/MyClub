<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LoanRecordDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'LoanRecord', [
            'Id',
            'ItemId',
            'BorrowerId',
            'LenderId',
            'LoanDate',
            'DueDate',
            'ReturnDate',
            'ReturnedToId',
            'QuantityLent',
            'Notes',
            'Status',
        ]);

        $this->assertColumnsExist($pdo, 'LoanItem', [
            'Id',
            'Name',
            'Quantity',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'FirstName',
            'LastName',
        ]);
    }

    public function testSelectBaseSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $base = $this->selectBase();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($base));
        $this->assertInstanceOf(
            PDOStatement::class,
            $pdo->prepare("$base WHERE lr.Status = :status ORDER BY lr.LoanDate DESC"),
        );
        $this->assertInstanceOf(
            PDOStatement::class,
            $pdo->prepare(
                "$base WHERE lr.Status IN ('active','returned','overdue')
                   AND lr.LoanDate <= :end AND lr.DueDate >= :start
                 ORDER BY lr.LoanDate ASC"
            ),
        );
        $this->assertInstanceOf(
            PDOStatement::class,
            $pdo->prepare("$base WHERE lr.Id = :id"),
        );

        $this->assertStringContainsString('FROM   LoanRecord lr', $base);
        $this->assertStringContainsString('JOIN   LoanItem   li ON li.Id = lr.ItemId', $base);
        $this->assertStringContainsString('LEFT JOIN Individual rt ON rt.Id = lr.ReturnedToId', $base);
    }

    public function testWriteSqlsAreValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        foreach ([
            "SELECT COALESCE(SUM(QuantityLent), 0) FROM LoanRecord WHERE ItemId = :itemId AND Status IN ('active','overdue') AND LoanDate <= :due AND DueDate >= :loan AND Id <> :excludeId",
            "UPDATE LoanRecord SET ItemId=:itemId, BorrowerId=:borrowerId, LenderId=:lenderId, LoanDate=:loanDate, DueDate=:dueDate, QuantityLent=:qty, Notes=:notes WHERE Id=:id AND Status='active'",
            "INSERT INTO LoanRecord (ItemId, BorrowerId, LenderId, LoanDate, DueDate, QuantityLent, Notes) VALUES (:itemId, :borrowerId, :lenderId, :loanDate, :dueDate, :qty, :notes)",
            "UPDATE LoanRecord SET ReturnDate=:returnDate, ReturnedToId=:returnedToId, Status='returned' WHERE Id=:id AND Status IN ('active','overdue')",
            "UPDATE LoanRecord SET Status='cancelled' WHERE Id=:id AND Status='active'",
            "UPDATE LoanRecord SET Status='overdue' WHERE Status='active' AND DueDate < date('now')",
        ] as $sql) {
            $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($sql), $sql);
        }
    }

    private function selectBase(): string
    {
        return "SELECT lr.Id, lr.ItemId, lr.BorrowerId, lr.LenderId,
                       lr.LoanDate, lr.DueDate, lr.ReturnDate, lr.ReturnedToId,
                       lr.QuantityLent, lr.Notes, lr.Status,
                       li.Name         AS ItemName,
                       li.Quantity     AS ItemTotalQty,
                       b.FirstName || ' ' || b.LastName   AS BorrowerName,
                       l.FirstName || ' ' || l.LastName   AS LenderName,
                       rt.FirstName || ' ' || rt.LastName AS ReturnedToName
                FROM   LoanRecord lr
                JOIN   LoanItem   li ON li.Id = lr.ItemId
                JOIN   Individual b  ON b.Id  = lr.BorrowerId
                JOIN   Individual l  ON l.Id  = lr.LenderId
                LEFT JOIN Individual rt ON rt.Id = lr.ReturnedToId";
    }
}