<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class MenuItemDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'MenuItem', [
            'Id',
            'ParentId',
            'IdGroup',
            'ForMembers',
            'ForAnonymous',
            'Url',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
        ]);
    }

    public function testAuthorizedUserSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAuthorizedUserSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('MenuItem.IdGroup,', $sql);
        $this->assertStringContainsString('MenuItem.ForMembers,', $sql);
        $this->assertStringContainsString('MenuItem.ForAnonymous,', $sql);
        $this->assertStringContainsString('"Group".Id AS groupId', $sql);
        $this->assertStringContainsString('FROM MenuItem', $sql);
        $this->assertStringContainsString('LEFT JOIN "Group" ON MenuItem.IdGroup = "Group".Id', $sql);
        $this->assertStringContainsString('WHERE MenuItem.Url = :route', $sql);
        $this->assertStringContainsString('LIMIT 1', $sql);
    }

    public function testSelectChildrenSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getSelectChildrenSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Id FROM MenuItem WHERE ParentId = :parentId', $sql);
    }

    public function testDeleteMenuItemSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDeleteMenuItemSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM MenuItem WHERE Id = :id', $sql);
    }

    private function getAuthorizedUserSql(): string
    {
        return '
            SELECT 
                MenuItem.IdGroup,
                MenuItem.ForMembers,
                MenuItem.ForAnonymous,
                "Group".Id AS groupId
            FROM MenuItem
            LEFT JOIN "Group" ON MenuItem.IdGroup = "Group".Id
            WHERE MenuItem.Url = :route
            LIMIT 1
        ';
    }

    private function getSelectChildrenSql(): string
    {
        return 'SELECT Id FROM MenuItem WHERE ParentId = :parentId';
    }

    private function getDeleteMenuItemSql(): string
    {
        return 'DELETE FROM MenuItem WHERE Id = :id';
    }
}