<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LogAnalyticsDataHelperTest extends DataHelperTestCase
{
    private const LOG_DB_PATH = __DIR__ . '/../../app/models/database/LogMyClub.sqlite';

    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);

        $this->assertColumnsExist($pdo, 'Log', [
            'Token',
            'Count',
            'Code',
            'CreatedAt',
            'Referer',
        ]);
    }

    public function testGetStatisticsDataSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getStatisticsDataSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('COUNT(DISTINCT Token) as uniqueVisitors', $sql);
        $this->assertStringContainsString('COALESCE(SUM(Count), 0) as pageViews', $sql);
        $this->assertStringContainsString('BETWEEN 200 AND 299', $sql);
        $this->assertStringContainsString('BETWEEN 300 AND 399', $sql);
        $this->assertStringContainsString('BETWEEN 400 AND 499', $sql);
        $this->assertStringContainsString('BETWEEN 500 AND 599', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE CreatedAt BETWEEN :startDate AND :endDate', $sql);
    }

    public function testGetDailyVisitorCountsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getDailyVisitorCountsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DATE(CreatedAt) as day', $sql);
        $this->assertStringContainsString('COUNT(DISTINCT Token) as uniqueVisitors', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE CreatedAt BETWEEN :startDate AND :endDate', $sql);
        $this->assertStringContainsString('GROUP BY DATE(CreatedAt)', $sql);
    }

    public function testGetNavigationRangeSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getNavigationRangeSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT MIN(CreatedAt) as first, MAX(CreatedAt) as last FROM Log', $sql);
    }

    public function testGetReferentStatsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getReferentStatsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('WITH PeriodData AS', $sql);
        $this->assertStringContainsString("WHEN Referer = '' THEN 'direct'", $sql);
        $this->assertStringContainsString("WHEN Referer LIKE :host THEN 'interne'", $sql);
        $this->assertStringContainsString("ELSE 'externe'", $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE CreatedAt >= :start_date AND CreatedAt < :end_date', $sql);
        $this->assertStringContainsString('GROUP BY source', $sql);
    }

    public function testGetExternalReferentStatsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getExternalReferentStatsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Referer as source, COUNT(*) as count', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString("AND Referer != ''", $sql);
        $this->assertStringContainsString('AND Referer NOT LIKE :host', $sql);
        $this->assertStringContainsString('GROUP BY Referer', $sql);
        $this->assertStringContainsString('ORDER BY count DESC', $sql);
    }

    private function getStatisticsDataSql(): string
    {
        return "
            SELECT 
                COUNT(DISTINCT Token) as uniqueVisitors,
                COALESCE(SUM(Count), 0) as pageViews,
                COALESCE(SUM(CASE WHEN CAST(Code AS INTEGER) BETWEEN 200 AND 299 
                    OR Code IS NULL OR Code = '' THEN Count ELSE 0 END), 0) as views2xx,
                COALESCE(SUM(CASE WHEN CAST(Code AS INTEGER) BETWEEN 300 AND 399 THEN Count ELSE 0 END), 0) as views3xx,
                COALESCE(SUM(CASE WHEN CAST(Code AS INTEGER) BETWEEN 400 AND 499 THEN Count ELSE 0 END), 0) as views4xx,
                COALESCE(SUM(CASE WHEN CAST(Code AS INTEGER) BETWEEN 500 AND 599 THEN Count ELSE 0 END), 0) as views5xx
            FROM Log
            WHERE CreatedAt BETWEEN :startDate AND :endDate
        ";
    }

    private function getDailyVisitorCountsSql(): string
    {
        return "
            SELECT
                DATE(CreatedAt) as day,
                COUNT(DISTINCT Token) as uniqueVisitors
            FROM Log
            WHERE CreatedAt BETWEEN :startDate AND :endDate
            GROUP BY DATE(CreatedAt)
            ORDER BY day
        ";
    }

    private function getNavigationRangeSql(): string
    {
        return 'SELECT MIN(CreatedAt) as first, MAX(CreatedAt) as last FROM Log';
    }

    private function getReferentStatsSql(): string
    {
        return "
            WITH PeriodData AS (
                SELECT 
                    CASE 
                        WHEN Referer = '' THEN 'direct'
                        WHEN Referer LIKE :host THEN 'interne'
                        ELSE 'externe'
                    END as source
                FROM Log
                WHERE CreatedAt >= :start_date AND CreatedAt < :end_date
            )
            SELECT source, COUNT(*) as count
            FROM PeriodData
            GROUP BY source
            ORDER BY 
                CASE 
                    WHEN source = 'direct' THEN 1
                    WHEN source = 'interne' THEN 2
                    ELSE 3
                END
        ";
    }

    private function getExternalReferentStatsSql(): string
    {
        return "
            SELECT Referer as source, COUNT(*) as count
            FROM Log
            WHERE CreatedAt >= :start_date 
              AND CreatedAt < :end_date
              AND Referer != ''
              AND Referer NOT LIKE :host
            GROUP BY Referer
            ORDER BY count DESC
        ";
    }
}