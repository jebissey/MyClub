<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LanguagesDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Languages', [
            'Id',
            'Name',
            'en_US',
            'fr_FR',
            'pl_PL',
        ]);
    }

    public function testInitializeLanguagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getInitializeLanguagesSql();

        $stmt = $pdo->query($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('PRAGMA table_info(Languages)', $sql);
    }

    public function testTranslateSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getTranslateSql('fr_FR');

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT `fr_FR` FROM Languages', $sql);
        $this->assertStringContainsString('WHERE Name = :key', $sql);
    }

    public function testGetTranslationsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetTranslationsSql('en_US', 'fr_FR', false);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Id, Name, `en_US` AS ref_value, `fr_FR` AS target_value', $sql);
        $this->assertStringContainsString('FROM Languages', $sql);
        $this->assertStringContainsString('ORDER BY Name', $sql);
        $this->assertStringNotContainsString('WHERE', $sql);
    }

    public function testGetTranslationsMissingOnlySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetTranslationsSql('en_US', 'fr_FR', true);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Id, Name, `en_US` AS ref_value, `fr_FR` AS target_value', $sql);
        $this->assertStringContainsString("WHERE `fr_FR` = '' OR `fr_FR` IS NULL", $sql);
        $this->assertStringContainsString('ORDER BY Name', $sql);
    }

    public function testCountMissingTranslationsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCountMissingTranslationsSql('fr_FR');

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM Languages', $sql);
        $this->assertStringContainsString("WHERE `fr_FR` = '' OR `fr_FR` IS NULL", $sql);
    }

    public function testUpdateTranslationSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateTranslationSql('fr_FR');

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE Languages', $sql);
        $this->assertStringContainsString('SET `fr_FR` = :value', $sql);
        $this->assertStringContainsString('WHERE Id = :id', $sql);
    }

    private function getInitializeLanguagesSql(): string
    {
        return 'PRAGMA table_info(Languages)';
    }

    private function getTranslateSql(string $lang): string
    {
        $escaped = $this->escapeColumn($lang);
        return "SELECT $escaped FROM Languages WHERE Name = :key";
    }

    private function getGetTranslationsSql(string $referenceLang, string $targetLang, bool $missingOnly): string
    {
        $ref = $this->escapeColumn($referenceLang);
        $target = $this->escapeColumn($targetLang);

        $sql = "SELECT Id, Name, $ref AS ref_value, $target AS target_value
                FROM Languages";

        if ($missingOnly) {
            $sql .= " WHERE $target = '' OR $target IS NULL";
        }

        $sql .= " ORDER BY Name";

        return $sql;
    }

    private function getCountMissingTranslationsSql(string $targetLang): string
    {
        $target = $this->escapeColumn($targetLang);
        return "SELECT COUNT(*) 
            FROM Languages 
            WHERE $target = '' OR $target IS NULL";
    }

    private function getUpdateTranslationSql(string $targetLang): string
    {
        $target = $this->escapeColumn($targetLang);
        return "UPDATE Languages
             SET $target = :value
             WHERE Id = :id";
    }

    private function escapeColumn(string $column): string
    {
        return '`' . str_replace('`', '``', $column) . '`';
    }
}