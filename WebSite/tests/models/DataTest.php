<?php

declare(strict_types=1);

namespace tests\models;

use InvalidArgumentException;
use PDO;
use PDOException;
use app\helpers\Application;
use app\helpers\ErrorManager;
use app\models\DataHelper;

class DataTest extends DataHelperTestCase
{
    private function makeDataHelper(PDO $pdo): DataHelper
    {
        /** @var DataHelper $helper */
        $helper = $this->makeHelper(DataHelper::class, $pdo);

        return $helper;
    }

    /**
     * Comme makeDataHelper(), mais câble aussi $application avec un stub
     * dont getErrorManager() renvoie un stub d'ErrorManager (raise() est
     * une méthode void non configurée : le stub ne fait rien et ne
     * déclenche ni rendu Latte, ni header(), ni exit()). Nécessaire pour
     * exercer les blocs catch (PDOException $e) de Data, qui appellent
     * tous $this->application->getErrorManager()->raise(...) avant de
     * relever l'exception.
     */
    private function makeDataHelperWithStubbedErrorManager(PDO $pdo): DataHelper
    {
        $helper = $this->makeDataHelper($pdo);

        $errorManagerStub = $this->createStub(ErrorManager::class);
        $applicationStub = $this->createStub(Application::class);
        $applicationStub->method('getErrorManager')->willReturn($errorManagerStub);

        $this->setProperty($helper, 'application', $applicationStub);

        return $helper;
    }

    // -------------------------------------------------------------------------
    // get()
    // -------------------------------------------------------------------------

    public function testGetReturnsMatchingRow(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Jean', 'Dupont', 'jean.dupont@test.local')");
        $helper = $this->makeDataHelper($pdo);

        $record = $helper->get('Person', ['LastName' => 'Dupont']);

        $this->assertIsObject($record);
        $this->assertSame('Jean', $record->FirstName);
    }

    public function testGetReturnsFalseWhenNoRowMatches(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelper($pdo);

        $this->assertFalse($helper->get('Person', ['LastName' => 'NoSuchLastName']));
    }

    public function testGetEmailWhereIsCaseInsensitive(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('John', 'Doe', 'John.Doe@Example.com')");
        $helper = $this->makeDataHelper($pdo);

        $record = $helper->get('Person', ['Email' => 'john.doe@example.com']);

        $this->assertIsObject($record);
        $this->assertSame('John', $record->FirstName);
    }

    // -------------------------------------------------------------------------
    // gets()
    // -------------------------------------------------------------------------

    public function testGetsReturnsAllRowsMatchingWhere(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('A', 'SameLastName', 'a@test.local')");
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('B', 'SameLastName', 'b@test.local')");
        $helper = $this->makeDataHelper($pdo);

        $rows = $helper->gets('Person', ['LastName' => 'SameLastName']);

        $this->assertCount(2, $rows);
    }

    public function testGetsAppliesOrderBy(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('X', 'Zed', 'x@test.local')");
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Y', 'Abel', 'y@test.local')");
        $helper = $this->makeDataHelper($pdo);

        // ORDER BY doit produire un résultat trié sur l'ensemble des lignes,
        // quel que soit le contenu déjà présent dans le template.
        $rows = $helper->gets('Person', [], 'LastName', 'LastName');

        $lastNames = array_column($rows, 'LastName');
        $sorted = $lastNames;
        sort($sorted);
        $this->assertSame($sorted, $lastNames);
    }

    public function testGetsWithKeyPairReturnsArrayKeyedByFirstField(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Kim', 'Keyed', 'kim@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDataHelper($pdo);

        $rows = $helper->gets('Person', ['LastName' => 'Keyed'], 'Id, FirstName', '', true);

        $this->assertArrayHasKey($id, $rows);
        $this->assertSame('Kim', $rows[$id]->FirstName);
    }

    public function testGetsWithKeyPairAndWildcardFieldsThrows(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelper($pdo);

        $this->expectException(InvalidArgumentException::class);
        $helper->gets('Person', [], '*', '', true);
    }

    // -------------------------------------------------------------------------
    // set()
    // -------------------------------------------------------------------------

    public function testSetWithoutWhereInsertsRowAndReturnsNewId(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelper($pdo);

        $newId = $helper->set('Person', [
            'FirstName' => 'Ines',
            'LastName'  => 'Inserted',
            'Email'     => 'ines.inserted@test.local',
        ]);

        $this->assertIsInt($newId);
        $stmt = $pdo->prepare('SELECT FirstName FROM Person WHERE Id = :id');
        $stmt->execute([':id' => $newId]);
        $this->assertSame('Ines', $stmt->fetchColumn());
    }

    public function testSetWithWhereUpdatesExistingRow(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Old', 'Name', 'old.name@test.local')");
        $id = (int) $pdo->lastInsertId();
        $helper = $this->makeDataHelper($pdo);

        $result = $helper->set('Person', ['FirstName' => 'New'], ['Id' => $id]);

        $this->assertNotFalse($result);
        $stmt = $pdo->prepare('SELECT FirstName FROM Person WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $this->assertSame('New', $stmt->fetchColumn());
    }

    // -------------------------------------------------------------------------
    // last()
    // -------------------------------------------------------------------------

    public function testLastReturnsMostRecentlyInsertedMatchingRow(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('First', 'Chrono', 'first.chrono@test.local')");
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Second', 'Chrono', 'second.chrono@test.local')");
        $helper = $this->makeDataHelper($pdo);

        $record = $helper->last('Person', ['LastName' => 'Chrono']);

        $this->assertIsObject($record);
        $this->assertSame('Second', $record->FirstName);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function testDeleteWithWhereRemovesMatchingRowsAndReturnsCount(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('ToDelete', 'A', 'todelete.a@test.local')");
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('ToDelete', 'B', 'todelete.b@test.local')");
        $helper = $this->makeDataHelper($pdo);

        $deleted = $helper->delete('Person', ['FirstName' => 'ToDelete']);

        $this->assertSame(2, $deleted);
        $stmt = $pdo->query("SELECT COUNT(*) FROM Person WHERE FirstName = 'ToDelete'");
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    // Note : delete() avec un $where vide lève une PDOException gérée via
    // $this->application->getErrorManager()->raise(...) avant d'être
    // re-levée. Testé ci-dessous via makeDataHelperWithStubbedErrorManager(),
    // qui neutralise ErrorManager::raise() (rendu Latte, header(), exit()).

    public function testDeleteWithEmptyWhereThrowsAndReportsPDOException(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelperWithStubbedErrorManager($pdo);

        $this->expectException(PDOException::class);
        $helper->delete('Person', []);
    }

    public function testGetWithUnknownWhereColumnThrowsAndReportsPDOException(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelperWithStubbedErrorManager($pdo);

        $this->expectException(PDOException::class);
        $helper->get('Person', ['ThisColumnDoesNotExist' => 'x']);
    }

    // -------------------------------------------------------------------------
    // query()
    // -------------------------------------------------------------------------

    public function testQueryExecutesRawSelectAndReturnsObjects(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $pdo->exec("INSERT INTO Person (FirstName, LastName, Email) VALUES ('Raw', 'Query', 'raw.query@test.local')");
        $helper = $this->makeDataHelper($pdo);

        $result = $helper->query('SELECT FirstName FROM Person WHERE LastName = ?', ['Query']);

        $this->assertIsArray($result);
        $this->assertSame('Raw', $result[0]->FirstName);
    }

    public function testQueryWithInvalidSqlReturnsFalseAndReportsError(): void
    {
        // query() ne relève pas l'exception : elle est avalée et query()
        // renvoie false, contrairement à get()/gets()/set()/delete()/last().
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelperWithStubbedErrorManager($pdo);

        $result = $helper->query('SELECT * FROM ThisTableDoesNotExist');

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // getSetting() / setSetting()
    // -------------------------------------------------------------------------

    public function testGetSettingReturnsDefaultWhenSettingIsMissing(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelper($pdo);

        $this->assertSame('fallback', $helper->getSetting('Test_Setting_Missing_XYZ', 'fallback'));
    }

    public function testSetSettingThenGetSettingRoundTrips(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelper($pdo);

        $helper->setSetting('Test_Setting_XYZ', 'Hello');

        $this->assertSame('Hello', $helper->getSetting('Test_Setting_XYZ', 'fallback'));
    }

    public function testSetSettingUpdatesExistingSetting(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelper($pdo);

        $helper->setSetting('Test_Setting_XYZ', 'First');
        $helper->setSetting('Test_Setting_XYZ', 'Second');

        $this->assertSame('Second', $helper->getSetting('Test_Setting_XYZ', 'fallback'));
    }

    // -------------------------------------------------------------------------
    // requireFields() (méthode statique)
    // -------------------------------------------------------------------------

    public function testRequireFieldsReturnsTrueWhenAllFieldsPresent(): void
    {
        $this->assertTrue(DataHelper::requireFields(
            ['FirstName' => 'A', 'LastName' => 'B'],
            ['FirstName', 'LastName'],
        ));
    }

    public function testRequireFieldsReturnsFalseWhenAFieldIsMissing(): void
    {
        $this->assertFalse(DataHelper::requireFields(
            ['FirstName' => 'A'],
            ['FirstName', 'LastName'],
        ));
    }

    // -------------------------------------------------------------------------
    // getTables()
    // -------------------------------------------------------------------------

    public function testGetTablesListsRealSchemaTables(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $helper = $this->makeDataHelper($pdo);

        $tables = $helper->getTables();

        $this->assertContains('Person', $tables);
    }
}