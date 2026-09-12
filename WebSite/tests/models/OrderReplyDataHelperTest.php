<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class OrderReplyDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'OrderReply', [
            'Id',
            'IdPerson',
            'IdOrder',
            'Answers',
            'LastUpdate',
        ]);
    }

    public function testInsertOrUpdateReliesOnExistingColumns(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // The underlying Data::get() and Data::set() use these columns
        $this->assertColumnsExist($pdo, 'OrderReply', [
            'Id',
            'IdPerson',
            'IdOrder',
            'Answers',
            'LastUpdate',
        ]);
    }

    public function testInsertOrderReplySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getInsertOrderReplySql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO OrderReply', $sql);
        $this->assertStringContainsString('(IdPerson, IdOrder, Answers, LastUpdate)', $sql);
        $this->assertStringContainsString('VALUES (?, ?, ?, ?)', $sql);
    }

    public function testUpdateOrderReplySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateOrderReplySql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE OrderReply', $sql);
        $this->assertStringContainsString('SET Answers = ?, LastUpdate = ?', $sql);
        $this->assertStringContainsString('WHERE Id = ?', $sql);
    }

    public function testSelectExistingReplySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getSelectExistingReplySql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Id FROM OrderReply', $sql);
        $this->assertStringContainsString('WHERE IdPerson = ? AND IdOrder = ?', $sql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from OrderReplyDataHelper / Data layer)
    // -------------------------------------------------------------------------

    private function getSelectExistingReplySql(): string
    {
        return 'SELECT Id FROM OrderReply WHERE IdPerson = ? AND IdOrder = ?';
    }

    private function getInsertOrderReplySql(): string
    {
        return 'INSERT INTO OrderReply (IdPerson, IdOrder, Answers, LastUpdate) VALUES (?, ?, ?, ?)';
    }

    private function getUpdateOrderReplySql(): string
    {
        return 'UPDATE OrderReply SET Answers = ?, LastUpdate = ? WHERE Id = ?';
    }
}