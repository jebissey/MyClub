<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class SharedFileDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'SharedFile', [
            'Item',
            'Token',
            'IdGroup',
            'OnlyForMembers',
        ]);
    }

    public function testGetPathsSharedSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetPathsSharedSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Item FROM SharedFile', $sql);
        $this->assertStringContainsString('WHERE Token IS NOT NULL', $sql);
    }

    public function testGetSharedFileSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetSharedFileSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('IdGroup AS idGroup', $sql);
        $this->assertStringContainsString('OnlyForMembers AS membersOnly', $sql);
        $this->assertStringContainsString('Token', $sql);
        $this->assertStringContainsString('FROM SharedFile', $sql);
        $this->assertStringContainsString('WHERE Item = :path', $sql);
        $this->assertStringContainsString('LIMIT 1', $sql);
    }

    public function testRemoveShareFileSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getRemoveShareFileSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE SharedFile', $sql);
        $this->assertStringContainsString('SET Token = null', $sql);
        $this->assertStringContainsString('WHERE Item = :path', $sql);
    }

    private function getGetPathsSharedSql(): string
    {
        return 'SELECT Item FROM SharedFile WHERE Token IS NOT NULL';
    }

    private function getGetSharedFileSql(): string
    {
        return "SELECT 
                    IdGroup AS idGroup,
                    OnlyForMembers AS membersOnly,
                    Token
                FROM SharedFile
                WHERE Item = :path
                LIMIT 1";
    }

    private function getRemoveShareFileSql(): string
    {
        return "UPDATE SharedFile 
                SET Token = null
                WHERE Item = :path";
    }
}