<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class EventTypeDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
            'Inactivated',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
        ]);

        $this->assertColumnsExist($pdo, 'PersonGroup', [
            'IdGroup',
            'IdPerson',
        ]);

        $this->assertColumnsExist($pdo, 'EventTypeAttribute', [
            'IdEventType',
            'IdAttribute',
        ]);
    }

    public function testGetsForSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetsForSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('et.Id, et.Name, et.Inactivated, et.IdGroup', $sql);
        $this->assertStringContainsString('LEFT JOIN `Group`', $sql);
        $this->assertStringContainsString('et.Inactivated = 0', $sql);
        $this->assertStringContainsString('PersonGroup', $sql);
        $this->assertStringContainsString('et.IdGroup is NULL', $sql);
        $this->assertStringContainsString('ORDER BY et.Name', $sql);
    }

    public function testUpdateSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $updateSql = $this->getUpdateEventTypeSql();
        $deleteSql = $this->getDeleteEventTypeAttributeSql();
        $insertSql = $this->getInsertEventTypeAttributeSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($updateSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($deleteSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($insertSql));

        $this->assertStringContainsString('UPDATE EventType SET Name = ?, IdGroup = ? WHERE Id = ?', $updateSql);
        $this->assertStringContainsString('DELETE FROM EventTypeAttribute WHERE IdEventType = ?', $deleteSql);
        $this->assertStringContainsString('INSERT INTO EventTypeAttribute (IdEventType, IdAttribute) VALUES (?, ?)', $insertSql);
    }

    private function getGetsForSql(): string
    {
        return "
            SELECT et.Id, et.Name, et.Inactivated, et.IdGroup
            FROM EventType et
            LEFT JOIN `Group` g ON et.IdGroup = g.Id
            WHERE et.Inactivated = 0 
            AND (
                g.Id IN (
                    SELECT pg.IdGroup
                    FROM PersonGroup pg
                    WHERE pg.IdPerson = ? AND pg.IdGroup = g.Id
                )
                OR et.IdGroup is NULL)
            ORDER BY et.Name
";
    }

    private function getUpdateEventTypeSql(): string
    {
        return 'UPDATE EventType SET Name = ?, IdGroup = ? WHERE Id = ?';
    }

    private function getDeleteEventTypeAttributeSql(): string
    {
        return 'DELETE FROM EventTypeAttribute WHERE IdEventType = ?';
    }

    private function getInsertEventTypeAttributeSql(): string
    {
        return 'INSERT INTO EventTypeAttribute (IdEventType, IdAttribute) VALUES (?, ?)';
    }
}