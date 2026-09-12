<?php

declare(strict_types=1);

namespace tests\models;

use Envms\FluentPDO\Query;
use PDO;
use RuntimeException;
use app\enums\FilterInputRule;
use app\exceptions\SqliteTableException;
use app\models\DbBrowserDataHelper;

class DbBrowserDataHelperTest extends DataHelperTestCase
{
    protected function tearDown(): void
    {
        unset($_POST);
        parent::tearDown();
    }

    private function makeDbBrowserHelper(PDO $pdo): DbBrowserDataHelper
    {
        /** @var DbBrowserDataHelper $helper */
        $helper = $this->makeHelper(DbBrowserDataHelper::class, $pdo);
        // makeHelper() ne câble que pdo/tables (communs à Data) ;
        // getQuery() a en plus besoin de $fluent, propre à Data.
        $this->setProperty($helper, 'fluent', new Query($pdo));

        return $helper;
    }

    // -------------------------------------------------------------------------
    // validateTableName() (comportement réel, pas de mirroring : c'est ici,
    // via des noms de table fournis par le webmaster, que sa validation
    // compte vraiment)
    // -------------------------------------------------------------------------

    public function testRejectsTableNameLongerThan64Characters(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $this->expectException(SqliteTableException::class);
        $helper->getTableColumns(str_repeat('a', 65));
    }

    public function testRejectsTableNameWithInvalidCharacters(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $this->expectException(SqliteTableException::class);
        $helper->getTableColumns('Person; DROP TABLE Person;--');
    }

    public function testRejectsTableNameNotInSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $this->expectException(SqliteTableException::class);
        $helper->getTableColumns('TableThatDoesNotExist');
    }

    public function testAcceptsARealTableName(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $columns = $helper->getTableColumns('Person');

        $this->assertContains('Id', $columns);
    }

    // -------------------------------------------------------------------------
    // Introspection: getTableColumns / getTableColumnsDetails / getColumnTypes
    // / getPrimaryKey / showCreateForm
    // -------------------------------------------------------------------------

    public function testGetTableColumnsReturnsRealSchemaColumns(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $columns = $helper->getTableColumns('Person');

        $this->assertContains('Id', $columns);
        $this->assertContains('FirstName', $columns);
        $this->assertContains('LastName', $columns);
        $this->assertContains('Email', $columns);
    }

    public function testGetTableColumnsDetailsExposesNotNullFlag(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $details = $helper->getTableColumnsDetails('Person');
        $names = array_column($details, 'name');

        $this->assertContains('Id', $names);
        foreach ($details as $detail) {
            $this->assertArrayHasKey('name', $detail);
            $this->assertArrayHasKey('notnull', $detail);
        }
    }

    public function testGetColumnTypesReturnsTypeAndPrimaryKeyFlagForId(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $types = $helper->getColumnTypes('Person');

        $this->assertArrayHasKey('Id', $types);
        $this->assertArrayHasKey('type', $types['Id']);
        $this->assertArrayHasKey('dflt_value', $types['Id']);
        $this->assertSame(1, $types['Id']['pk']);
    }

    public function testGetPrimaryKeyReturnsIdForPersonTable(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $this->assertSame('Id', $helper->getPrimaryKey('Person'));
    }

    public function testShowCreateFormReturnsColumnsAndTypesTogether(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        [$columns, $types] = $helper->showCreateForm('Person');

        $this->assertContains('FirstName', $columns);
        $this->assertArrayHasKey('FirstName', $types);
    }

    // -------------------------------------------------------------------------
    // showEditForm()
    // -------------------------------------------------------------------------

    public function testShowEditFormReturnsExistingRecord(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Jean', 'Dupont', 'jean.dupont@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        [$columns, $record, $primaryKey, $types] = $helper->showEditForm('Person', $id);

        $this->assertContains('LastName', $columns);
        $this->assertSame('Dupont', $record->LastName);
        $this->assertSame('Id', $primaryKey);
        $this->assertArrayHasKey('LastName', $types);
    }

    public function testShowEditFormThrowsWhenRecordNotFound(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $this->expectException(RuntimeException::class);
        $helper->showEditForm('Person', 999999);
    }

    // -------------------------------------------------------------------------
    // createRecord / updateRecord / deleteRecord (lisent $_POST)
    // -------------------------------------------------------------------------

    public function testCreateRecordInsertsRowFromPostData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $_POST = ['FirstName' => 'Alice', 'LastName' => 'Martin', 'Email' => 'alice.martin@test.local'];
        $helper->createRecord('Person');

        $stmt = $pdo->query("SELECT FirstName FROM Person WHERE LastName = 'Martin'");
        $this->assertSame('Alice', $stmt->fetchColumn());
    }

    public function testUpdateRecordUpdatesRowFromPostData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Paul', 'Durand', 'paul.durand@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        $_POST = ['FirstName' => 'Paul-Updated', 'LastName' => 'Durand'];
        $helper->updateRecord('Person', $id);

        $stmt = $pdo->prepare('SELECT FirstName FROM Person WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $this->assertSame('Paul-Updated', $stmt->fetchColumn());
    }

    public function testUpdateRecordIgnoresPrimaryKeyFieldFromPostData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Marc', 'Petit', 'marc.petit@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        $_POST = ['Id' => $id + 1000, 'FirstName' => 'Marc-Updated', 'LastName' => 'Petit'];
        $helper->updateRecord('Person', $id);

        $stmt = $pdo->prepare('SELECT FirstName FROM Person WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $this->assertSame('Marc-Updated', $stmt->fetchColumn());
    }

    public function testDeleteRecordRemovesRow(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('ToDelete', 'Person', 'todelete.person@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        $helper->deleteRecord('Person', $id);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Person WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    // -------------------------------------------------------------------------
    // generateFilterConfig / generateFilterSchema
    // -------------------------------------------------------------------------

    public function testGenerateFilterConfigListsAllColumnsAsNameLabelPairs(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $config = $helper->generateFilterConfig('Person');
        $names = array_column($config, 'name');

        $this->assertContains('Email', $names);
        foreach ($config as $entry) {
            $this->assertSame($entry['name'], $entry['label']);
        }
    }

    public function testGenerateFilterSchemaMapsIntegerColumnToIntRule(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $schema = $helper->generateFilterSchema('Person');

        $this->assertSame(FilterInputRule::Int->value, $schema['Id']);
    }

    public function testGenerateFilterSchemaMapsTextColumnToContentRule(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $schema = $helper->generateFilterSchema('Person');

        $this->assertSame(FilterInputRule::Content->value, $schema['FirstName']);
    }

    // -------------------------------------------------------------------------
    // showTable()
    // -------------------------------------------------------------------------

    public function testShowTableReturnsOnePageOfRowsWithoutFilters(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('A', 'Un', 'a.un@test.local')");
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('B', 'Deux', 'b.deux@test.local')");
        $helper = $this->makeDbBrowserHelper($pdo);

        [$rows, $columns, $page, $totalPages, $filters] = $helper->showTable('Person', 1, [], 1);

        $this->assertCount(1, $rows);
        $this->assertContains('FirstName', $columns);
        $this->assertSame(1, $page);
        $this->assertGreaterThanOrEqual(2, $totalPages);
        $this->assertSame([], $filters);
    }

    public function testShowTableAppliesColumnFilter(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Zoe', 'UniqueFilteredLastName', 'zoe.filtered@test.local')");
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Zoe', 'SomeoneElse', 'zoe.other@test.local')");
        $helper = $this->makeDbBrowserHelper($pdo);

        [$rows] = $helper->showTable('Person', 10, ['LastName' => 'UniqueFilteredLastName'], 1);

        $this->assertCount(1, $rows);
        $this->assertSame('UniqueFilteredLastName', $rows[0]->LastName);
    }

    // -------------------------------------------------------------------------
    // getQuery()
    // -------------------------------------------------------------------------

    public function testGetQueryBuildsSelectFromRequestedTable(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $select = $helper->getQuery('Person');

        $this->assertStringContainsString('Person', (string) $select);
    }
}