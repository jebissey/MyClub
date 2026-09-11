<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class ArwardsDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'FirstName',
            'LastName',
            'NickName',
        ]);

        $this->assertColumnsExist($pdo, 'Counter', [
            'IdPerson',
            'Name',
            'Value',
        ]);
    }

    public function testGetDataSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDataSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('p.Id,', $sql);
        $this->assertStringContainsString('p.FirstName,', $sql);
        $this->assertStringContainsString('p.LastName,', $sql);
        $this->assertStringContainsString('p.NickName,', $sql);
        $this->assertStringContainsString('c.Name as CounterName,', $sql);
        $this->assertStringContainsString('SUM(c.Value) as CounterValue,', $sql);
        $this->assertStringContainsString('(SELECT SUM(Value) FROM Counter WHERE IdPerson = p.Id) as Total', $sql);
        $this->assertStringContainsString('FROM Person p', $sql);
        $this->assertStringContainsString('LEFT JOIN Counter c ON p.Id = c.IdPerson', $sql);
        $this->assertStringContainsString('GROUP BY p.Id, p.FirstName, p.LastName, p.NickName, c.Name', $sql);
        $this->assertStringContainsString('HAVING Total > 0', $sql);
        $this->assertStringContainsString('ORDER BY Total DESC', $sql);
    }

    public function testGetCounterNamesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCounterNamesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT DISTINCT Name FROM Counter ORDER BY Name', $sql);
    }

    private function getDataSql(): string
    {
        return '
            SELECT 
                p.Id, 
                p.FirstName, 
                p.LastName, 
                p.NickName, 
                c.Name as CounterName, 
                SUM(c.Value) as CounterValue, 
                (SELECT SUM(Value) FROM Counter WHERE IdPerson = p.Id) as Total
            FROM Person p
            LEFT JOIN Counter c ON p.Id = c.IdPerson
            GROUP BY p.Id, p.FirstName, p.LastName, p.NickName, c.Name
            HAVING Total > 0
            ORDER BY Total DESC';
    }

    private function getCounterNamesSql(): string
    {
        return 'SELECT DISTINCT Name FROM Counter ORDER BY Name';
    }
}