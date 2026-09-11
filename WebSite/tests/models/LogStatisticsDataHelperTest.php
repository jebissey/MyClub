<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LogStatisticsDataHelperTest extends DataHelperTestCase
{
    private const LOG_DB_PATH = __DIR__ . '/../../app/models/database/LogMyClub.sqlite';

    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);

        $this->assertColumnsExist($pdo, 'Log', [
            'Os',
            'Browser',
            'ScreenResolution',
            'Type',
            'CreatedAt',
        ]);
    }

    public function testGetOsDistributionSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getOsDistributionSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Os, COUNT(*) AS count', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE CreatedAt BETWEEN :startDate AND :endDate', $sql);
        $this->assertStringContainsString('GROUP BY Os', $sql);
        $this->assertStringContainsString('ORDER BY count DESC', $sql);
    }

    public function testGetBrowserDistributionSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getBrowserDistributionSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('WITH RECURSIVE', $sql);
        $this->assertStringContainsString('split(id, browser, word, rest, position) AS', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE CreatedAt BETWEEN :startDate AND :endDate', $sql);
        $this->assertStringContainsString('UNION ALL', $sql);
        $this->assertStringContainsString("NOT GLOB '[0-9]*'", $sql);
        $this->assertStringContainsString('SELECT word AS Browser, COUNT(*) as count', $sql);
        $this->assertStringContainsString('FROM split', $sql);
        $this->assertStringContainsString('GROUP BY word', $sql);
        $this->assertStringContainsString('ORDER BY count DESC', $sql);
    }

    public function testGetScreenResolutionDistributionSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getScreenResolutionDistributionSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT ScreenResolution, COUNT(*) AS count', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE CreatedAt BETWEEN :startDate AND :endDate', $sql);
        $this->assertStringContainsString('GROUP BY ScreenResolution', $sql);
        $this->assertStringContainsString('ORDER BY count DESC', $sql);
    }

    public function testGetTypeDistributionSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getTypeDistributionSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Type, COUNT(*) AS count', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE CreatedAt BETWEEN :startDate AND :endDate', $sql);
        $this->assertStringContainsString('GROUP BY Type', $sql);
        $this->assertStringContainsString('ORDER BY count DESC', $sql);
    }

    private function getOsDistributionSql(): string
    {
        return '
            SELECT Os, COUNT(*) AS count
            FROM Log
            WHERE CreatedAt BETWEEN :startDate AND :endDate
            GROUP BY Os
            ORDER BY count DESC
        ';
    }

    private function getBrowserDistributionSql(): string
    {
        return "
            WITH RECURSIVE
            split(id, browser, word, rest, position) AS (
                SELECT 
                    rowid,
                    Browser,
                    '',
                    Browser || ' ',
                    1
                FROM Log
                WHERE CreatedAt BETWEEN :startDate AND :endDate

                UNION ALL

                SELECT 
                    id,
                    browser,
                    CASE 
                        WHEN word = '' 
                            THEN SUBSTR(rest, 0, INSTR(rest, ' '))
                        ELSE word || ' ' || SUBSTR(rest, 0, INSTR(rest, ' '))
                    END,
                    LTRIM(SUBSTR(rest, INSTR(rest, ' '))),
                    position + 1
                FROM split
                WHERE rest != ''
                AND SUBSTR(rest, 0, INSTR(rest, ' ')) NOT GLOB '[0-9]*'
            )

            SELECT word AS Browser, COUNT(*) as count
            FROM split
            WHERE rest = ''
            OR SUBSTR(rest, 0, INSTR(rest, ' ')) GLOB '[0-9]*'
            GROUP BY word
            ORDER BY count DESC";
    }

    private function getScreenResolutionDistributionSql(): string
    {
        return '
            SELECT ScreenResolution, COUNT(*) AS count
            FROM Log
            WHERE CreatedAt BETWEEN :startDate AND :endDate
            GROUP BY ScreenResolution
            ORDER BY count DESC
        ';
    }

    private function getTypeDistributionSql(): string
    {
        return '
            SELECT Type, COUNT(*) AS count
            FROM Log
            WHERE CreatedAt BETWEEN :startDate AND :endDate
            GROUP BY Type
            ORDER BY count DESC
        ';
    }
}