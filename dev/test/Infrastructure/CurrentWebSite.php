<?php

declare(strict_types=1);

namespace test\Infrastructure;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class CurrentWebSite
{
    private const TEMPLATE_RELATIVE = '/app/models/database/MyClub.sqlite';
    private const LAST_TEST_COPY = 'lastMyClubTest.sqlite';

    public static function backup(string $dbWebSitePath): bool
    {
        if (!file_exists($dbWebSitePath)) {
            return false;
        }

        $destination = self::backupPath($dbWebSitePath);
        return copy($dbWebSitePath, $destination);
    }

    public static function remove(string $dbWebSitePath): bool
    {
        if (!file_exists($dbWebSitePath)) {
            return true;
        }

        return unlink($dbWebSitePath);
    }

    public static function restore(string $dbWebSitePath): bool
    {
        if (file_exists($dbWebSitePath)) {
            self::copyTest($dbWebSitePath);
        }

        $backupPath = self::backupPath($dbWebSitePath);
        if (!file_exists($backupPath)) {
            return false;
        }

        return copy($backupPath, $dbWebSitePath);
    }

    /**
     * Copy the template DB from app/models/database into WebSite/data,
     * then seed groups, authorizations, individuals and members for tests.
     */
    public static function installFresh(string $dbWebSitePath): void
    {
        $templatePath = self::templatePath();
        if (!file_exists($templatePath)) {
            throw new InvalidArgumentException("Template database not found: {$templatePath}");
        }

        $directory = dirname($dbWebSitePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create database directory: {$directory}");
        }

        if (!copy($templatePath, $dbWebSitePath)) {
            throw new RuntimeException(
                "Failed to copy template database from {$templatePath} to {$dbWebSitePath}"
            );
        }

        self::seedTestData($dbWebSitePath);
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private static function copyTest(string $dbWebSitePath): bool
    {
        $destination = __DIR__ . '/../Database/' . self::LAST_TEST_COPY;
        return copy($dbWebSitePath, $destination);
    }

    private static function backupPath(string $dbWebSitePath): string
    {
        return __DIR__ . '/../Database/' . basename($dbWebSitePath);
    }

    private static function templatePath(): string
    {
        return MYCLUB_WEBSITE_DIR . self::TEMPLATE_RELATIVE;
    }

    private static function seedTestData(string $dbWebSitePath): void
    {
        try {
            $pdo = new PDO('sqlite:' . $dbWebSitePath, options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Cannot open site database for seeding: ' . $e->getMessage(),
                0,
                $e
            );
        }

        $pdo->exec('PRAGMA foreign_keys = OFF');
        $pdo->beginTransaction();

        try {
            $pdo->exec(<<<'SQL'
INSERT INTO "Group" VALUES
 (2,'Human ressources',0,0),
 (3,'Events',0,0),
 (4,'Redactor',0,0),
 (5,'Editor',0,0),
 (6,'Home designer',0,0),
 (7,'Event designer',0,0),
 (8,'Visitor insights',0,0),
 (9,'Menu designer',0,0),
 (10,'Kanban designer',0,0),
 (11,'Translator',0,0),
 (12,'Communication manager',0,0),
 (13,'Loan designer',0,0),
 (14,'Loan manager',0,0),
 (15,'Exercice designer',0,0);

INSERT INTO "GroupAuthorization" VALUES
 (2,2,2),
 (3,3,3),
 (4,4,4),
 (5,5,5),
 (6,6,6),
 (7,7,7),
 (8,8,8),
 (9,9,9),
 (10,10,10),
 (11,11,11),
 (12,12,12),
 (13,13,13),
 (14,14,14),
 (15,15,15);

INSERT INTO "Individual" VALUES
 (2,'Member','personmanager@myclub.foo','Person','Manager',NULL,NULL,NULL,'2026-09-29 07:26:43'),
 (3,'Member','eventmanager@myclub.foo','Event','Manager',NULL,NULL,NULL,'2026-09-29 07:26:43'),
 (4,'Member','redactor@myclub.foo','Redactor','Manager',NULL,NULL,NULL,'2026-09-29 07:26:44'),
 (5,'Member','editor@myclub.foo','Editor','Manager',NULL,NULL,NULL,'2026-09-29 07:26:44'),
 (6,'Member','homedesigner@myclub.foo','Home','Designer',NULL,NULL,NULL,'2026-09-29 07:26:44'),
 (7,'Member','eventdesigner@myclub.foo','Event','Designer',NULL,NULL,NULL,'2026-09-29 07:26:44'),
 (8,'Member','visitorinsights@myclub.foo','Visitor','Insights',NULL,NULL,NULL,'2026-09-29 07:26:45'),
 (9,'Member','menudesigner@myclub.foo','Navbar','Designer',NULL,NULL,NULL,'2026-09-29 07:26:45'),
 (10,'Member','kanbandesigner@myclub.foo','Kanban','Designer',NULL,NULL,NULL,'2026-09-29 07:26:45'),
 (11,'Member','translator@myclub.foo','Translator','Manager',NULL,NULL,NULL,'2026-09-29 07:26:46'),
 (12,'Member','communicationmanager@myclub.foo','Communication','Manager',NULL,NULL,NULL,'2026-09-29 07:26:46'),
 (13,'Member','loandesigner@myclub.foo','Loan','Designer',NULL,NULL,NULL,'2026-09-29 07:26:46'),
 (14,'Member','loanmanager@myclub.foo','Loan','Manager',NULL,NULL,NULL,'2026-09-29 07:26:46'),
 (15,'Member','exercisedesigner@myclub.foo','Exercise','Designer',NULL,NULL,NULL,'2026-09-29 07:26:47');

INSERT INTO "Member" VALUES
 (2,'9ec71e403dace1e462f2d788450fcae3492e8c759a78e3e036b1cab1c6a77281',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:43',0,NULL,'2026-09-29 07:29:40',NULL,NULL,'',0,0,'',NULL,NULL),
 (3,'a52bfdaec7d0f794c82cedb8e4761532853c62aed72346f0dc7465eb31bfae69',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:43',0,NULL,'2026-09-29 07:29:40',NULL,NULL,'',0,0,'',NULL,NULL),
 (4,'28f5bbf0cf07213e91923715c8d2e6a79090274806b5662ef573afd6c38bf070',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:44',0,NULL,'2026-09-29 07:29:41',NULL,NULL,'',0,0,'',NULL,NULL),
 (5,'4bbb0d3f52d9de9702ae9bd320a10ae7b481380f752dc073d4e92a9e1f60f526',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:44',0,NULL,'2026-09-29 07:29:41',NULL,NULL,'',0,0,'',NULL,NULL),
 (6,'17ef3a46b95ea65576fa50f23bb5317c2d6970236f715728e69d53671e24f8e2',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:44',0,NULL,'2026-09-29 07:29:42',NULL,NULL,'',0,0,'',NULL,NULL),
 (7,'10ea3df33a3cfd1547352762c0d8beee6e6cfd0af984c55e7d8950720fd6ccbe',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:44',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL),
 (8,'780158a1778f3eea579eeb5952c26647fe8883d75ea05cc14e4268331349beb4',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:45',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL),
 (9,'70aae30959a97e359e090b21cd9283f2bd9cd09dd4af44926850289da9557abe',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:45',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL),
 (10,'95fc140275c9e2b8deabc7c21c62264403003e9386c7d9b23c98e66fc55a24e6',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:45',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL),
 (11,'5b9be38734c6ba49946961a441d7e24f3c5303f252d4699e3022461a7af107b7',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:46',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL),
 (12,'b1330d491756f1259358d3fbd283da22a8109fc901be575a241fe3cc6c50b23e',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:46',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL),
 (13,'edb3acb0e5a20eb0c6b21a403ee427c01c4f12b7a81466d9caf57fd9aa6de97b',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:46',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL),
 (14,'9c50f9ef79a4b371317c5923c286b0695222ddfecd4695ba5e7ec0f548587154',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:46',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL),
 (15,'a3ec9066e25deb463d5b90a2a87c364dfc89665bde799720b0d50177ed67d9c4',NULL,NULL,'no',NULL,NULL,NULL,0,0,NULL,'2026-09-29 07:26:47',0,NULL,'2026-09-29 07:29:39',NULL,NULL,'',0,0,'',NULL,NULL);

INSERT INTO "MemberGroup" VALUES
 (3,2,2),
 (4,3,3),
 (5,4,4),
 (6,5,5),
 (7,6,6),
 (8,7,7),
 (9,8,8),
 (10,9,9),
 (11,10,10),
 (12,11,11),
 (13,12,12),
 (14,13,13),
 (15,14,14),
 (16,15,15);
SQL);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Failed to seed test data: ' . $e->getMessage(), 0, $e);
        } finally {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
    }
}