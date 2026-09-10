<?php

declare(strict_types=1);

namespace tests\models;

use PDO;

class KaraokeDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'KaraokeSession', [
            'Id',
            'SessionId',
            'SongName',
            'Status',
            'CountdownStart',
            'PlayStartTime',
            'CurrentTime',
            'CreatedAt',
            'UpdatedAt',
        ]);

        $this->assertColumnsExist($pdo, 'KaraokeClient', [
            'Id',
            'ClientId',
            'IdKaraokeSession',
            'IsHost',
            'LastHeartbeat',
            'CreatedAt',
        ]);
    }

    public function testCleanupSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $deleteClient = 'DELETE FROM "KaraokeClient"';
        $deleteSession = 'DELETE FROM "KaraokeSession"';

        $this->assertInstanceOf(\PDOStatement::class, $pdo->prepare($deleteClient));
        $this->assertInstanceOf(\PDOStatement::class, $pdo->prepare($deleteSession));
    }

    public function testCleanupOldClientsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCleanupOldClientsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM "KaraokeClient"', $sql);
        $this->assertStringContainsString('"LastHeartbeat" < ?', $sql);
    }

    public function testCountActiveClientsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCountActiveClientsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM "KaraokeClient"', $sql);
        $this->assertStringContainsString('"IdKaraokeSession" = ?', $sql);
    }

    public function testDeleteSessionIfEmptySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDeleteSessionSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM "KaraokeSession"', $sql);
        $this->assertStringContainsString('"Id" = ?', $sql);
    }

    public function testDisconnectClientSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDisconnectClientSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM "KaraokeClient"', $sql);
        $this->assertStringContainsString('"ClientId" = ?', $sql);
    }

    public function testGetOrCreateSessionSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $selectSql = $this->getSelectSessionBySessionIdSql();
        $insertSql = $this->getInsertSessionSql();

        $this->assertInstanceOf(\PDOStatement::class, $pdo->prepare($selectSql));
        $this->assertInstanceOf(\PDOStatement::class, $pdo->prepare($insertSql));

        $this->assertStringContainsString('SELECT "Id" FROM "KaraokeSession"', $selectSql);
        $this->assertStringContainsString('"SessionId" = ?', $selectSql);
        $this->assertStringContainsString('INSERT INTO "KaraokeSession"', $insertSql);
        $this->assertStringContainsString('"SessionId", "SongName", "Status", "CreatedAt", "UpdatedAt"', $insertSql);
        $this->assertStringContainsString('"waiting"', $insertSql);
        $this->assertStringContainsString('datetime("now")', $insertSql);
    }

    public function testGetSessionByIdSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetSessionByIdSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT * FROM "KaraokeSession"', $sql);
        $this->assertStringContainsString('"Id" = ?', $sql);
    }

    public function testGetSessionBySessionIdSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetSessionBySessionIdSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT * FROM "KaraokeSession"', $sql);
        $this->assertStringContainsString('"SessionId" = ?', $sql);
    }

    public function testIsClientHostSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getIsClientHostSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT "IsHost" FROM "KaraokeClient"', $sql);
        $this->assertStringContainsString('"ClientId" = ?', $sql);
        $this->assertStringContainsString('"IdKaraokeSession" = ?', $sql);
    }

    public function testRegisterClientSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $selectHostSql = $this->getSelectExistingHostSql();
        $upsertSql = $this->getRegisterClientUpsertSql();

        $this->assertInstanceOf(\PDOStatement::class, $pdo->prepare($selectHostSql));
        $this->assertInstanceOf(\PDOStatement::class, $pdo->prepare($upsertSql));

        $this->assertStringContainsString('SELECT "ClientId" FROM "KaraokeClient"', $selectHostSql);
        $this->assertStringContainsString('"IsHost" = 1', $selectHostSql);
        $this->assertStringContainsString('INSERT INTO "KaraokeClient"', $upsertSql);
        $this->assertStringContainsString('ON CONFLICT("ClientId") DO UPDATE SET', $upsertSql);
        $this->assertStringContainsString('"LastHeartbeat" = datetime("now")', $upsertSql);
        $this->assertStringContainsString('excluded."IsHost"', $upsertSql);
        $this->assertStringContainsString('excluded."IdKaraokeSession"', $upsertSql);
    }

    public function testStartCountdownSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getStartCountdownSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE "KaraokeSession"', $sql);
        $this->assertStringContainsString('"Status" = "countdown"', $sql);
        $this->assertStringContainsString('"CountdownStart" = ?', $sql);
        $this->assertStringContainsString('"PlayStartTime" = ?', $sql);
        $this->assertStringContainsString('"UpdatedAt" = datetime("now")', $sql);
        $this->assertStringContainsString('"Id" = ?', $sql);
    }

    public function testUpdateHeartbeatSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateHeartbeatSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE "KaraokeClient"', $sql);
        $this->assertStringContainsString('"LastHeartbeat" = datetime("now")', $sql);
        $this->assertStringContainsString('"ClientId" = ?', $sql);
    }

    private function getCleanupOldClientsSql(): string
    {
        return 'DELETE FROM "KaraokeClient" WHERE "LastHeartbeat" < ?';
    }

    private function getCountActiveClientsSql(): string
    {
        return 'SELECT COUNT(*) FROM "KaraokeClient" WHERE "IdKaraokeSession" = ?';
    }

    private function getDeleteSessionSql(): string
    {
        return 'DELETE FROM "KaraokeSession" WHERE "Id" = ?';
    }

    private function getDisconnectClientSql(): string
    {
        return 'DELETE FROM "KaraokeClient" WHERE "ClientId" = ?';
    }

    private function getSelectSessionBySessionIdSql(): string
    {
        return 'SELECT "Id" FROM "KaraokeSession" WHERE "SessionId" = ?';
    }

    private function getInsertSessionSql(): string
    {
        return '
            INSERT INTO "KaraokeSession" ("SessionId", "SongName", "Status", "CreatedAt", "UpdatedAt")
            VALUES (?, ?, "waiting", datetime("now"), datetime("now"))
';
    }

    private function getGetSessionByIdSql(): string
    {
        return 'SELECT * FROM "KaraokeSession" WHERE "Id" = ?';
    }

    private function getGetSessionBySessionIdSql(): string
    {
        return 'SELECT * FROM "KaraokeSession" WHERE "SessionId" = ?';
    }

    private function getIsClientHostSql(): string
    {
        return '
            SELECT "IsHost" FROM "KaraokeClient" 
            WHERE "ClientId" = ? AND "IdKaraokeSession" = ?
';
    }

    private function getSelectExistingHostSql(): string
    {
        return '
                SELECT "ClientId" FROM "KaraokeClient"
                WHERE "IdKaraokeSession" = ? AND "IsHost" = 1
                LIMIT 1
';
    }

    private function getRegisterClientUpsertSql(): string
    {
        return '
                INSERT INTO "KaraokeClient" ("ClientId", "IdKaraokeSession", "IsHost", "LastHeartbeat", "CreatedAt")
                VALUES (?, ?, ?, datetime("now"), datetime("now"))
                ON CONFLICT("ClientId") DO UPDATE SET
                    "LastHeartbeat" = datetime("now"),
                    "IsHost" = excluded."IsHost",
                    "IdKaraokeSession" = excluded."IdKaraokeSession"
';
    }

    private function getStartCountdownSql(): string
    {
        return '
            UPDATE "KaraokeSession"
            SET "Status" = "countdown",
                "CountdownStart" = ?,
                "PlayStartTime" = ?,
                "UpdatedAt" = datetime("now")
            WHERE "Id" = ?
';
    }

    private function getUpdateHeartbeatSql(): string
    {
        return '
            UPDATE "KaraokeClient" 
            SET "LastHeartbeat" = datetime("now")
            WHERE "ClientId" = ?
';
    }
}
