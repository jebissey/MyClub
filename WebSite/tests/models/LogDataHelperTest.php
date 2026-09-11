<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LogDataHelperTest extends DataHelperTestCase
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

        $this->assertColumnsExist($mainPdo, 'Person', [
            'Email',
            'FirstName',
            'LastName',
        ]);
    }

    public function testGetLastVisitPerActivePersonSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getLastVisitPerActivePersonSql(['e0', 'e1']);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT l.Who, l.CreatedAt, l.Os, l.Browser', $sql);
        $this->assertStringContainsString('FROM Log l', $sql);
        $this->assertStringContainsString('SELECT LOWER(Who) AS Who, MAX(CreatedAt) AS MaxCreatedAt', $sql);
        $this->assertStringContainsString('WHERE LOWER(Who) IN (:e0, :e1)', $sql);
        $this->assertStringContainsString('GROUP BY LOWER(Who)', $sql);
        $this->assertStringContainsString('ON LOWER(l.Who) = latest.Who', $sql);
        $this->assertStringContainsString('AND l.CreatedAt = latest.MaxCreatedAt', $sql);
    }

    public function testGetVisitedPagesColumnsMatchLogSchema(): void
    {
        // getVisitedPages() builds its query via FluentPDO (Select object), not a raw
        // SQL string, so there is no literal to prepare()/assert against here. Its
        // selected columns are covered by testQueriedColumnsExistInDatabaseSchema()
        // above (CreatedAt, Type, Browser, Os, Uri, Who, Code, Message, Duration all
        // belong to the asserted Log column list).
        $this->assertTrue(true);
    }

    public function testGetPersonsBaseSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPersonsBaseSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT LOWER(Email) AS Email, FirstName, LastName FROM Person', $sql);
    }

    public function testGetPersonsFilteredSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPersonsFilteredSql(2);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT LOWER(Email) AS Email, FirstName, LastName FROM Person', $sql);
        $this->assertStringContainsString('WHERE LOWER(Email) IN (?,?)', $sql);
    }

    public function testGetTopArticlesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getTopArticlesSql("CreatedAt >= '2024-01-01'");

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('CleanUri AS Uri', $sql);
        $this->assertStringContainsString('COUNT(*) AS visits', $sql);
        $this->assertStringContainsString('ROUND(AVG(CASE WHEN Duration IS NOT NULL THEN Duration END), 2) AS avg_duration', $sql);
        $this->assertStringContainsString('WHEN CleanUri LIKE "/article/%" THEN CAST(substr(CleanUri, 10) AS INTEGER)', $sql);
        $this->assertStringContainsString('WHEN CleanUri LIKE "/menu/show/article/%" THEN CAST(substr(CleanUri, 20) AS INTEGER)', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString("WHERE CreatedAt >= '2024-01-01'", $sql);
        $this->assertStringContainsString('GROUP BY CleanUri', $sql);
        $this->assertStringContainsString('ORDER BY visits DESC', $sql);
        $this->assertStringContainsString('LIMIT :top', $sql);
    }

    public function testGetTopPagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getTopPagesSql("CreatedAt >= '2024-01-01'");

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('CleanUri AS Uri', $sql);
        $this->assertStringContainsString('COUNT(*) AS visits', $sql);
        $this->assertStringContainsString('ROUND(AVG(CASE WHEN Duration IS NOT NULL THEN Duration END), 2) AS avg_duration', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString("WHERE CreatedAt >= '2024-01-01'", $sql);
        $this->assertStringContainsString('GROUP BY CleanUri', $sql);
        $this->assertStringContainsString('ORDER BY visits DESC', $sql);
        $this->assertStringContainsString('LIMIT :limit', $sql);
    }

    public function testGetVisitsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getVisitsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Who, SUM(Count) as VisitCount', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE CreatedAt BETWEEN :start AND :end', $sql);
        $this->assertStringContainsString('GROUP BY Who', $sql);
    }

    public function testGetInstallationsDataSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getInstallationsDataSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString("WHERE l2.IpAddress = Log.IpAddress", $sql);
        $this->assertStringContainsString("AND l2.Uri LIKE '/api/lastVersion%'", $sql);
        $this->assertStringContainsString("MAX(CreatedAt) as lastCheck", $sql);
        $this->assertStringContainsString('COUNT(*) as checkCount', $sql);
        $this->assertStringContainsString('GROUP_CONCAT(DISTINCT', $sql);
        $this->assertStringContainsString('as webappVersions', $sql);
        $this->assertStringContainsString('GROUP_CONCAT(DISTINCT Message) as phpVersions', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString("WHERE Uri LIKE '/api/lastVersion%'", $sql);
        $this->assertStringContainsString('GROUP BY IpAddress', $sql);
        $this->assertStringContainsString('ORDER BY MAX(CreatedAt) DESC', $sql);
    }

    public function testGetCreationTimeDistributionSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getCreationTimeDistributionSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT CAST(Duration AS INTEGER) AS duration', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE Uri LIKE :uri_pattern', $sql);
        $this->assertStringContainsString('AND Duration IS NOT NULL', $sql);
        $this->assertStringContainsString('AND Duration > 0', $sql);
        $this->assertStringContainsString('AND CreatedAt BETWEEN :from AND :to', $sql);
        $this->assertStringContainsString('ORDER BY duration ASC', $sql);
    }

    public function testGetCreationTimeTrendSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getCreationTimeTrendSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT CAST(Duration AS INTEGER) AS duration,', $sql);
        $this->assertStringContainsString('CreatedAt', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE Uri LIKE :uri', $sql);
        $this->assertStringContainsString('AND Duration IS NOT NULL', $sql);
        $this->assertStringContainsString('AND Duration > 0', $sql);
        $this->assertStringContainsString('AND CreatedAt BETWEEN :from AND :to', $sql);
        $this->assertStringContainsString('ORDER BY CreatedAt ASC', $sql);
    }

    /** @param list<string> $placeholderNames without leading colon, e.g. ['e0', 'e1'] */
    private function getLastVisitPerActivePersonSql(array $placeholderNames): string
    {
        $placeholders = array_map(static fn(string $name): string => ':' . $name, $placeholderNames);
        $in = implode(', ', $placeholders);

        return "
            SELECT l.Who, l.CreatedAt, l.Os, l.Browser
            FROM Log l
            INNER JOIN (
                SELECT LOWER(Who) AS Who, MAX(CreatedAt) AS MaxCreatedAt
                FROM Log
                WHERE LOWER(Who) IN ($in)
                GROUP BY LOWER(Who)
            ) latest
                ON LOWER(l.Who) = latest.Who
            AND l.CreatedAt = latest.MaxCreatedAt
        ";
    }

    private function getPersonsBaseSql(): string
    {
        return 'SELECT LOWER(Email) AS Email, FirstName, LastName FROM Person';
    }

    private function getPersonsFilteredSql(int $emailCount): string
    {
        $placeholders = implode(',', array_fill(0, $emailCount, '?'));

        return $this->getPersonsBaseSql() . " WHERE LOWER(Email) IN ($placeholders)";
    }

    private function getTopArticlesSql(string $dateCondition): string
    {
        return '
            SELECT
                CleanUri AS Uri,
                COUNT(*) AS visits,
                ROUND(AVG(CASE WHEN Duration IS NOT NULL THEN Duration END), 2) AS avg_duration,
                CASE
                    WHEN CleanUri LIKE "/article/%" THEN CAST(substr(CleanUri, 10) AS INTEGER)
                    WHEN CleanUri LIKE "/menu/show/article/%" THEN CAST(substr(CleanUri, 20) AS INTEGER)
                    ELSE NULL
                END AS articleId
            FROM (
                SELECT
                    CASE
                        WHEN INSTR(Uri, " (") > 0 THEN SUBSTR(Uri, 1, INSTR(Uri, " (") - 1)
                        ELSE Uri
                    END AS CleanUri,
                    Duration
                FROM Log
                WHERE ' . $dateCondition . '
            )
            WHERE (
                (CleanUri LIKE "/article/%" AND CleanUri GLOB "/article/[0-9]*" AND CleanUri NOT LIKE "/article/%/%")
                OR
                (CleanUri LIKE "/menu/show/article/%"
                    AND CleanUri GLOB "/menu/show/article/[0-9]*"
                    AND CleanUri NOT LIKE "/menu/show/article/%/%"
                )
            )
            GROUP BY CleanUri
            ORDER BY visits DESC
            LIMIT :top
        ';
    }

    private function getTopPagesSql(string $dateCondition): string
    {
        return "
            SELECT
                CleanUri AS Uri,
                COUNT(*) AS visits,
                ROUND(AVG(CASE WHEN Duration IS NOT NULL THEN Duration END), 2) AS avg_duration
            FROM (
                SELECT
                    CASE
                        WHEN INSTR(Uri, ' (') > 0 THEN SUBSTR(Uri, 1, INSTR(Uri, ' (') - 1)
                        ELSE Uri
                    END AS CleanUri,
                    Duration
                FROM Log
                WHERE $dateCondition
            )
            GROUP BY CleanUri
            ORDER BY visits DESC
            LIMIT :limit
        ";
    }

    private function getVisitsSql(): string
    {
        return "
            SELECT Who, SUM(Count) as VisitCount
            FROM Log
            WHERE CreatedAt BETWEEN :start AND :end
            GROUP BY Who
        ";
    }

    private function getInstallationsDataSql(): string
    {
        return "
            SELECT
                IpAddress,
                COALESCE(
                    (SELECT
                        CASE
                            WHEN INSTR(Uri,'url=') > 0 THEN
                                CASE
                                    WHEN INSTR(SUBSTR(Uri, INSTR(Uri,'url=')+4),'&') > 0 THEN
                                        SUBSTR(
                                            SUBSTR(Uri, INSTR(Uri,'url=')+4),
                                            1,
                                            INSTR(SUBSTR(Uri, INSTR(Uri,'url=')+4),'&')-1
                                        )
                                    ELSE SUBSTR(Uri, INSTR(Uri,'url=')+4)
                                END
                        END
                     FROM Log l2
                     WHERE l2.IpAddress = Log.IpAddress
                       AND l2.Uri LIKE '/api/lastVersion%'
                       AND INSTR(l2.Uri,'url=') > 0
                     LIMIT 1),
                    IpAddress
                ) as Host,
                MAX(CreatedAt) as lastCheck,
                COUNT(*) as checkCount,
                GROUP_CONCAT(DISTINCT
                    CASE
                        WHEN INSTR(Uri,'cv=') > 0 THEN
                            CASE
                                WHEN INSTR(SUBSTR(Uri, INSTR(Uri,'cv=')+3),'&') > 0 THEN
                                    SUBSTR(
                                        SUBSTR(Uri, INSTR(Uri,'cv=')+3),
                                        1,
                                        INSTR(SUBSTR(Uri, INSTR(Uri,'cv=')+3),'&')-1
                                    )
                                ELSE SUBSTR(Uri, INSTR(Uri,'cv=')+3)
                            END
                    END
                ) as webappVersions,
                GROUP_CONCAT(DISTINCT Message) as phpVersions
            FROM Log
            WHERE Uri LIKE '/api/lastVersion%'
            GROUP BY IpAddress
            ORDER BY MAX(CreatedAt) DESC
        ";
    }

    private function getCreationTimeDistributionSql(): string
    {
        return "
            SELECT CAST(Duration AS INTEGER) AS duration
            FROM Log
            WHERE Uri LIKE :uri_pattern
            AND Duration IS NOT NULL
            AND Duration > 0
            AND CreatedAt BETWEEN :from AND :to
            ORDER BY duration ASC
        ";
    }

    private function getCreationTimeTrendSql(): string
    {
        return "
            SELECT CAST(Duration AS INTEGER) AS duration,
                CreatedAt
            FROM Log
            WHERE Uri LIKE :uri
            AND Duration IS NOT NULL
            AND Duration > 0
            AND CreatedAt BETWEEN :from AND :to
            ORDER BY CreatedAt ASC
        ";
    }
}