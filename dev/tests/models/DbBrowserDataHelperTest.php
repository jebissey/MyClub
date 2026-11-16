<?php

declare(strict_types=1);

namespace tests\models;

use Envms\FluentPDO\Query;
use PDO;
use PDOStatement;
use RuntimeException;
use app\enums\FilterInputRule;
use app\models\DbBrowserDataHelper;

class DbBrowserDataHelperTest extends DataHelperTestCase
{
    private function makeDbBrowserHelper(PDO $pdo): DbBrowserDataHelper
    {
        /** @var DbBrowserDataHelper $helper */
        $helper = $this->makeHelper(DbBrowserDataHelper::class, $pdo);
        // makeHelper() only wires pdo/tables (common to Data);
        // getQuery() also needs $fluent, which is specific to Data.
        $this->setProperty($helper, 'fluent', new Query($pdo));

        return $helper;
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
    // createRecord / updateRecord / deleteRecord (form data is passed as an argument)
    // -------------------------------------------------------------------------

    public function testCreateRecordInsertsRowFromFormData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $helper->createRecord('Individual', [
            'Type' => 'Member',
            'FirstName' => 'Alice',
            'LastName' => 'Martin',
            'Email' => 'alice.martin@test.local',
        ]);

        $stmt = $pdo->query(
            "SELECT FirstName FROM Individual WHERE LastName = 'Martin'",
        );
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $this->assertSame('Alice', $stmt->fetchColumn());
    }

    public function testCreateRecordIgnoresNonScalarValues(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDbBrowserHelper($pdo);

        $helper->createRecord('Individual', [
            'Type' => 'Member',
            'FirstName' => 'Scalar',
            'LastName' => 'Checked',
            'Email' => 'scalar.checked@test.local',
            'NickName' => ['injected'],
        ]);

        $stmt = $pdo->query("SELECT NickName FROM Individual WHERE LastName = 'Checked'");
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($row);
        $this->assertNotSame('Array', $row['NickName']);
    }

    public function testUpdateRecordUpdatesRowFromFormData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'Paul', 'Durand', 'paul.durand@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        $helper->updateRecord('Individual', $id, [
            'FirstName' => 'Paul-Updated',
            'LastName' => 'Durand',
        ]);

        $stmt = $pdo->prepare('SELECT FirstName FROM Individual WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $this->assertSame('Paul-Updated', $stmt->fetchColumn());
    }

    public function testUpdateRecordIgnoresPrimaryKeyFieldFromFormData(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Individual (Type, FirstName, LastName, Email) VALUES ('Member', 'Marc', 'Petit', 'marc.petit@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDbBrowserHelper($pdo);

        $helper->updateRecord('Individual', $id, [
            'Id' => $id + 1000,
            'FirstName' => 'Marc-Updated',
            'LastName' => 'Petit',
        ]);

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
