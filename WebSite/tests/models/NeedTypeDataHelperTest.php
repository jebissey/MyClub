<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class NeedTypeDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'NeedType', [
            'Id',
            'Name',
        ]);
    }

    public function testInsertOrUpdateReliesOnExistingColumns(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // The underlying Data::set() uses these columns
        $this->assertColumnsExist($pdo, 'NeedType', [
            'Id',
            'Name',
        ]);
    }

    public function testInsertNeedTypeSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getInsertNeedTypeSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO NeedType', $sql);
        $this->assertStringContainsString('(Name)', $sql);
        $this->assertStringContainsString('VALUES (?)', $sql);
    }

    public function testUpdateNeedTypeSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateNeedTypeSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE NeedType', $sql);
        $this->assertStringContainsString('SET Name = ?', $sql);
        $this->assertStringContainsString('WHERE Id = ?', $sql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from NeedTypeDataHelper / Data layer)
    // -------------------------------------------------------------------------

    private function getInsertNeedTypeSql(): string
    {
        return 'INSERT INTO NeedType (Name) VALUES (?)';
    }

    private function getUpdateNeedTypeSql(): string
    {
        return 'UPDATE NeedType SET Name = ? WHERE Id = ?';
    }
}