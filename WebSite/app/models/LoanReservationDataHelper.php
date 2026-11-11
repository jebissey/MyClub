<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;
use app\modules\Loan\valueObjects\LoanReservationRow;
use PDO;

final class LoanReservationDataHelper extends Data
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

    /** @return list<LoanReservationRow> */
    public function getAllReservations(int $userId = 0): array
    {
        $base = $this->selectBase();
        if ($userId > 0) {
            $stmt = $this->pdo->prepare(
                "$base WHERE res.UserId = :uid ORDER BY res.ReservationDate DESC, res.StartTime ASC"
            );
            $stmt->execute([':uid' => $userId]);
        } else {
            $stmt = $this->pdo->query(
                "$base ORDER BY res.ReservationDate DESC, res.StartTime ASC"
            );
        }
        return array_values(array_map(LoanReservationRow::fromArray(...), $this->fetchAllOrFail($stmt)));
    }

    /**
     * Active reservations whose date is within [start, end].
     * @return list<LoanReservationRow>
     */
    public function getActiveReservationsBetween(string $start, string $end): array
    {
        $base = $this->selectBase();
        $stmt = $this->pdo->prepare(
            "$base
             WHERE res.Status = 'active'
               AND res.ReservationDate BETWEEN :start AND :end
             ORDER BY res.ReservationDate ASC, res.StartTime ASC"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        return array_values(array_map(LoanReservationRow::fromArray(...), $this->fetchAllOrFail($stmt)));
    }

    /** @return array<string, mixed>|null */
    public function getReservation(int $id): ?array
    {
        $base = $this->selectBase();
        $stmt = $this->pdo->prepare("$base WHERE res.Id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * Quantity of an item already reserved on the same day and overlapping time slot.
     */
    public function getReservedQuantity(
        int $itemId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): int {
        $sql = "SELECT COALESCE(SUM(QuantityReserved), 0)
                FROM LoanReservation
                WHERE ItemId = :itemId
                  AND Status = 'active'
                  AND ReservationDate = :date
                  AND StartTime < :end
                  AND EndTime   > :start";
        $params = [
            ':itemId' => $itemId,
            ':date'   => $date,
            ':start'  => $startTime,
            ':end'    => $endTime,
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
     * Creates or updates a reservation. Returns its Id.
     * @param array<string, mixed> $data
     */
    public function saveReservation(array $data): int
    {
        $idRaw     = $data['id'] ?? 0;
        $id        = is_scalar($idRaw) ? (int)$idRaw : 0;

        $itemIdRaw = $data['itemId'] ?? 0;
        $itemId    = is_scalar($itemIdRaw) ? (int)$itemIdRaw : 0;

        $userIdRaw = $data['userId'] ?? 0;
        $userId    = is_scalar($userIdRaw) ? (int)$userIdRaw : 0;

        $date      = $data['reservationDate'] ?? '';
        $start     = $data['startTime'] ?? '';
        $end       = $data['endTime'] ?? '';

        $qtyRaw    = $data['quantity'] ?? 1;
        $qty       = max(1, is_scalar($qtyRaw) ? (int)$qtyRaw : 1);

        $notesRaw  = $data['notes'] ?? '';
        $notes     = trim(is_string($notesRaw) ? $notesRaw : '');

        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                "UPDATE LoanReservation
                 SET ItemId=:itemId, UserId=:userId,
                     ReservationDate=:date, StartTime=:start, EndTime=:end,
                     QuantityReserved=:qty, Notes=:notes
                 WHERE Id=:id AND Status='active'"
            );
            $stmt->execute([
                ':itemId' => $itemId,
                ':userId' => $userId,
                ':date'   => $date,
                ':start'  => $start,
                ':end'    => $end,
                ':qty'    => $qty,
                ':notes'  => $notes,
                ':id'     => $id,
            ]);
            return $id;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO LoanReservation
             (ItemId, UserId, ReservationDate, StartTime, EndTime, QuantityReserved, Notes)
             VALUES (:itemId, :userId, :date, :start, :end, :qty, :notes)"
        );
        $stmt->execute([
            ':itemId' => $itemId,
            ':userId' => $userId,
            ':date'   => $date,
            ':start'  => $start,
            ':end'    => $end,
            ':qty'    => $qty,
            ':notes'  => $notes,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function cancelReservation(int $id, int $userId = 0): bool
    {
        $sql = "UPDATE LoanReservation SET Status='cancelled'
                WHERE Id=:id AND Status='active'";
        $params = [':id' => $id];
        // A regular user can only cancel their own reservations
        if ($userId > 0) {
            $sql .= " AND UserId=:uid";
            $params[':uid'] = $userId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
}
