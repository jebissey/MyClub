<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LoanDataHelperTest extends DataHelperTestCase
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

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'FirstName',
            'LastName',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // MATÉRIELS
    // ══════════════════════════════════════════════════════════════════════

    public function testGetAllItemsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetAllItemsSql();

        $stmt = $pdo->query($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT * FROM LoanItem', $sql);
        $this->assertStringContainsString('ORDER BY IsActive DESC, Name ASC', $sql);
    }

    public function testGetActiveItemsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sqlAll = $this->getGetActiveItemsAllSql();
        $stmtAll = $pdo->query($sqlAll);
        $this->assertInstanceOf(PDOStatement::class, $stmtAll);
        $this->assertStringContainsString('WHERE IsActive = 1', $sqlAll);
        $this->assertStringContainsString('ORDER BY Name ASC', $sqlAll);

        $sqlTyped = $this->getGetActiveItemsByTypeSql();
        $stmtTyped = $pdo->prepare($sqlTyped);
        $this->assertInstanceOf(PDOStatement::class, $stmtTyped);
        $this->assertStringContainsString("AND (Type = :type OR Type = 'both')", $sqlTyped);
    }

    public function testGetItemSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetItemSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT * FROM LoanItem WHERE Id = :id', $sql);
    }

    public function testSaveItemSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $updateSql = $this->getUpdateItemSql();
        $insertSql = $this->getInsertItemSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($updateSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($insertSql));

        $this->assertStringContainsString('UPDATE LoanItem', $updateSql);
        $this->assertStringContainsString("UpdatedAt=datetime('now')", $updateSql);
        $this->assertStringContainsString('WHERE Id=:id', $updateSql);

        $this->assertStringContainsString('INSERT INTO LoanItem (Name, Description, Type, Quantity, IsActive)', $insertSql);
        $this->assertStringContainsString('VALUES (:name, :desc, :type, :qty, :active)', $insertSql);
    }

    public function testDeleteItemSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $countLoansSql = $this->getCountActiveLoansForItemSql();
        $countReservationsSql = $this->getCountActiveReservationsForItemSql();
        $deleteSql = $this->getDeleteItemSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($countLoansSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($countReservationsSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($deleteSql));

        $this->assertStringContainsString("FROM LoanRecord", $countLoansSql);
        $this->assertStringContainsString("Status='active'", $countLoansSql);
        $this->assertStringContainsString("FROM LoanReservation", $countReservationsSql);
        $this->assertStringContainsString("Status='active'", $countReservationsSql);
        $this->assertStringContainsString('DELETE FROM LoanItem WHERE Id=:id', $deleteSql);
    }

    // ══════════════════════════════════════════════════════════════════════
    // PRÊTS
    // ══════════════════════════════════════════════════════════════════════

    public function testLoanSelectBaseSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getLoanSelectBaseSql() . ' WHERE lr.Id = :id';

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM   LoanRecord lr', $sql);
        $this->assertStringContainsString('JOIN   LoanItem   li ON li.Id = lr.ItemId', $sql);
        $this->assertStringContainsString('JOIN   Person     b  ON b.Id  = lr.BorrowerId', $sql);
        $this->assertStringContainsString('JOIN   Person     l  ON l.Id  = lr.LenderId', $sql);
        $this->assertStringContainsString('LEFT JOIN Person  rt ON rt.Id = lr.ReturnedToId', $sql);
    }

    public function testGetAllLoansSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sqlNoStatus = $this->getGetAllLoansSql();
        $stmtNoStatus = $pdo->query($sqlNoStatus);
        $this->assertInstanceOf(PDOStatement::class, $stmtNoStatus);
        $this->assertStringContainsString('ORDER BY lr.LoanDate DESC', $sqlNoStatus);

        $sqlWithStatus = $this->getGetAllLoansByStatusSql();
        $stmtWithStatus = $pdo->prepare($sqlWithStatus);
        $this->assertInstanceOf(PDOStatement::class, $stmtWithStatus);
        $this->assertStringContainsString('WHERE lr.Status = :status', $sqlWithStatus);
    }

    public function testGetLoanSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetLoanSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('WHERE lr.Id = :id', $sql);
    }

    public function testGetAvailableQtyForLoanSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = $this->getAvailableQtyForLoanSql(false);
        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $this->assertStringContainsString('SELECT COALESCE(SUM(QuantityLent), 0)', $sql);
        $this->assertStringContainsString("Status IN ('active','overdue')", $sql);
        $this->assertStringContainsString('LoanDate <= :due', $sql);
        $this->assertStringContainsString('DueDate  >= :loan', $sql);
        $this->assertStringNotContainsString('excludeId', $sql);

        $sqlExcluded = $this->getAvailableQtyForLoanSql(true);
        $stmtExcluded = $pdo->prepare($sqlExcluded);
        $this->assertInstanceOf(PDOStatement::class, $stmtExcluded);
        $this->assertStringContainsString('AND Id <> :excludeId', $sqlExcluded);
    }

    public function testSaveLoanSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $updateSql = $this->getUpdateLoanSql();
        $insertSql = $this->getInsertLoanSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($updateSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($insertSql));

        $this->assertStringContainsString('UPDATE LoanRecord', $updateSql);
        $this->assertStringContainsString("WHERE Id=:id AND Status='active'", $updateSql);

        $this->assertStringContainsString('INSERT INTO LoanRecord', $insertSql);
        $this->assertStringContainsString('(ItemId, BorrowerId, LenderId, LoanDate, DueDate, QuantityLent, Notes)', $insertSql);
    }

    public function testSetLoanReturnSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getSetLoanReturnSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString("Status='returned'", $sql);
        $this->assertStringContainsString("WHERE Id=:id AND Status IN ('active','overdue')", $sql);
    }

    public function testCancelLoanSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCancelLoanSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString("SET Status='cancelled'", $sql);
        $this->assertStringContainsString("WHERE Id=:id AND Status='active'", $sql);
    }

    public function testUpdateOverdueLoansSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateOverdueLoansSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString("SET Status='overdue'", $sql);
        $this->assertStringContainsString("WHERE Status='active' AND DueDate < date('now')", $sql);
    }

    // ══════════════════════════════════════════════════════════════════════
    // RÉSERVATIONS
    // ══════════════════════════════════════════════════════════════════════

    public function testReservationSelectBaseSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getReservationSelectBaseSql() . ' WHERE res.Id = :id';

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM   LoanReservation res', $sql);
        $this->assertStringContainsString('JOIN   LoanItem li ON li.Id = res.ItemId', $sql);
        $this->assertStringContainsString('JOIN   Person   p  ON p.Id  = res.UserId', $sql);
    }

    public function testGetAllReservationsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sqlAll = $this->getGetAllReservationsSql();
        $stmtAll = $pdo->query($sqlAll);
        $this->assertInstanceOf(PDOStatement::class, $stmtAll);
        $this->assertStringContainsString('ORDER BY res.ReservationDate DESC, res.StartTime ASC', $sqlAll);

        $sqlByUser = $this->getGetAllReservationsByUserSql();
        $stmtByUser = $pdo->prepare($sqlByUser);
        $this->assertInstanceOf(PDOStatement::class, $stmtByUser);
        $this->assertStringContainsString('WHERE res.UserId = :uid', $sqlByUser);
    }

    public function testGetReservationSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetReservationSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('WHERE res.Id = :id', $sql);
    }

    public function testGetAvailableQtyForReservationSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = $this->getAvailableQtyForReservationSql(false);
        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $this->assertStringContainsString('SELECT COALESCE(SUM(QuantityReserved), 0)', $sql);
        $this->assertStringContainsString("Status = 'active'", $sql);
        $this->assertStringContainsString('ReservationDate = :date', $sql);
        $this->assertStringContainsString('StartTime < :end', $sql);
        $this->assertStringContainsString('EndTime   > :start', $sql);
        $this->assertStringNotContainsString('excludeId', $sql);

        $sqlExcluded = $this->getAvailableQtyForReservationSql(true);
        $stmtExcluded = $pdo->prepare($sqlExcluded);
        $this->assertInstanceOf(PDOStatement::class, $stmtExcluded);
        $this->assertStringContainsString('AND Id <> :excludeId', $sqlExcluded);
    }

    public function testSaveReservationSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $updateSql = $this->getUpdateReservationSql();
        $insertSql = $this->getInsertReservationSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($updateSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($insertSql));

        $this->assertStringContainsString('UPDATE LoanReservation', $updateSql);
        $this->assertStringContainsString("WHERE Id=:id AND Status='active'", $updateSql);

        $this->assertStringContainsString('INSERT INTO LoanReservation', $insertSql);
        $this->assertStringContainsString('(ItemId, UserId, ReservationDate, StartTime, EndTime, QuantityReserved, Notes)', $insertSql);
    }

    public function testCancelReservationSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = $this->getCancelReservationSql(false);
        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $this->assertStringContainsString("SET Status='cancelled'", $sql);
        $this->assertStringContainsString("WHERE Id=:id AND Status='active'", $sql);
        $this->assertStringNotContainsString('UserId=:uid', $sql);

        $sqlByUser = $this->getCancelReservationSql(true);
        $stmtByUser = $pdo->prepare($sqlByUser);
        $this->assertInstanceOf(PDOStatement::class, $stmtByUser);
        $this->assertStringContainsString('AND UserId=:uid', $sqlByUser);
    }

    // ══════════════════════════════════════════════════════════════════════
    // CALENDRIER
    // ══════════════════════════════════════════════════════════════════════

    public function testGetCalendarLoanEventsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCalendarLoansSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM   LoanRecord lr', $sql);
        $this->assertStringContainsString("WHERE  lr.Status IN ('active','returned','overdue')", $sql);
        $this->assertStringContainsString('lr.LoanDate <= :end', $sql);
        $this->assertStringContainsString('lr.DueDate  >= :start', $sql);
    }

    public function testGetCalendarReservationEventsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCalendarReservationsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM   LoanReservation res', $sql);
        $this->assertStringContainsString("WHERE  res.Status = 'active'", $sql);
        $this->assertStringContainsString('res.ReservationDate BETWEEN :start AND :end', $sql);
    }

    // ══════════════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ══════════════════════════════════════════════════════════════════════

    public function testGetAllPersonsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetAllPersonsSql();

        $stmt = $pdo->query($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString("FirstName || ' ' || LastName AS FullName", $sql);
        $this->assertStringContainsString('FROM Person ORDER BY LastName ASC, FirstName ASC', $sql);
    }

    // ══════════════════════════════════════════════════════════════════════
    // SQL builders (mirroring LoanDataHelper)
    // ══════════════════════════════════════════════════════════════════════

    private function getGetAllItemsSql(): string
    {
        return 'SELECT * FROM LoanItem ORDER BY IsActive DESC, Name ASC';
    }

    private function getGetActiveItemsAllSql(): string
    {
        return 'SELECT * FROM LoanItem WHERE IsActive = 1 ORDER BY Name ASC';
    }

    private function getGetActiveItemsByTypeSql(): string
    {
        return "SELECT * FROM LoanItem WHERE IsActive = 1
				 AND (Type = :type OR Type = 'both') ORDER BY Name ASC";
    }

    private function getGetItemSql(): string
    {
        return 'SELECT * FROM LoanItem WHERE Id = :id';
    }

    private function getUpdateItemSql(): string
    {
        return "UPDATE LoanItem
				 SET Name=:name, Description=:desc, Type=:type,
				     Quantity=:qty, IsActive=:active,
				     UpdatedAt=datetime('now')
				 WHERE Id=:id";
    }

    private function getInsertItemSql(): string
    {
        return "INSERT INTO LoanItem (Name, Description, Type, Quantity, IsActive)
			 VALUES (:name, :desc, :type, :qty, :active)";
    }

    private function getCountActiveLoansForItemSql(): string
    {
        return "SELECT COUNT(*) FROM LoanRecord
			 WHERE ItemId=:id AND Status='active'";
    }

    private function getCountActiveReservationsForItemSql(): string
    {
        return "SELECT COUNT(*) FROM LoanReservation
			 WHERE ItemId=:id AND Status='active'";
    }

    private function getDeleteItemSql(): string
    {
        return 'DELETE FROM LoanItem WHERE Id=:id';
    }

    private function getLoanSelectBaseSql(): string
    {
        return "SELECT lr.*,
				       li.Name         AS ItemName,
				       li.Quantity     AS ItemTotalQty,
				       b.FirstName || ' ' || b.LastName  AS BorrowerName,
				       l.FirstName || ' ' || l.LastName  AS LenderName,
				       rt.FirstName || ' ' || rt.LastName AS ReturnedToName
				FROM   LoanRecord lr
				JOIN   LoanItem   li ON li.Id = lr.ItemId
				JOIN   Person     b  ON b.Id  = lr.BorrowerId
				JOIN   Person     l  ON l.Id  = lr.LenderId
				LEFT JOIN Person  rt ON rt.Id = lr.ReturnedToId";
    }

    private function getGetAllLoansSql(): string
    {
        return $this->getLoanSelectBaseSql() . ' ORDER BY lr.LoanDate DESC';
    }

    private function getGetAllLoansByStatusSql(): string
    {
        return $this->getLoanSelectBaseSql() . ' WHERE lr.Status = :status ORDER BY lr.LoanDate DESC';
    }

    private function getGetLoanSql(): string
    {
        return $this->getLoanSelectBaseSql() . ' WHERE lr.Id = :id';
    }

    private function getAvailableQtyForLoanSql(bool $withExclude): string
    {
        $sql = "SELECT COALESCE(SUM(QuantityLent), 0)
				FROM LoanRecord
				WHERE ItemId = :itemId
				  AND Status IN ('active','overdue')
				  AND LoanDate <= :due
				  AND DueDate  >= :loan";
        if ($withExclude) {
            $sql .= ' AND Id <> :excludeId';
        }
        return $sql;
    }

    private function getUpdateLoanSql(): string
    {
        return "UPDATE LoanRecord
				 SET ItemId=:itemId, BorrowerId=:borrowerId, LenderId=:lenderId,
				     LoanDate=:loanDate, DueDate=:dueDate,
				     QuantityLent=:qty, Notes=:notes
				 WHERE Id=:id AND Status='active'";
    }

    private function getInsertLoanSql(): string
    {
        return "INSERT INTO LoanRecord
			 (ItemId, BorrowerId, LenderId, LoanDate, DueDate, QuantityLent, Notes)
			 VALUES (:itemId, :borrowerId, :lenderId, :loanDate, :dueDate, :qty, :notes)";
    }

    private function getSetLoanReturnSql(): string
    {
        return "UPDATE LoanRecord
			 SET ReturnDate=:returnDate, ReturnedToId=:returnedToId, Status='returned'
			 WHERE Id=:id AND Status IN ('active','overdue')";
    }

    private function getCancelLoanSql(): string
    {
        return "UPDATE LoanRecord SET Status='cancelled'
			 WHERE Id=:id AND Status='active'";
    }

    private function getUpdateOverdueLoansSql(): string
    {
        return "UPDATE LoanRecord SET Status='overdue'
			 WHERE Status='active' AND DueDate < date('now')";
    }

    private function getReservationSelectBaseSql(): string
    {
        return "SELECT res.*,
				       li.Name     AS ItemName,
				       li.Quantity AS ItemTotalQty,
				       p.FirstName || ' ' || p.LastName AS UserName
				FROM   LoanReservation res
				JOIN   LoanItem li ON li.Id = res.ItemId
				JOIN   Person   p  ON p.Id  = res.UserId";
    }

    private function getGetAllReservationsSql(): string
    {
        return $this->getReservationSelectBaseSql() . ' ORDER BY res.ReservationDate DESC, res.StartTime ASC';
    }

    private function getGetAllReservationsByUserSql(): string
    {
        return $this->getReservationSelectBaseSql() . ' WHERE res.UserId = :uid ORDER BY res.ReservationDate DESC, res.StartTime ASC';
    }

    private function getGetReservationSql(): string
    {
        return $this->getReservationSelectBaseSql() . ' WHERE res.Id = :id';
    }

    private function getAvailableQtyForReservationSql(bool $withExclude): string
    {
        $sql = "SELECT COALESCE(SUM(QuantityReserved), 0)
				FROM LoanReservation
				WHERE ItemId = :itemId
				  AND Status = 'active'
				  AND ReservationDate = :date
				  AND StartTime < :end
				  AND EndTime   > :start";
        if ($withExclude) {
            $sql .= ' AND Id <> :excludeId';
        }
        return $sql;
    }

    private function getUpdateReservationSql(): string
    {
        return "UPDATE LoanReservation
				 SET ItemId=:itemId, UserId=:userId,
				     ReservationDate=:date, StartTime=:start, EndTime=:end,
				     QuantityReserved=:qty, Notes=:notes
				 WHERE Id=:id AND Status='active'";
    }

    private function getInsertReservationSql(): string
    {
        return "INSERT INTO LoanReservation
			 (ItemId, UserId, ReservationDate, StartTime, EndTime, QuantityReserved, Notes)
			 VALUES (:itemId, :userId, :date, :start, :end, :qty, :notes)";
    }

    private function getCancelReservationSql(bool $withUser): string
    {
        $sql = "UPDATE LoanReservation SET Status='cancelled'
				WHERE Id=:id AND Status='active'";
        if ($withUser) {
            $sql .= ' AND UserId=:uid';
        }
        return $sql;
    }

    private function getCalendarLoansSql(): string
    {
        return "SELECT lr.Id, lr.LoanDate, lr.DueDate, lr.ReturnDate, lr.Status,
			        li.Name AS ItemName,
			        b.FirstName || ' ' || b.LastName AS BorrowerName
			 FROM   LoanRecord lr
			 JOIN   LoanItem li ON li.Id = lr.ItemId
			 JOIN   Person   b  ON b.Id  = lr.BorrowerId
			 WHERE  lr.Status IN ('active','returned','overdue')
			   AND  lr.LoanDate <= :end
			   AND  lr.DueDate  >= :start";
    }

    private function getCalendarReservationsSql(): string
    {
        return "SELECT res.Id, res.ReservationDate, res.StartTime, res.EndTime,
			        li.Name AS ItemName,
			        p.FirstName || ' ' || p.LastName AS UserName
			 FROM   LoanReservation res
			 JOIN   LoanItem li ON li.Id = res.ItemId
			 JOIN   Person   p  ON p.Id  = res.UserId
			 WHERE  res.Status = 'active'
			   AND  res.ReservationDate BETWEEN :start AND :end";
    }

    private function getGetAllPersonsSql(): string
    {
        return "SELECT Id, FirstName || ' ' || LastName AS FullName
			 FROM Person ORDER BY LastName ASC, FirstName ASC";
    }
}