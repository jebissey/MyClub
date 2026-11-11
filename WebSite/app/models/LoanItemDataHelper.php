<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;
use app\helpers\To;
use app\modules\Loan\valueObjects\LoanItemRow;
use PDO;

final class LoanItemDataHelper extends Data
{
    private const COLUMNS = 'Id, Name, Description, Type, Quantity, IsActive';

    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @return list<LoanItemRow> */
    public function getAllItems(): array
    {
        $stmt = $this->pdo->query(
            "SELECT " . self::COLUMNS . "
             FROM LoanItem
             ORDER BY IsActive DESC, Name ASC"
        );
        return array_values(array_map(LoanItemRow::fromArray(...), $this->fetchAllOrFail($stmt)));
    }

    /**
     * @param string|null $type 'loan' | 'reservation' | 'both' | null (all active)
     * @return list<LoanItemRow>
     */
    public function getActiveItems(?string $type = null): array
    {
        if ($type === null) {
            $stmt = $this->pdo->query(
                "SELECT " . self::COLUMNS . "
                 FROM LoanItem
                 WHERE IsActive = 1
                 ORDER BY Name ASC"
            );
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT " . self::COLUMNS . "
                 FROM LoanItem
                 WHERE IsActive = 1
                   AND (Type = :type OR Type = 'both')
                 ORDER BY Name ASC"
            );
            $stmt->execute([':type' => $type]);
        }
        return array_values(array_map(LoanItemRow::fromArray(...), $this->fetchAllOrFail($stmt)));
    }

    /** @return array<string, mixed>|null */
    public function getItem(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT " . self::COLUMNS . " FROM LoanItem WHERE Id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        /** @var array<string, mixed> $row */
        return $row;
    }

    /** Total quantity owned for an item, or null if the item does not exist. */
    public function getItemQuantity(int $id): ?int
    {
        $stmt = $this->pdo->prepare("SELECT Quantity FROM LoanItem WHERE Id = :id");
        $stmt->execute([':id' => $id]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : To::int($value);
    }

    /**
     * Creates or updates an item. Returns its Id.
     * @param array<string, mixed> $data
     */
    public function saveItem(array $data): int
    {
        $idRaw          = $data['id'] ?? 0;
        $id             = is_scalar($idRaw) ? (int)$idRaw : 0;

        $nameRaw        = $data['name'] ?? '';
        $name           = trim(is_string($nameRaw) ? $nameRaw : '');

        $descriptionRaw = $data['description'] ?? '';
        $description    = trim(is_string($descriptionRaw) ? $descriptionRaw : '');

        $typeRaw        = $data['type'] ?? '';
        $type           = is_string($typeRaw) && in_array($typeRaw, ['loan', 'reservation', 'both'], true)
            ? $typeRaw : 'both';

        $quantityRaw    = $data['quantity'] ?? 1;
        $quantity       = max(1, is_scalar($quantityRaw) ? (int)$quantityRaw : 1);

        $isActive       = isset($data['isActive']) ? (int)(bool)$data['isActive'] : 1;

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

    /** Deletes an item (only if it has no active loans or reservations). */
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
}
