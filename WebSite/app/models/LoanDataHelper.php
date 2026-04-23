<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

use PDO;

class LoanDataHelper extends Data
{
	public function __construct(Application $application)
	{
		parent::__construct($application);
	}

	// ══════════════════════════════════════════════════════════════════════════
	// MATÉRIELS
	// ══════════════════════════════════════════════════════════════════════════

	public function getAllItems(): array
	{
		$stmt = $this->pdo->query(
			"SELECT * FROM LoanItem ORDER BY IsActive DESC, Name ASC"
		);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/** @param string|null $type 'loan' | 'reservation' | 'both' | null (tous actifs) */
	public function getActiveItems(?string $type = null): array
	{
		if ($type === null) {
			$stmt = $this->pdo->query(
				"SELECT * FROM LoanItem WHERE IsActive = 1 ORDER BY Name ASC"
			);
		} else {
			$stmt = $this->pdo->prepare(
				"SELECT * FROM LoanItem WHERE IsActive = 1
				 AND (Type = :type OR Type = 'both') ORDER BY Name ASC"
			);
			$stmt->execute([':type' => $type]);
		}
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getItem(int $id): ?array
	{
		$stmt = $this->pdo->prepare("SELECT * FROM LoanItem WHERE Id = :id");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	/** Crée ou met à jour un matériel. Retourne l'Id. */
	public function saveItem(array $data): int
	{
		$id          = (int)($data['id'] ?? 0);
		$name        = trim($data['name'] ?? '');
		$description = trim($data['description'] ?? '');
		$type        = in_array($data['type'] ?? '', ['loan', 'reservation', 'both'], true)
			? $data['type'] : 'both';
		$quantity    = max(1, (int)($data['quantity'] ?? 1));
		$isActive    = isset($data['isActive']) ? (int)(bool)$data['isActive'] : 1;

		if ($id > 0) {
			$stmt = $this->pdo->prepare(
				"UPDATE LoanItem
				 SET Name=:name, Description=:desc, Type=:type,
				     Quantity=:qty, IsActive=:active,
				     UpdatedAt=datetime('now')
				 WHERE Id=:id"
			);
			$stmt->execute([
				':name'   => $name,
				':desc'   => $description,
				':type'   => $type,
				':qty'    => $quantity,
				':active' => $isActive,
				':id'     => $id,
			]);
			return $id;
		}

		$stmt = $this->pdo->prepare(
			"INSERT INTO LoanItem (Name, Description, Type, Quantity, IsActive)
			 VALUES (:name, :desc, :type, :qty, :active)"
		);
		$stmt->execute([
			':name'   => $name,
			':desc'   => $description,
			':type'   => $type,
			':qty'    => $quantity,
			':active' => $isActive,
		]);
		return (int)$this->pdo->lastInsertId();
	}

	/** Supprime un matériel (uniquement s'il n'a pas de prêts/réservations actifs). */
	public function deleteItem(int $id): bool
	{
		$stmt = $this->pdo->prepare(
			"SELECT COUNT(*) FROM LoanRecord
			 WHERE ItemId=:id AND Status='active'"
		);
		$stmt->execute([':id' => $id]);
		if ((int)$stmt->fetchColumn() > 0) {
			return false;
		}
		$stmt2 = $this->pdo->prepare(
			"SELECT COUNT(*) FROM LoanReservation
			 WHERE ItemId=:id AND Status='active'"
		);
		$stmt2->execute([':id' => $id]);
		if ((int)$stmt2->fetchColumn() > 0) {
			return false;
		}

		$this->pdo->prepare("DELETE FROM LoanItem WHERE Id=:id")
			->execute([':id' => $id]);
		return true;
	}

	// ══════════════════════════════════════════════════════════════════════════
	// PRÊTS
	// ══════════════════════════════════════════════════════════════════════════

	private function loanSelectBase(): string
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

	public function getAllLoans(string $status = ''): array
	{
		$base = $this->loanSelectBase();
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
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getLoan(int $id): ?array
	{
		$base = $this->loanSelectBase();
		$stmt = $this->pdo->prepare("$base WHERE lr.Id = :id");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	/**
	 * Retourne la quantité disponible pour un prêt (à emporter).
	 * Tient compte des prêts actifs qui se chevauchent avec la période demandée.
	 */
	public function getAvailableQtyForLoan(
		int $itemId,
		string $loanDate,
		string $dueDate,
		?int $excludeId = null
	): int {
		$item = $this->getItem($itemId);
		if (!$item) {
			return 0;
		}

		$sql = "SELECT COALESCE(SUM(QuantityLent), 0)
				FROM LoanRecord
				WHERE ItemId = :itemId
				  AND Status IN ('active','overdue')
				  AND LoanDate <= :due
				  AND DueDate  >= :loan";
		if ($excludeId !== null) {
			$sql .= " AND Id <> :excludeId";
		}
		$stmt = $this->pdo->prepare($sql);
		$params = [
			':itemId' => $itemId,
			':loan'   => $loanDate,
			':due'    => $dueDate,
		];
		if ($excludeId !== null) {
			$params[':excludeId'] = $excludeId;
		}
		$stmt->execute($params);
		$used = (int)$stmt->fetchColumn();
		return max(0, (int)$item['Quantity'] - $used);
	}

	/** Crée ou met à jour un prêt. Retourne l'Id. */
	public function saveLoan(array $data): int
	{
		$id         = (int)($data['id'] ?? 0);
		$itemId     = (int)($data['itemId'] ?? 0);
		$borrowerId = (int)($data['borrowerId'] ?? 0);
		$lenderId   = (int)($data['lenderId'] ?? 0);
		$loanDate   = $data['loanDate'] ?? '';
		$dueDate    = $data['dueDate'] ?? '';
		$qty        = max(1, (int)($data['quantity'] ?? 1));
		$notes      = trim($data['notes'] ?? '');

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

	/** Enregistre le retour d'un matériel prêté. */
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

	/** Met à jour les prêts dont la date de retour prévue est dépassée. */
	public function updateOverdueLoans(): void
	{
		$this->pdo->exec(
			"UPDATE LoanRecord SET Status='overdue'
			 WHERE Status='active' AND DueDate < date('now')"
		);
	}

	// ══════════════════════════════════════════════════════════════════════════
	// RÉSERVATIONS
	// ══════════════════════════════════════════════════════════════════════════

	private function reservationSelectBase(): string
	{
		return "SELECT res.*,
				       li.Name     AS ItemName,
				       li.Quantity AS ItemTotalQty,
				       p.FirstName || ' ' || p.LastName AS UserName
				FROM   LoanReservation res
				JOIN   LoanItem li ON li.Id = res.ItemId
				JOIN   Person   p  ON p.Id  = res.UserId";
	}

	public function getAllReservations(int $userId = 0): array
	{
		$base = $this->reservationSelectBase();
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
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getReservation(int $id): ?array
	{
		$base = $this->reservationSelectBase();
		$stmt = $this->pdo->prepare("$base WHERE res.Id = :id");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	/**
	 * Retourne la quantité disponible pour une réservation sur place.
	 * Tient compte des réservations actives qui se chevauchent le même jour/horaire.
	 */
	public function getAvailableQtyForReservation(
		int $itemId,
		string $date,
		string $startTime,
		string $endTime,
		?int $excludeId = null
	): int {
		$item = $this->getItem($itemId);
		if (!$item) {
			return 0;
		}

		$sql = "SELECT COALESCE(SUM(QuantityReserved), 0)
				FROM LoanReservation
				WHERE ItemId = :itemId
				  AND Status = 'active'
				  AND ReservationDate = :date
				  AND StartTime < :end
				  AND EndTime   > :start";
		if ($excludeId !== null) {
			$sql .= " AND Id <> :excludeId";
		}
		$stmt = $this->pdo->prepare($sql);
		$params = [
			':itemId' => $itemId,
			':date'   => $date,
			':start'  => $startTime,
			':end'    => $endTime,
		];
		if ($excludeId !== null) {
			$params[':excludeId'] = $excludeId;
		}
		$stmt->execute($params);
		$used = (int)$stmt->fetchColumn();
		return max(0, (int)$item['Quantity'] - $used);
	}

	/** Crée ou met à jour une réservation. Retourne l'Id. */
	public function saveReservation(array $data): int
	{
		$id     = (int)($data['id'] ?? 0);
		$itemId = (int)($data['itemId'] ?? 0);
		$userId = (int)($data['userId'] ?? 0);
		$date   = $data['reservationDate'] ?? '';
		$start  = $data['startTime'] ?? '';
		$end    = $data['endTime'] ?? '';
		$qty    = max(1, (int)($data['quantity'] ?? 1));
		$notes  = trim($data['notes'] ?? '');

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
		// Un utilisateur simple ne peut annuler que ses propres réservations
		if ($userId > 0) {
			$sql    .= " AND UserId=:uid";
			$params[':uid'] = $userId;
		}
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
		return $stmt->rowCount() > 0;
	}

	// ══════════════════════════════════════════════════════════════════════════
	// CALENDRIER
	// ══════════════════════════════════════════════════════════════════════════

	/** Retourne les événements FullCalendar pour la période [start, end]. */
	public function getCalendarEvents(string $start, string $end): array
	{
		$events = [];

		// Prêts
		$stmt = $this->pdo->prepare(
			"SELECT lr.Id, lr.LoanDate, lr.DueDate, lr.ReturnDate, lr.Status,
			        li.Name AS ItemName,
			        b.FirstName || ' ' || b.LastName AS BorrowerName
			 FROM   LoanRecord lr
			 JOIN   LoanItem li ON li.Id = lr.ItemId
			 JOIN   Person   b  ON b.Id  = lr.BorrowerId
			 WHERE  lr.Status IN ('active','returned','overdue')
			   AND  lr.LoanDate <= :end
			   AND  lr.DueDate  >= :start"
		);
		$stmt->execute([':start' => $start, ':end' => $end]);
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$color = match ($row['Status']) {
				'returned' => '#198754',
				'overdue'  => '#dc3545',
				default    => '#0d6efd',
			};
			$endDate = $row['ReturnDate'] ?? $row['DueDate'];
			// FullCalendar : end est exclusif, on ajoute 1 jour pour les all-day events
			$endDateExclusive = date('Y-m-d', strtotime($endDate . ' +1 day'));
			$events[] = [
				'id'              => 'loan-' . $row['Id'],
				'title'           => $row['ItemName'] . ' → ' . $row['BorrowerName'],
				'start'           => $row['LoanDate'],
				'end'             => $endDateExclusive,
				'backgroundColor' => $color,
				'borderColor'     => $color,
				'extendedProps'   => [
					'type'   => 'loan',
					'id'     => $row['Id'],
					'status' => $row['Status'],
				],
			];
		}

		// Réservations
		$stmt2 = $this->pdo->prepare(
			"SELECT res.Id, res.ReservationDate, res.StartTime, res.EndTime,
			        li.Name AS ItemName,
			        p.FirstName || ' ' || p.LastName AS UserName
			 FROM   LoanReservation res
			 JOIN   LoanItem li ON li.Id = res.ItemId
			 JOIN   Person   p  ON p.Id  = res.UserId
			 WHERE  res.Status = 'active'
			   AND  res.ReservationDate BETWEEN :start AND :end"
		);
		$stmt2->execute([':start' => $start, ':end' => $end]);
		foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$events[] = [
				'id'              => 'res-' . $row['Id'],
				'title'           => $row['ItemName'] . ' – ' . $row['UserName'],
				'start'           => $row['ReservationDate'] . 'T' . $row['StartTime'],
				'end'             => $row['ReservationDate'] . 'T' . $row['EndTime'],
				'backgroundColor' => '#fd7e14',
				'borderColor'     => '#fd7e14',
				'extendedProps'   => [
					'type' => 'reservation',
					'id'   => $row['Id'],
				],
			];
		}

		return $events;
	}

	// ══════════════════════════════════════════════════════════════════════════
	// UTILITAIRES
	// ══════════════════════════════════════════════════════════════════════════

	/** Retourne toutes les personnes (pour les selects borrower/lender/user). */
	public function getAllPersons(): array
	{
		$stmt = $this->pdo->query(
			"SELECT Id, FirstName || ' ' || LastName AS FullName
			 FROM Person ORDER BY LastName ASC, FirstName ASC"
		);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
