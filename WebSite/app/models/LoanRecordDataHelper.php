<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;
use app\modules\Loan\valueObjects\LoanRecordRow;
use PDO;

final class LoanRecordDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
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

    /** @return list<LoanRecordRow> */
    public function getAllLoans(string $status = ''): array
    {
        $base = $this->selectBase();
        if ($status !== '') {
            $stmt = $this->pdo->prepare(
                "$base WHERE lr.Status = :status ORDER BY lr.LoanDate DESC"
            );
            $stmt->execute([':status' => $status]);
        } else {
            $stmt = $this->pdo->query(
                "$base ORDER BY lr.LoanDate DESC"
            );
        }
        return array_values(array_map(LoanRecordRow::fromArray(...), $this->fetchAllOrFail($stmt)));
    }

    /**
     * Loans (except cancelled ones) overlapping the period [start, end].
     * @return list<LoanRecordRow>
     */
    public function getLoansBetween(string $start, string $end): array
    {
        $base = $this->selectBase();
        $stmt = $this->pdo->prepare(
            "$base
             WHERE lr.Status IN ('active','returned','overdue')
               AND lr.LoanDate <= :end
               AND lr.DueDate  >= :start
             ORDER BY lr.LoanDate ASC"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        return array_values(array_map(LoanRecordRow::fromArray(...), $this->fetchAllOrFail($stmt)));
    }

    /** @return array<string, mixed>|null */
    public function getLoan(int $id): ?array
    {
        $base = $this->selectBase();
        $stmt = $this->pdo->prepare("$base WHERE lr.Id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * Quantity of an item already lent (active or overdue loans) during the period.
     */
    public function getLentQuantity(
        int $itemId,
        string $loanDate,
        string $dueDate,
        ?int $excludeId = null
    ): int {
        $sql = "SELECT COALESCE(SUM(QuantityLent), 0)
                FROM LoanRecord
                WHERE ItemId = :itemId
                  AND Status IN ('active','overdue')
                  AND LoanDate <= :due
                  AND DueDate  >= :loan";
        $params = [
            ':itemId' => $itemId,
            ':loan'   => $loanDate,
            ':due'    => $dueDate,
        ];
        if ($excludeId !== null) {
            $sql .= " AND Id <> :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Creates or updates a loan. Returns its Id.
     * @param array<string, mixed> $data
     */
    public function saveLoan(array $data): int
    {
        $idRaw         = $data['id'] ?? 0;
        $id            = is_scalar($idRaw) ? (int)$idRaw : 0;

        $itemIdRaw     = $data['itemId'] ?? 0;
        $itemId        = is_scalar($itemIdRaw) ? (int)$itemIdRaw : 0;

        $borrowerIdRaw = $data['borrowerId'] ?? 0;
        $borrowerId    = is_scalar($borrowerIdRaw) ? (int)$borrowerIdRaw : 0;

        $lenderIdRaw   = $data['lenderId'] ?? 0;
        $lenderId      = is_scalar($lenderIdRaw) ? (int)$lenderIdRaw : 0;

        $loanDate      = $data['loanDate'] ?? '';
        $dueDate       = $data['dueDate'] ?? '';

        $qtyRaw        = $data['quantity'] ?? 1;
        $qty           = max(1, is_scalar($qtyRaw) ? (int)$qtyRaw : 1);

        $notesRaw      = $data['notes'] ?? '';
        $notes         = trim(is_string($notesRaw) ? $notesRaw : '');

        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                "UPDATE LoanRecord
                 SET ItemId=:itemId, BorrowerId=:borrowerId, LenderId=:lenderId,
                     LoanDate=:loanDate, DueDate=:dueDate,
                     QuantityLent=:qty, Notes=:notes
                 WHERE Id=:id AND Status='active'"
            );
            $stmt->execute([
                ':itemId'     => $itemId,
                ':borrowerId' => $borrowerId,
                ':lenderId'   => $lenderId,
                ':loanDate'   => $loanDate,
                ':dueDate'    => $dueDate,
                ':qty'        => $qty,
                ':notes'      => $notes,
                ':id'         => $id,
            ]);
            return $id;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO LoanRecord
             (ItemId, BorrowerId, LenderId, LoanDate, DueDate, QuantityLent, Notes)
             VALUES (:itemId, :borrowerId, :lenderId, :loanDate, :dueDate, :qty, :notes)"
        );
        $stmt->execute([
            ':itemId'     => $itemId,
            ':borrowerId' => $borrowerId,
            ':lenderId'   => $lenderId,
            ':loanDate'   => $loanDate,
            ':dueDate'    => $dueDate,
            ':qty'        => $qty,
            ':notes'      => $notes,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Records the return of a loaned item. */
    public function setLoanReturn(int $id, string $returnDate, int $returnedToId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE LoanRecord
             SET ReturnDate=:returnDate, ReturnedToId=:returnedToId, Status='returned'
             WHERE Id=:id AND Status IN ('active','overdue')"
        );
        $stmt->execute([
            ':returnDate'   => $returnDate,
            ':returnedToId' => $returnedToId,
            ':id'           => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function cancelLoan(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE LoanRecord SET Status='cancelled'
             WHERE Id=:id AND Status='active'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Marks as overdue the loans whose due date has passed. */
    public function updateOverdueLoans(): void
    {
        $this->pdo->exec(
            "UPDATE LoanRecord SET Status='overdue'
             WHERE Status='active' AND DueDate < date('now')"
        );
    }
}
