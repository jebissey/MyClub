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
        $helper->getTableColumns('Individual; DROP TABLE Individual;--');
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

        $columns = $helper->getTableColumns('Individual');

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

        $columns = $helper->getTableColumns('Individual');

        $this->assertContains('Id', $columns);
        $this->assertContains('FirstName', $columns);
        $this->assertContains('LastName', $columns);
        $this->assertContains('Email', $columns);
    }

    public function testGetTableColumnsDetailsExposesNotNullFlag(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $details = $helper->getTableColumnsDetails('Individual');
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

        $types = $helper->getColumnTypes('Individual');

        $this->assertArrayHasKey('Id', $types);
        $this->assertArrayHasKey('type', $types['Id']);
        $this->assertArrayHasKey('dflt_value', $types['Id']);
        $this->assertSame(1, $types['Id']['pk']);
    }

    public function testGetPrimaryKeyReturnsIdForIndividualTable(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $this->assertSame('Id', $helper->getPrimaryKey('Individual'));
    }

    public function testShowCreateFormReturnsColumnsAndTypesTogether(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        [$columns, $types] = $helper->showCreateForm('Individual');

        $this->assertContains('FirstName', $columns);
        $this->assertArrayHasKey('FirstName', $types);
    }

    // -------------------------------------------------------------------------
    // showEditForm()
    // -------------------------------------------------------------------------

    public function testShowEditFormReturnsExistingRecord(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'Jean', 'Dupont', 'jean.dupont@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        [$columns, $record, $primaryKey, $types] = $helper->showEditForm('Individual', $id);

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
        $helper->showEditForm('Individual', 999999);
    }

    // -------------------------------------------------------------------------
    // createRecord / updateRecord / deleteRecord (lisent $_POST)
    // -------------------------------------------------------------------------

    public function testCreateRecordInsertsRowFromPostData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $_POST = ['Type' => 'Member', 'FirstName' => 'Alice', 'LastName' => 'Martin', 'Email' => 'alice.martin@test.local'];
        $helper->createRecord('Individual');

        $stmt = $pdo->query("SELECT FirstName FROM Individual WHERE LastName = 'Martin'");
        $this->assertSame('Alice', $stmt->fetchColumn());
    }

    public function testUpdateRecordUpdatesRowFromPostData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'Paul', 'Durand', 'paul.durand@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        $_POST = ['FirstName' => 'Paul-Updated', 'LastName' => 'Durand'];
        $helper->updateRecord('Individual', $id);

        $stmt = $pdo->prepare('SELECT FirstName FROM Individual WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $this->assertSame('Paul-Updated', $stmt->fetchColumn());
    }

    public function testUpdateRecordIgnoresPrimaryKeyFieldFromPostData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'Marc', 'Petit', 'marc.petit@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        $_POST = ['Id' => $id + 1000, 'FirstName' => 'Marc-Updated', 'LastName' => 'Petit'];
        $helper->updateRecord('Individual', $id);

        $stmt = $pdo->prepare('SELECT FirstName FROM Individual WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $this->assertSame('Marc-Updated', $stmt->fetchColumn());
    }

    public function testDeleteRecordRemovesRow(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'ToDelete', 'Person', 'todelete.person@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        $helper->deleteRecord('Individual', $id);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Individual WHERE Id = :id');
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

        $config = $helper->generateFilterConfig('Individual');
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

        $schema = $helper->generateFilterSchema('Individual');

        $this->assertSame(FilterInputRule::Int->value, $schema['Id']);
    }

    public function testGenerateFilterSchemaMapsTextColumnToContentRule(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $schema = $helper->generateFilterSchema('Individual');

        $this->assertSame(FilterInputRule::Content->value, $schema['FirstName']);
    }

    // -------------------------------------------------------------------------
    // showTable()
    // -------------------------------------------------------------------------

    public function testShowTableReturnsOnePageOfRowsWithoutFilters(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'A', 'Un', 'a.un@test.local')");
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'B', 'Deux', 'b.deux@test.local')");
        $helper = $this->makeDbBrowserHelper($pdo);

        [$rows, $columns, $page, $totalPages, $filters] = $helper->showTable('Individual', 1, [], 1);

        $this->assertCount(1, $rows);
        $this->assertContains('FirstName', $columns);
        $this->assertSame(1, $page);
        $this->assertGreaterThanOrEqual(2, $totalPages);
        $this->assertSame([], $filters);
    }

    public function testShowTableAppliesColumnFilter(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'Zoe', 'UniqueFilteredLastName', 'zoe.filtered@test.local')");
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'Zoe', 'SomeoneElse', 'zoe.other@test.local')");
        $helper = $this->makeDbBrowserHelper($pdo);

        [$rows] = $helper->showTable('Individual', 10, ['LastName' => 'UniqueFilteredLastName'], 1);

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

        $select = $helper->getQuery('Individual');

        $this->assertStringContainsString('Individual', (string) $select);
    }
}