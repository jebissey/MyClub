<?php

declare(strict_types=1);

namespace app\models\database;

use PDO;
use PDOStatement;
use app\helpers\Application;
use app\helpers\LogMessage;
use app\helpers\To;

/**
 * Deletes rows whose foreign key points to a missing parent, and verifies that none remain.
 */
final class OrphanRecordsHandler
{
    public function delete(PDO $pdo, LogMessage $logMessage): void
    {
        foreach ($this->listTables($pdo) as $table) {
            foreach ($this->listForeignKeys($pdo, $table) as $reference) {
                $this->deleteOrphansOfReference($pdo, $table, $reference, $logMessage);
            }
        }
    }

    public function assertNone(PDO $pdo): void
    {
        $stmt = $pdo->query('PRAGMA foreign_key_check');
        if (!$stmt instanceof PDOStatement) {
            return;
        }
        $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($violations === []) {
            return;
        }
        $details = implode('; ', array_map($this->describeViolation(...), $violations));
        Application::unreachable("Orphaned records found after migration: {$details}");
    }

    /**
     * @return list<string>
     */
    private function listTables(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
        if (!$stmt instanceof PDOStatement) {
            return [];
        }
        $names = array_map(
            static fn(mixed $name): string => To::str($name),
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        );
        return array_values(array_filter($names, static fn(string $name): bool => $name !== ''));
    }

    /**
     * @return list<array{from: string, to: string, table: string}>
     */
    private function listForeignKeys(PDO $pdo, string $table): array
    {
        $stmt = $pdo->query('PRAGMA foreign_key_list(' . $pdo->quote($table) . ')');
        if (!$stmt instanceof PDOStatement) {
            return [];
        }
        $references = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $reference = is_array($row) ? $this->toReference($row) : null;
            if ($reference !== null) {
                $references[] = $reference;
            }
        }
        return $references;
    }

    /**
     * @param array<mixed> $row
     * @return array{from: string, to: string, table: string}|null
     */
    private function toReference(array $row): ?array
    {
        $reference = [
            'from' => To::str($row['from'] ?? null),
            'to' => To::str($row['to'] ?? null),
            'table' => To::str($row['table'] ?? null),
        ];
        // 'to' is NULL in SQLite when the foreign key implicitly references the parent
        // primary key. An empty identifier would make the DELETE wrong, so such keys are skipped.
        return in_array('', $reference, true) ? null : $reference;
    }

    /**
     * @param array{from: string, to: string, table: string} $reference
     */
    private function deleteOrphansOfReference(PDO $pdo, string $table, array $reference, LogMessage $logMessage): void
    {
        ['from' => $from, 'to' => $to, 'table' => $parentTable] = $reference;
        $sql = sprintf(
            'DELETE FROM "%s" WHERE "%s" IS NOT NULL AND "%s" NOT IN (SELECT "%s" FROM "%s")',
            $table,
            $from,
            $from,
            $to,
            $parentTable
        );
        $deleted = $pdo->exec($sql);
        if ($deleted === false || $deleted === 0) {
            return;
        }
        $logMessage->setMessage(
            "Orphans deleted in {$table}.{$from} referencing {$parentTable}.{$to}: {$deleted} row(s)"
        );
    }

    private function describeViolation(mixed $violation): string
    {
        if (!is_array($violation)) {
            return 'unknown violation';
        }
        return 'table=' . To::str($violation['table'] ?? null)
            . ' rowid=' . To::str($violation['rowid'] ?? null)
            . ' parent=' . To::str($violation['parent'] ?? null)
            . ' fkid=' . To::str($violation['fkid'] ?? null);
    }
}
