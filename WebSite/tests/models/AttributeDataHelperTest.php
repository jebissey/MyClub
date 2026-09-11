<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class AttributeDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Attribute', [
            'Id',
            'Name',
            'Detail',
            'Color',
        ]);

        $this->assertColumnsExist($pdo, 'EventTypeAttribute', [
            'IdAttribute',
            'IdEventType',
        ]);
    }

    public function testGetAttributesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAttributesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT *', $sql);
        $this->assertStringContainsString('FROM Attribute', $sql);
        $this->assertStringContainsString('ORDER BY Name', $sql);
    }

    public function testGetAttributesOfSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAttributesOfSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Attribute.*', $sql);
        $this->assertStringContainsString('FROM EventTypeAttribute', $sql);
        $this->assertStringContainsString('INNER JOIN Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id', $sql);
        $this->assertStringContainsString('WHERE EventTypeAttribute.IdEventType = :id', $sql);
    }

    private function getAttributesSql(): string
    {
        return '
            SELECT *
            FROM Attribute
            ORDER BY Name
';
    }

    private function getAttributesOfSql(): string
    {
        return '
            SELECT Attribute.*
            FROM EventTypeAttribute
            INNER JOIN Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id
            WHERE EventTypeAttribute.IdEventType = :id
';
    }
}