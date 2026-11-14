<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LoanReservationDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'LoanReservation', [
            'Id',
            'ItemId',
            'UserId',
            'ReservationDate',
            'StartTime',
            'EndTime',
            'QuantityReserved',
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
            $pdo->prepare("$base WHERE res.UserId = :uid ORDER BY res.ReservationDate DESC, res.StartTime ASC"),
        );
        $this->assertInstanceOf(
            PDOStatement::class,
            $pdo->prepare(
                "$base
                 WHERE res.Status = 'active'
                   AND res.ReservationDate BETWEEN :start AND :end
                 ORDER BY res.ReservationDate ASC, res.StartTime ASC"
            ),
        );
        $this->assertInstanceOf(
            PDOStatement::class,
            $pdo->prepare("$base WHERE res.Id = :id"),
        );

        $this->assertStringContainsString('FROM   LoanReservation res', $base);
        $this->assertStringContainsString('JOIN   LoanItem li ON li.Id = res.ItemId', $base);
        $this->assertStringContainsString('JOIN   Individual p  ON p.Id  = res.UserId', $base);
    }

    public function testWriteSqlsAreValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        foreach ([
            "SELECT COALESCE(SUM(QuantityReserved), 0) FROM LoanReservation
             WHERE ItemId = :itemId AND Status = 'active' AND ReservationDate = :date
               AND StartTime < :end AND EndTime > :start AND Id <> :excludeId",
            "UPDATE LoanReservation
             SET ItemId=:itemId, UserId=:userId, ReservationDate=:date, StartTime=:start, EndTime=:end,
                 QuantityReserved=:qty, Notes=:notes
             WHERE Id=:id AND Status='active'",
            "INSERT INTO LoanReservation (ItemId, UserId, ReservationDate, StartTime, EndTime, QuantityReserved, Notes)
             VALUES (:itemId, :userId, :date, :start, :end, :qty, :notes)",
            "UPDATE LoanReservation SET Status='cancelled' WHERE Id=:id AND Status='active' AND UserId=:uid",
        ] as $sql) {
            $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($sql), $sql);
        }
    }

    private function selectBase(): string
    {
        return "SELECT res.Id, res.ItemId, res.UserId,
                       res.ReservationDate, res.StartTime, res.EndTime,
                       res.QuantityReserved, res.Notes, res.Status,
                       li.Name     AS ItemName,
                       li.Quantity AS ItemTotalQty,
                       p.FirstName || ' ' || p.LastName AS UserName
                FROM   LoanReservation res
                JOIN   LoanItem li ON li.Id = res.ItemId
                JOIN   Individual p  ON p.Id  = res.UserId";
    }
}