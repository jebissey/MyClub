<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class PersonDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
            'Phone',
            'Imported',
            'Inactivated',
            'Password',
            'Preferences',
            'Availabilities',
            'InPresentationDirectory',
            'ShowPhoneInPresentationDirectory',
            'ShowEmailInPresentationDirectory',
            'MyPublicDataInPresentationDirectory',
            'UseGravatar',
            'Avatar',
            'PresentationLastUpdate',
            'LastSignIn',
            'LastSignOut',
        ]);

        $this->assertColumnsExist($pdo, 'PersonGroup', [
            'IdPerson',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Survey', [
            'Id',
            'IdArticle',
        ]);

        $this->assertColumnsExist($pdo, 'Order', [
            'Id',
            'IdArticle',
        ]);

        $this->assertColumnsExist($pdo, 'Message', [
            'EventId',
            'PersonId',
            'Text',
            'From',
        ]);

        $this->assertColumnsExist($pdo, 'Contact', [
            'Id',
            'Email',
            'NickName',
            'Token',
            'TokenCreatedAt',
        ]);

        $this->assertColumnsExist($pdo, 'GroupAuthorization', [
            'IdGroup',
            'IdAuthorization',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
        ]);

        $this->assertColumnsExist($pdo, 'Authorization', [
            'Id',
            'Name',
        ]);
    }

    public function testCreateSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $selectSql = $this->getSelectEmptyEmailPersonSql();
        $insertSql = $this->getInsertEmptyPersonSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($selectSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($insertSql));

        $this->assertStringContainsString('SELECT Id FROM Person', $selectSql);
        $this->assertStringContainsString("Email = ''", $selectSql);
        $this->assertStringContainsString('INSERT INTO Person', $insertSql);
        $this->assertStringContainsString('(Email, FirstName, LastName, Imported)', $insertSql);
    }

    public function testGetAllPersonsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetAllPersonsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Id, LOWER(Email) AS EmailKey FROM Person', $sql);
    }

    public function testGetMembersAlertsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetMembersAlertsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Person AS p', $sql);
        $this->assertStringContainsString('p.Inactivated = 0', $sql);
        $this->assertStringContainsString("p.Preferences LIKE '%noAlerts%'", $sql);
        $this->assertStringContainsString("p.Preferences LIKE '%newEvent%'", $sql);
        $this->assertStringContainsString("p.Preferences LIKE '%newArticle%'", $sql);
    }

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Id, Email, FirstName, LastName, PresentationLastUpdate', $sql);
        $this->assertStringContainsString('FROM Person', $sql);
        $this->assertStringContainsString('InPresentationDirectory = 1', $sql);
        $this->assertStringContainsString('PresentationLastUpdate >= :searchFrom', $sql);
        $this->assertStringContainsString('Email != :email', $sql);
    }

    public function testGetPersonsForCommunicationSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetPersonsForCommunicationSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT DISTINCT p.Id, p.FirstName, p.LastName, p.Email', $sql);
        $this->assertStringContainsString('FROM Person p', $sql);
        $this->assertStringContainsString("p.Email != ''", $sql);
    }

    public function testGetPersonsInGroupSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetPersonsInGroupSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('Person.Id AS PersonId', $sql);
        $this->assertStringContainsString('FROM Person', $sql);
        $this->assertStringContainsString('Person.Inactivated = 0', $sql);
    }

    public function testGetPersonsInGroupForDirectorySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetPersonsInGroupForDirectorySql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Person p', $sql);
        $this->assertStringContainsString('JOIN PersonGroup pg ON p.Id = pg.IdPerson', $sql);
        $this->assertStringContainsString('pg.IdGroup = ?', $sql);
        $this->assertStringContainsString('p.InPresentationDirectory = 1', $sql);
        $this->assertStringContainsString('p.Inactivated = 0', $sql);
    }

    public function testGetRedactorsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetRedactorsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Person', $sql);
        $this->assertStringContainsString('INNER JOIN PersonGroup', $sql);
        $this->assertStringContainsString('INNER JOIN GroupAuthorization', $sql);
        $this->assertStringContainsString('GroupAuthorization.IdAuthorization = 4', $sql);
        $this->assertStringContainsString('Person.Inactivated = 0', $sql);
    }

    public function testGetWebmasterEmailSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetWebmasterEmailSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT Email FROM Person', $sql);
        $this->assertStringContainsString('INNER JOIN PersonGroup', $sql);
        $this->assertStringContainsString('INNER JOIN "Group"', $sql);
        $this->assertStringContainsString('INNER JOIN GroupAuthorization', $sql);
        $this->assertStringContainsString('INNER JOIN Authorization', $sql);
        $this->assertStringContainsString('Authorization.Name = "Webmaster"', $sql);
    }

    public function testImportFromCsvUpsertSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getImportUpsertSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO Person', $sql);
        $this->assertStringContainsString('(Email, FirstName, LastName, Phone, Imported, Inactivated)', $sql);
        $this->assertStringContainsString('ON CONFLICT(Email) DO UPDATE SET', $sql);
        $this->assertStringContainsString('FirstName   = excluded.FirstName', $sql);
        $this->assertStringContainsString('Inactivated = 0', $sql);
    }

    public function testImportDeactivateSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getImportDeactivateSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE Person', $sql);
        $this->assertStringContainsString('SET Inactivated = 1', $sql);
        $this->assertStringContainsString('WHERE Id IN (', $sql);
    }

    public function testUpdateActivitySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $updateLastSignOutSql = $this->getUpdateLastSignOutSql();
        $updateLastSignInSql  = $this->getUpdateLastSignInSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($updateLastSignOutSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($updateLastSignInSql));

        $this->assertStringContainsString('UPDATE Person', $updateLastSignOutSql);
        $this->assertStringContainsString('SET LastSignOut = :lastActivity', $updateLastSignOutSql);
        $this->assertStringContainsString('WHERE Email = :email COLLATE NOCASE', $updateLastSignOutSql);

        $this->assertStringContainsString('UPDATE Person', $updateLastSignInSql);
        $this->assertStringContainsString('SET LastSignIn = :now', $updateLastSignInSql);
        $this->assertStringContainsString('WHERE Email = :email COLLATE NOCASE', $updateLastSignInSql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from PersonDataHelper)
    // -------------------------------------------------------------------------

    private function getSelectEmptyEmailPersonSql(): string
    {
        return "SELECT Id FROM Person WHERE Email = ''";
    }

    private function getInsertEmptyPersonSql(): string
    {
        return "
                INSERT INTO Person (Email, FirstName, LastName, Imported) 
                VALUES ('', '', '', 0)
        ";
    }

    private function getGetAllPersonsSql(): string
    {
        return "SELECT Id, LOWER(Email) AS EmailKey FROM Person";
    }

    private function getGetMembersAlertsSql(): string
    {
        return "
            SELECT 
                p.FirstName || ' ' || p.LastName || 
                CASE 
                    WHEN p.NickName IS NOT NULL AND p.NickName != '' THEN ' (' || p.NickName || ')'
                    ELSE ''
                END AS clubMember,
                CASE 
                    WHEN p.Preferences LIKE '%noAlerts%' THEN 'X'
                    ELSE ''
                END AS NoAlert,
                CASE 
                    WHEN p.Preferences LIKE '%newEvent%' THEN 'X'
                    ELSE ''
                END AS NewEvent,
                CASE 
                    WHEN p.Preferences LIKE '%newArticle%' THEN 'X'
                    ELSE ''
                END AS NewArticle
            FROM Person AS p
            WHERE (p.Preferences LIKE '%noAlerts%' 
            OR p.Preferences LIKE '%newEvent%' 
            OR p.Preferences LIKE '%newArticle%')
            AND p.Inactivated = 0
            ORDER BY clubMember
        ";
    }

    private function getGetNewsSql(): string
    {
        return "
            SELECT Id, Email, FirstName, LastName, PresentationLastUpdate
            FROM Person
            WHERE InPresentationDirectory = 1
            AND PresentationLastUpdate >= :searchFrom
            AND Email != :email
            ORDER BY PresentationLastUpdate DESC
        ";
    }

    private function getGetPersonsForCommunicationSql(): string
    {
        // Représentation minimale / typique (sans jointure dynamique)
        return "
            SELECT DISTINCT p.Id, p.FirstName, p.LastName, p.Email
            FROM Person p
            WHERE p.Email != ''
            AND p.Inactivated = 0
            ORDER BY p.FirstName, p.LastName
        ";
    }

    private function getGetPersonsInGroupSql(): string
    {
        return "
            SELECT
                Person.Id AS PersonId,
                Person.Id AS Id,
                FirstName,
                LastName,
                Email,
                Preferences,
                Availabilities,
                InPresentationDirectory,
                ShowPhoneInPresentationDirectory,
                ShowEmailInPresentationDirectory
            FROM Person
            WHERE Person.Inactivated = 0
            ORDER BY FirstName, LastName
        ";
    }

    private function getGetPersonsInGroupForDirectorySql(): string
    {
        return "
            SELECT DISTINCT 
                p.Id,
                P.UseGravatar, 
                p.Email,
                p.Avatar,
                p.FirstName,
                p.LastName,
                p.NickName
            FROM Person p
            JOIN PersonGroup pg ON p.Id = pg.IdPerson
            WHERE pg.IdGroup = ? AND p.InPresentationDirectory = 1 AND p.Inactivated = 0
            ORDER BY p.FirstName, p.LastName
        ";
    }

    private function getGetRedactorsSql(): string
    {
        return "
            SELECT Person.Id AS PersonId, FirstName, LastName, NickName, Email
            FROM Person
            INNER JOIN PersonGroup        ON PersonGroup.IdPerson        = Person.Id
            INNER JOIN GroupAuthorization ON GroupAuthorization.IdGroup = PersonGroup.IdGroup
            WHERE Person.Inactivated = 0
            AND GroupAuthorization.IdAuthorization = 4
            GROUP BY Person.Id
            ORDER BY FirstName, LastName
        ";
    }

    private function getGetWebmasterEmailSql(): string
    {
        return '
            SELECT Email FROM Person
            INNER JOIN PersonGroup on Person.Id = PersonGroup.IdPerson
            INNER JOIN "Group" on "Group".Id = PersonGroup.IdGroup
            INNER JOIN GroupAuthorization on "Group".Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id
            WHERE Authorization.Name = "Webmaster"
        ';
    }

    private function getImportUpsertSql(): string
    {
        return "
                INSERT INTO Person (Email, FirstName, LastName, Phone, Imported, Inactivated)
                VALUES (:email, :firstName, :lastName, :phone, 1, 0)
                ON CONFLICT(Email) DO UPDATE SET
                    FirstName   = excluded.FirstName,
                    LastName    = excluded.LastName,
                    Phone       = excluded.Phone,
                    Imported    = 1,
                    Inactivated = 0
        ";
    }

    private function getImportDeactivateSql(): string
    {
        return "
                    UPDATE Person
                    SET Inactivated = 1
                    WHERE Id IN (?,?)
        ";
    }

    private function getUpdateLastSignOutSql(): string
    {
        return "
                UPDATE Person 
                SET LastSignOut = :lastActivity
                WHERE Email = :email COLLATE NOCASE
        ";
    }

    private function getUpdateLastSignInSql(): string
    {
        return "
            UPDATE Person 
            SET LastSignIn = :now
            WHERE Email = :email COLLATE NOCASE
        ";
    }
}