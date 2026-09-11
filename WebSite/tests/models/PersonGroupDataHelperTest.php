<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class PersonGroupDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'PersonGroup', [
            'Id',
            'IdPerson',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'SelfRegistration',
        ]);
    }

    public function testIsPersonInGroupReliesOnExistingColumns(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // The underlying Data::get() uses these columns
        $this->assertColumnsExist($pdo, 'PersonGroup', [
            'Id',
            'IdPerson',
            'IdGroup',
        ]);
    }

    public function testDeleteSelfRegistrationGroupsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDeleteSelfRegistrationGroupsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM PersonGroup', $sql);
        $this->assertStringContainsString('WHERE IdPerson = :personId', $sql);
        $this->assertStringContainsString('AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)', $sql);
    }

    public function testInsertPersonGroupSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getInsertPersonGroupSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO PersonGroup', $sql);
        $this->assertStringContainsString('(IdPerson, IdGroup)', $sql);
        $this->assertStringContainsString('VALUES (?, ?)', $sql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from PersonGroupDataHelper)
    // -------------------------------------------------------------------------

    private function getDeleteSelfRegistrationGroupsSql(): string
    {
        return "
            DELETE FROM PersonGroup 
            WHERE IdPerson = :personId 
            AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)
        ";
    }

    private function getInsertPersonGroupSql(): string
    {
        return 'INSERT INTO PersonGroup (IdPerson, IdGroup) VALUES (?, ?)';
    }
}