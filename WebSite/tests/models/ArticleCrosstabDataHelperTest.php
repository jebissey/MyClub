<?php

declare(strict_types=1);

namespace Tests\models;

use PDO;
use PHPUnit\Framework\TestCase;
use app\helpers\Application;
use app\models\ArticleCrosstabDataHelper;
use app\models\CrosstabDataHelper;

/**
 * NOTE: getItems() delegates entirely to CrosstabDataHelper::generateCrosstab(),
 * so behavioural coverage is limited to verifying the SQL/params/labels handed
 * off to that collaborator. The schema-level tests below additionally guard
 * against the raw SQL drifting out of sync with the real database structure
 * (renamed/removed columns, typos in JOIN conditions, etc.) by running it
 * against the shipped SQLite template.
 */
final class ArticleCrosstabDataHelperTest extends TestCase
{
    private const DB_PATH = __DIR__ . '/../../app/models/database/MyClub.sqlite';

    private function makeHelper(?CrosstabDataHelper $crosstabDataHelper = null): ArticleCrosstabDataHelper
    {
        return new ArticleCrosstabDataHelper(
            $this->createStub(Application::class),
            $crosstabDataHelper ?? $this->createStub(CrosstabDataHelper::class),
        );
    }

    // --- getItems() delegation ---

    public function testGetItemsPassesDateRangeAsBoundParameters(): void
    {
        $crosstabDataHelper = $this->createMock(CrosstabDataHelper::class);
        $crosstabDataHelper->expects($this->once())
            ->method('generateCrosstab')
            ->with(
                $this->callback('is_string'),
                [':start' => '2026-01-01', ':end' => '2026-01-31'],
                'Audience',
                'Rédacteurs',
            )
            ->willReturn(['some' => 'result']);

        $helper = $this->makeHelper($crosstabDataHelper);

        $result = $helper->getItems(['start' => '2026-01-01', 'end' => '2026-01-31']);

        $this->assertSame(['some' => 'result'], $result);
    }

    public function testGetItemsReturnsExactlyWhatGenerateCrosstabReturns(): void
    {
        $crosstabDataHelper = $this->createStub(CrosstabDataHelper::class);
        $crosstabDataHelper->method('generateCrosstab')->willReturn([['a' => 1], ['a' => 2]]);

        $helper = $this->makeHelper($crosstabDataHelper);

        $this->assertSame(
            [['a' => 1], ['a' => 2]],
            $helper->getItems(['start' => '2026-01-01', 'end' => '2026-12-31']),
        );
    }

    public function testGetItemsBuildsSqlReferencingExpectedTablesAndJoin(): void
    {
        /** @var string|null $capturedSql */
        $capturedSql = null;

        $crosstabDataHelper = $this->createStub(CrosstabDataHelper::class);
        $crosstabDataHelper->method('generateCrosstab')
            ->willReturnCallback(function (string $sql) use (&$capturedSql): array {
                $capturedSql = $sql;
                return [];
            });

        $this->makeHelper($crosstabDataHelper)->getItems(['start' => '2026-01-01', 'end' => '2026-01-31']);

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('FROM Person p', $capturedSql);
        $this->assertStringContainsString('JOIN Article a ON p.Id = a.CreatedBy', $capturedSql);
        $this->assertStringContainsString('LEFT JOIN "Group" g ON g.Id = a.IdGroup', $capturedSql);
        $this->assertStringContainsString('a.PublishedBy IS NOT NULL', $capturedSql);
    }

    // --- schema validation: the columns/tables referenced by the SQL actually exist ---

    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();

        $this->assertColumnsExist($pdo, 'Person', ['Id', 'FirstName', 'LastName', 'NickName']);
        $this->assertColumnsExist($pdo, 'Article', ['CreatedBy', 'IdGroup', 'LastUpdate', 'PublishedBy']);
        $this->assertColumnsExist($pdo, 'Group', ['Id', 'Name']);
    }

    public function testGeneratedSqlExecutesSuccessfullyAgainstTemplateSchema(): void
    {
        /** @var string|null $capturedSql */
        $capturedSql = null;

        $crosstabDataHelper = $this->createStub(CrosstabDataHelper::class);
        $crosstabDataHelper->method('generateCrosstab')
            ->willReturnCallback(function (string $sql) use (&$capturedSql): array {
                $capturedSql = $sql;
                return [];
            });

        $this->makeHelper($crosstabDataHelper)->getItems([
            'start' => '2000-01-01',
            'end'   => '2100-01-01',
        ]);

        $this->assertNotNull($capturedSql, 'getItems() should build a SQL string before delegating.');

        $pdo  = $this->openTemplateDatabaseOrSkip();
        $stmt = $pdo->prepare($capturedSql);
        $stmt->bindValue(':start', '2000-01-01');
        $stmt->bindValue(':end', '2100-01-01');

        // On SQLite, an unknown table/column name makes prepare()/execute()
        // throw rather than silently returning no rows, so this test fails
        // fast the moment the query drifts out of sync with the real schema.
        $this->assertTrue(
            $stmt->execute(),
            'The SQL built by getItems() failed to execute against the MyClub.sqlite template schema.'
        );
    }

    /** @param array<int, string> $expectedColumns */
    private function assertColumnsExist(PDO $pdo, string $table, array $expectedColumns): void
    {
        $stmt = $pdo->query(sprintf('PRAGMA table_info("%s")', $table));
        $this->assertNotFalse($stmt, "Could not read schema for table '{$table}'.");

        $actualColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        $this->assertNotEmpty($actualColumns, "Table '{$table}' does not exist in the template database.");

        foreach ($expectedColumns as $column) {
            $this->assertContains(
                $column,
                $actualColumns,
                "Expected column '{$table}.{$column}' not found in the template database schema."
            );
        }
    }

    private function openTemplateDatabaseOrSkip(): PDO
    {
        if (!file_exists(self::DB_PATH)) {
            $this->markTestSkipped('Template database not found at ' . self::DB_PATH);
        }

        return new PDO('sqlite:' . self::DB_PATH);
    }
}