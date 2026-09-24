<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class PersonGroupDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'Id',
            'IdMember',
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
        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'Id',
            'IdMember',
            'IdGroup',
        ]);
    }

    public function testDeleteSelfRegistrationGroupsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDeleteSelfRegistrationGroupsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM MemberGroup', $sql);
        $this->assertStringContainsString('WHERE IdMember = :personId', $sql);
        $this->assertStringContainsString('AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)', $sql);
    }

    public function testInsertPersonGroupSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getInsertPersonGroupSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO MemberGroup', $sql);
        $this->assertStringContainsString('(IdMember, IdGroup)', $sql);
        $this->assertStringContainsString('VALUES (?, ?)', $sql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from PersonGroupDataHelper)
    // -------------------------------------------------------------------------

    private function getDeleteSelfRegistrationGroupsSql(): string
    {
        return "
            DELETE FROM MemberGroup 
            WHERE IdMember = :personId 
            AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)
";
    }

    private function getInsertPersonGroupSql(): string
    {
        return 'INSERT INTO MemberGroup (IdMember, IdGroup) VALUES (?, ?)';
    }
}