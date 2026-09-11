<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class NeedDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Need', [
            'Id',
            'Name',
            'IdNeedType',
        ]);

        $this->assertColumnsExist($pdo, 'NeedType', [
            'Id',
            'Name',
        ]);
    }

    public function testGetNeedsAndTheirTypesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetNeedsAndTheirTypesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Need.*, NeedType.Name AS TypeName', $sql);
        $this->assertStringContainsString('FROM Need', $sql);
        $this->assertStringContainsString('LEFT JOIN NeedType ON Need.IdNeedType = NeedType.Id', $sql);
        $this->assertStringContainsString('ORDER BY NeedType.Name, Need.Name', $sql);
    }

    public function testNeedsforNeedTypeSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getNeedsforNeedTypeSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Need.*, NeedType.Name AS TypeName', $sql);
        $this->assertStringContainsString('FROM Need', $sql);
        $this->assertStringContainsString('JOIN NeedType ON Need.IdNeedType = NeedType.Id', $sql);
        $this->assertStringContainsString('WHERE Need.IdNeedType = :needTypeId', $sql);
    }

    private function getGetNeedsAndTheirTypesSql(): string
    {
        return "
            SELECT Need.*, NeedType.Name AS TypeName
            FROM Need
            LEFT JOIN NeedType ON Need.IdNeedType = NeedType.Id
            ORDER BY NeedType.Name, Need.Name
        ";
    }

    private function getNeedsforNeedTypeSql(): string
    {
        return "
            SELECT Need.*, NeedType.Name AS TypeName
            FROM Need
            JOIN NeedType ON Need.IdNeedType = NeedType.Id
            WHERE Need.IdNeedType = :needTypeId
        ";
    }
}