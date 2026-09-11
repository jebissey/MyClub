<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LogCompactDataHelperTest extends DataHelperTestCase
{
    private const LOG_DB_PATH = __DIR__ . '/../../app/models/database/LogMyClub.sqlite';

    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $logPdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);

        $this->assertColumnsExist($logPdo, 'Log', [
            'Id',
            'IpAddress',
            'Referer',
            'Os',
            'Browser',
            'ScreenResolution',
            'Type',
            'Uri',
            'Token',
            'Who',
            'CreatedAt',
            'Code',
            'Message',
            'Count',
            'Duration',
        ]);

        $mainPdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($mainPdo, 'Metadata', [
            'Id',
            'Compact_lastDate',
            'Compact_everyXdays',
            'Compact_maxRecords',
        ]);
    }

    public function testCountLogRowsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getCountLogRowsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*) FROM Log', $sql);
    }

    public function testDeleteOldRowsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getDeleteOldRowsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM Log', $sql);
        $this->assertStringContainsString("WHERE CreatedAt < datetime('now', ?)", $sql);
    }

    public function testCompactInsertSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getCompactInsertSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO Log (', $sql);
        $this->assertStringContainsString(
            'IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, ',
            $sql,
        );
        $this->assertStringContainsString('Token, Who, CreatedAt, Code, Message, Count, Duration', $sql);
        $this->assertStringContainsString("datetime(CreatedAt, 'start of month') AS CompactedDate", $sql);
        $this->assertStringContainsString('SUM(Count) AS TotalCount', $sql);
        $this->assertStringContainsString('ROUND(AVG(Duration), 2) AS AvgDuration', $sql);
        $this->assertStringContainsString("WHERE CreatedAt < datetime('now', ?)", $sql);
        $this->assertStringContainsString("GROUP BY Uri, Who, strftime('%Y-%m', CreatedAt)", $sql);
        $this->assertStringContainsString('HAVING SUM(Count) > 0', $sql);
    }

    public function testCompactDeleteSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getCompactDeleteSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM Log', $sql);
        $this->assertStringContainsString("WHERE CreatedAt < datetime('now', ?)", $sql);
        $this->assertStringContainsString("AND NOT ({$this->getEmptyCondition()})", $sql);
    }

    public function testEnforceMaxRecordsDeleteSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getEnforceMaxRecordsDeleteSql(10);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM Log', $sql);
        $this->assertStringContainsString('WHERE Id IN (', $sql);
        $this->assertStringContainsString('SELECT Id FROM Log', $sql);
        $this->assertStringContainsString('ORDER BY datetime(CreatedAt) ASC', $sql);
        $this->assertStringContainsString('LIMIT 10', $sql);
    }

    private function getEmptyCondition(): string
    {
        return "IpAddress = '' AND Referer = '' AND Os = '' AND Browser = '' 
                          AND ScreenResolution = '' AND Type = '' AND Token = '' 
                          AND Code = '' AND Message = ''";
    }

    private function getCountLogRowsSql(): string
    {
        return 'SELECT COUNT(*) FROM Log';
    }

    private function getDeleteOldRowsSql(): string
    {
        return "
            DELETE FROM Log 
            WHERE CreatedAt < datetime('now', ?)
        ";
    }

    private function getCompactInsertSql(): string
    {
        $emptyCondition = $this->getEmptyCondition();

        return "
            INSERT INTO Log (
                IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, 
                Token, Who, CreatedAt, Code, Message, Count, Duration
            )
            SELECT 
                '', '', '', '', '', '',
                Uri,
                '',
                Who,
                datetime(CreatedAt, 'start of month') AS CompactedDate,
                '', '',
                SUM(Count) AS TotalCount,
                ROUND(AVG(Duration), 2) AS AvgDuration
            FROM Log
            WHERE CreatedAt < datetime('now', ?)
            AND NOT ($emptyCondition)
            GROUP BY Uri, Who, strftime('%Y-%m', CreatedAt)
            HAVING SUM(Count) > 0;
        ";
    }

    private function getCompactDeleteSql(): string
    {
        $emptyCondition = $this->getEmptyCondition();

        return "
            DELETE FROM Log 
            WHERE CreatedAt < datetime('now', ?)
            AND NOT ($emptyCondition)
        ";
    }

    private function getEnforceMaxRecordsDeleteSql(int $toDelete): string
    {
        return "
            DELETE FROM Log
            WHERE Id IN (
                SELECT Id FROM Log
                ORDER BY datetime(CreatedAt) ASC
                LIMIT $toDelete
            )
        ";
    }
}