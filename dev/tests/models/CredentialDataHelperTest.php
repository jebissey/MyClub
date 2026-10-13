<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class CredentialDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::CREDENTIAL_DB_PATH);

        $this->assertColumnsExist($pdo, 'Credential', [
            'Service',
            'Name',
            'Value',
            'UpdatedAt',
        ]);
    }

    public function testGetValueSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::CREDENTIAL_DB_PATH);
        $sql = $this->getValueSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString(
            'SELECT Value FROM "Credential"',
            $sql,
        );
        $this->assertStringContainsString(
            'WHERE Service = :service AND Name = :name',
            $sql,
        );
        $this->assertStringContainsString('LIMIT 1', $sql);
    }

    public function testSetValueSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::CREDENTIAL_DB_PATH);
        $sql = $this->getSetValueSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString(
            'INSERT INTO "Credential" (Service, Name, Value, UpdatedAt)',
            $sql,
        );
        $this->assertStringContainsString(
            'VALUES (:service, :name, :value, :updatedAt)',
            $sql,
        );
        $this->assertStringContainsString(
            'ON CONFLICT(Service, Name)',
            $sql,
        );
        $this->assertStringContainsString(
            'DO UPDATE SET Value = excluded.Value,',
            $sql,
        );
        $this->assertStringContainsString(
            'UpdatedAt = excluded.UpdatedAt',
            $sql,
        );
    }

    private function getValueSql(): string
    {
        return 'SELECT Value FROM "Credential"
             WHERE Service = :service AND Name = :name
             LIMIT 1';
    }

    private function getSetValueSql(): string
    {
        return 'INSERT INTO "Credential" (Service, Name, Value, UpdatedAt)
             VALUES (:service, :name, :value, :updatedAt)
             ON CONFLICT(Service, Name)
             DO UPDATE SET Value = excluded.Value,
                           UpdatedAt = excluded.UpdatedAt';
    }
}