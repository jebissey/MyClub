<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class ExerciseTableDataHelperTest extends DataHelperTestCase
{
    public function testGetQueryForAnonymousUserSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getQueryForAnonymousUserSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM exercise_list_view', $sql);
        $this->assertStringContainsString('Id, CreatedBy, Title, Detail, LastUpdate, PersonName, GroupName,  ForMembers', $sql);
        $this->assertStringContainsString('WHERE (IdGroup IS NULL AND OnlyForMembers = 0)', $sql);
        $this->assertStringContainsString('ORDER BY LastUpdate DESC', $sql);
    }

    public function testGetQueryForConnectedUserSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getQueryForConnectedUserSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM exercise_list_view', $sql);
        $this->assertStringNotContainsString('WHERE', $sql);
        $this->assertStringContainsString('ORDER BY LastUpdate DESC', $sql);
    }


    private function getQueryForAnonymousUserSql(): string
    {
        return '
            SELECT Id, CreatedBy, Title, Detail, LastUpdate, PersonName, GroupName,  ForMembers
            FROM exercise_list_view
            WHERE (IdGroup IS NULL AND OnlyForMembers = 0)
            ORDER BY LastUpdate DESC
        ';
    }

    private function getQueryForConnectedUserSql(): string
    {
        return '
            SELECT Id, CreatedBy, Title, Detail, LastUpdate, PersonName, GroupName,  ForMembers
            FROM exercise_list_view
            ORDER BY LastUpdate DESC
        ';
    }
}