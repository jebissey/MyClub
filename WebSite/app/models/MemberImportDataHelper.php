<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use RuntimeException;
use app\helpers\Application;
use app\helpers\To;

final class MemberImportDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @return array<int, array{
     *  email: string,
     *  firstName: string,
     *  lastName: string,
     *  phone: string,
     *  imported: bool,
     *  inactivated: bool,
     *  customFields: string|null}>
     */
    public function getMembersForImport(): array
    {
        $stmt = $this->pdo->query('
            SELECT i.Id, i.Email, i.FirstName, i.LastName, i.Phone, m.Imported, m.Inactivated, m.CustomFields
            FROM Individual i
            JOIN Member m ON m.Id = i.Id
        ');
        if ($stmt === false) {
            throw new RuntimeException('Unable to read the members for the import');
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $members = [];
        foreach ($rows as $row) {
            $customJson = $row['CustomFields'] ?? null;
            $members[To::int($row['Id'])] = [
                'email' => To::str($row['Email'] ?? ''),
                'firstName' => To::str($row['FirstName'] ?? ''),
                'lastName' => To::str($row['LastName'] ?? ''),
                'phone' => To::str($row['Phone'] ?? ''),
                'imported' => To::bool($row['Imported'] ?? false),
                'inactivated' => To::bool($row['Inactivated'] ?? false),
                'customFields' => is_string($customJson) ? $customJson : null,
            ];
        }

        return $members;
    }

    /**
     * Creates the Individual and its Member record; returns the new Id.
     */
    public function insertMember(
        string $email,
        string $firstName,
        string $lastName,
        string $phone,
        bool $inactivated,
        ?string $customFields
    ): int {
        $this->pdo->prepare("
            INSERT INTO Individual (Type, Email, FirstName, LastName, Phone)
            VALUES ('Member', :email, :firstName, :lastName, :phone)
        ")->execute([
            ':email' => $email,
            ':firstName' => $firstName,
            ':lastName' => $lastName,
            ':phone' => $phone,
        ]);
        $id = (int)$this->pdo->lastInsertId();

        $this->pdo->prepare('
            INSERT INTO Member (Id, Imported, Inactivated, CustomFields)
            VALUES (:id, 0, :inactivated, :customFields)
        ')->execute([
            ':id' => $id,
            ':inactivated' => $inactivated ? 1 : 0,
            ':customFields' => $customFields,
        ]);

        return $id;
    }

    public function updateIndividual(int $id, string $email, string $firstName, string $lastName, string $phone): void
    {
        $this->pdo->prepare('
            UPDATE Individual
            SET Email = :email, FirstName = :firstName, LastName = :lastName, Phone = :phone
            WHERE Id = :id
        ')->execute([
            ':id' => $id,
            ':email' => $email,
            ':firstName' => $firstName,
            ':lastName' => $lastName,
            ':phone' => $phone,
        ]);
    }

    public function updateMemberState(int $id, bool $inactivated, ?string $customFields): void
    {
        $this->pdo->prepare('
            UPDATE Member SET Inactivated = :inactivated, CustomFields = :customFields WHERE Id = :id
        ')->execute([
            ':id' => $id,
            ':inactivated' => $inactivated ? 1 : 0,
            ':customFields' => $customFields,
        ]);
    }

    public function deactivateMember(int $id): void
    {
        $this->pdo->prepare('UPDATE Member SET Inactivated = 1 WHERE Id = :id')->execute([':id' => $id]);
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }


    /**
     * Upsert Individual on Email (ON CONFLICT), returns the Id (new or existing).
     *
     * @param array{email: string, firstName: string, lastName: string, phone: string} $person
     */
    public function upsertIndividualByEmail(array $person): int
    {
        $this->pdo->prepare("
        INSERT INTO Individual (Type, Email, FirstName, LastName, Phone)
        VALUES ('Member', :email, :firstName, :lastName, :phone)
        ON CONFLICT(Email) DO UPDATE SET
            FirstName = excluded.FirstName,
            LastName  = excluded.LastName,
            Phone     = excluded.Phone
    ")->execute([
            ':email'     => $person['email'],
            ':firstName' => $person['firstName'],
            ':lastName'  => $person['lastName'],
            ':phone'     => $person['phone'],
        ]);

        $id = (int) $this->pdo->lastInsertId();
        if ($id === 0) {
            $stmt = $this->pdo->prepare('SELECT Id FROM Individual WHERE Email = :email');
            $stmt->execute([':email' => $person['email']]);
            $id = (int) $stmt->fetchColumn();
        }

        return $id;
    }

    /**
     * Ensure Member row exists and is marked Imported=1, Inactivated=0.
     */
    public function upsertMemberAsImported(int $id): void
    {
        $this->pdo->prepare("
        INSERT INTO Member (Id, Imported, Inactivated)
        VALUES (:id, 1, 0)
        ON CONFLICT(Id) DO UPDATE SET
            Imported    = 1,
            Inactivated = 0
    ")->execute([':id' => $id]);
    }

    public function getCustomFieldsJson(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT CustomFields FROM Member WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $json = $stmt->fetchColumn();

        return is_string($json) ? $json : null;
    }

    public function setCustomFieldsJson(int $id, string $customFieldsJson): void
    {
        $this->pdo->prepare('UPDATE Member SET CustomFields = :customFields WHERE Id = :id')
            ->execute([
                ':id'           => $id,
                ':customFields' => $customFieldsJson,
            ]);
    }

    /**
     * @param list<int> $ids
     */
    public function deactivateMembers(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("
        UPDATE Member
        SET Inactivated = 1
        WHERE Id IN ($placeholders)
    ");
        $stmt->execute($ids);

        return $stmt->rowCount();
    }
}
