<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class PersonDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Individual (données communes)
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Type',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
            'Avatar',
            'Phone',
            'CreatedAt',
        ]);

        // Member (données spécifiques aux membres)
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Password',
            'Token',
            'TokenCreatedAt',
            'UseGravatar',
            'Availabilities',
            'Preferences',
            'Notifications',
            'Imported',
            'Inactivated',
            'Presentation',
            'PresentationLastUpdate',
            'InPresentationDirectory',
            'Location',
            'LastSignIn',
            'LastSignOut',
            'Notepad',
            'Alert',
            'ShowPhoneInPresentationDirectory',
            'ShowEmailInPresentationDirectory',
            'MemberInfo',
            'MyPublicDataInPresentationDirectory',
            'LastPageView',
        ]);

        // MemberGroup (remplace PersonGroup)
        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'Id',
            'IdMember',
            'IdGroup',
        ]);

        // Contact (sous-type)
        $this->assertColumnsExist($pdo, 'Contact', [
            'Id',
            'Token',
            'TokenCreatedAt',
        ]);

        // Tables encore utilisées
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
        $insertIndividualSql = $this->getInsertEmptyIndividualSql();
        $insertMemberSql = $this->getInsertEmptyMemberSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($selectSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($insertIndividualSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($insertMemberSql));

        $this->assertStringContainsString('SELECT Id FROM Individual', $selectSql);
        $this->assertStringContainsString("Email = ''", $selectSql);
        $this->assertStringContainsString('INSERT INTO Individual', $insertIndividualSql);
        $this->assertStringContainsString('INSERT INTO Member', $insertMemberSql);
    }

    public function testGetAllPersonsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetAllPersonsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Individual i', $sql);
        $this->assertStringContainsString('INNER JOIN Member m', $sql);
        $this->assertStringContainsString('LOWER(i.Email) AS EmailKey', $sql);
    }

    public function testGetActiveMembersContactInfoByEmailSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetActiveMembersContactInfoByEmailSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Member', $sql);
        $this->assertStringContainsString('INNER JOIN Individual', $sql);
        $this->assertStringContainsString('Member.Inactivated = 0', $sql);
        $this->assertStringContainsString('Individual.Email', $sql);
        $this->assertStringContainsString('Individual.Phone', $sql);
        $this->assertStringContainsString('Individual.NickName', $sql);
    }

    public function testGetMembersAlertsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetMembersAlertsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Member m', $sql);
        $this->assertStringContainsString('INNER JOIN Individual i', $sql);
        $this->assertStringContainsString('m.Inactivated = 0', $sql);
        $this->assertStringContainsString("m.Preferences LIKE '%noAlerts%'", $sql);
        $this->assertStringContainsString("m.Preferences LIKE '%newEvent%'", $sql);
        $this->assertStringContainsString("m.Preferences LIKE '%newArticle%'", $sql);
    }

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Member m', $sql);
        $this->assertStringContainsString('INNER JOIN Individual i', $sql);
        $this->assertStringContainsString('m.InPresentationDirectory = 1', $sql);
        $this->assertStringContainsString('m.PresentationLastUpdate >= :searchFrom', $sql);
        $this->assertStringContainsString('i.Email != :email', $sql);
    }

    public function testGetPersonsForCommunicationSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetPersonsForCommunicationSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Member m', $sql);
        $this->assertStringContainsString('INNER JOIN Individual i', $sql);
        $this->assertStringContainsString("i.Email != ''", $sql);
        $this->assertStringContainsString('m.Inactivated = 0', $sql);
    }

    public function testGetPersonsInGroupSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetPersonsInGroupSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('i.Id AS PersonId', $sql);
        $this->assertStringContainsString('FROM Member m', $sql);
        $this->assertStringContainsString('INNER JOIN Individual i', $sql);
        $this->assertStringContainsString('m.Inactivated = 0', $sql);
    }

    public function testGetPersonsInGroupForDirectorySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetPersonsInGroupForDirectorySql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Member m', $sql);
        $this->assertStringContainsString('INNER JOIN Individual i', $sql);
        $this->assertStringContainsString('INNER JOIN MemberGroup mg', $sql);
        $this->assertStringContainsString('mg.IdGroup = ?', $sql);
        $this->assertStringContainsString('m.InPresentationDirectory = 1', $sql);
        $this->assertStringContainsString('m.Inactivated = 0', $sql);
    }

    public function testGetRedactorsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetRedactorsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Member m', $sql);
        $this->assertStringContainsString('INNER JOIN Individual i', $sql);
        $this->assertStringContainsString('INNER JOIN MemberGroup mg', $sql);
        $this->assertStringContainsString('INNER JOIN GroupAuthorization', $sql);
        $this->assertStringContainsString('ga.IdAuthorization = 4', $sql);
        $this->assertStringContainsString('m.Inactivated = 0', $sql);
    }

    public function testGetWebmasterEmailSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetWebmasterEmailSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT i.Email', $sql);
        $this->assertStringContainsString('FROM Member m', $sql);
        $this->assertStringContainsString('INNER JOIN Individual i', $sql);
        $this->assertStringContainsString('INNER JOIN MemberGroup mg', $sql);
        $this->assertStringContainsString('INNER JOIN "Group"', $sql);
        $this->assertStringContainsString('INNER JOIN GroupAuthorization', $sql);
        $this->assertStringContainsString('INNER JOIN Authorization', $sql);
        $this->assertStringContainsString('a.Name = "Webmaster"', $sql);
    }

    public function testImportFromCsvUpsertSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $upsertIndividualSql = $this->getImportUpsertIndividualSql();
        $upsertMemberSql     = $this->getImportUpsertMemberSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($upsertIndividualSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($upsertMemberSql));

        $this->assertStringContainsString('INSERT INTO Individual', $upsertIndividualSql);
        $this->assertStringContainsString('ON CONFLICT(Email) DO UPDATE SET', $upsertIndividualSql);
        $this->assertStringContainsString('INSERT INTO Member', $upsertMemberSql);
        $this->assertStringContainsString('ON CONFLICT(Id) DO UPDATE SET', $upsertMemberSql);
        $this->assertStringContainsString('Inactivated = 0', $upsertMemberSql);
    }

    public function testImportDeactivateSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getImportDeactivateSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE Member', $sql);
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

        $this->assertStringContainsString('UPDATE Member', $updateLastSignOutSql);
        $this->assertStringContainsString('SET LastSignOut = :lastActivity', $updateLastSignOutSql);
        $this->assertStringContainsString('SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE', $updateLastSignOutSql);

        $this->assertStringContainsString('UPDATE Member', $updateLastSignInSql);
        $this->assertStringContainsString('SET LastSignIn = :now', $updateLastSignInSql);
        $this->assertStringContainsString('SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE', $updateLastSignInSql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from PersonDataHelper)
    // -------------------------------------------------------------------------

    private function getSelectEmptyEmailPersonSql(): string
    {
        return "SELECT Id FROM Individual WHERE Email = '' AND Type = 'Member'";
    }

    private function getInsertEmptyIndividualSql(): string
    {
        return "
                INSERT INTO Individual (Type, Email, FirstName, LastName)
                VALUES ('Member', '', '', '')
";
    }

    private function getInsertEmptyMemberSql(): string
    {
        return "
                INSERT INTO Member (Id, Imported)
                VALUES (:id, 0)
";
    }

    private function getGetAllPersonsSql(): string
    {
        return "SELECT i.Id, LOWER(i.Email) AS EmailKey
             FROM Individual i
             INNER JOIN Member m ON m.Id = i.Id";
    }

    private function getGetActiveMembersContactInfoByEmailSql(): string
    {
        return "
            SELECT Individual.Email, Individual.Phone, Individual.FirstName, Individual.LastName, Individual.NickName
            FROM Member
            INNER JOIN Individual ON Individual.Id = Member.Id
            WHERE Member.Inactivated = 0
        ";
    }

    private function getGetMembersAlertsSql(): string
    {
        return "
            SELECT 
                i.FirstName || ' ' || i.LastName || 
                CASE 
                    WHEN i.NickName IS NOT NULL AND i.NickName != '' THEN ' (' || i.NickName || ')'
                    ELSE ''
                END AS clubMember,
                CASE 
                    WHEN m.Preferences LIKE '%noAlerts%' THEN 'X'
                    ELSE ''
                END AS NoAlert,
                CASE 
                    WHEN m.Preferences LIKE '%newEvent%' THEN 'X'
                    ELSE ''
                END AS NewEvent,
                CASE 
                    WHEN m.Preferences LIKE '%newArticle%' THEN 'X'
                    ELSE ''
                END AS NewArticle
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            WHERE (m.Preferences LIKE '%noAlerts%' 
               OR m.Preferences LIKE '%newEvent%' 
               OR m.Preferences LIKE '%newArticle%')
              AND m.Inactivated = 0
            ORDER BY clubMember
";
    }

    private function getGetNewsSql(): string
    {
        return "
            SELECT i.Id, i.Email, i.FirstName, i.LastName, m.PresentationLastUpdate
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            WHERE m.InPresentationDirectory = 1
              AND m.PresentationLastUpdate >= :searchFrom
              AND i.Email != :email
            ORDER BY m.PresentationLastUpdate DESC
";
    }

    private function getGetPersonsForCommunicationSql(): string
    {
        return "
            SELECT DISTINCT i.Id, i.FirstName, i.LastName, i.Email
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            WHERE i.Email != ''
              AND m.Inactivated = 0
            ORDER BY i.FirstName, i.LastName
";
    }

    private function getGetPersonsInGroupSql(): string
    {
        return "
            SELECT
                i.Id AS PersonId,
                i.Id AS Id,
                i.FirstName,
                i.LastName,
                i.Email,
                m.Preferences,
                m.Availabilities,
                m.InPresentationDirectory,
                m.ShowPhoneInPresentationDirectory,
                m.ShowEmailInPresentationDirectory
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            WHERE m.Inactivated = 0
            ORDER BY i.FirstName, i.LastName
";
    }

    private function getGetPersonsInGroupForDirectorySql(): string
    {
        return "
            SELECT DISTINCT 
                i.Id,
                m.UseGravatar, 
                i.Email,
                i.Avatar,
                i.FirstName,
                i.LastName,
                i.NickName
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            WHERE mg.IdGroup = ?
              AND m.InPresentationDirectory = 1
              AND m.Inactivated = 0
            ORDER BY i.FirstName, i.LastName
";
    }

    private function getGetRedactorsSql(): string
    {
        return "
            SELECT i.Id AS PersonId, i.FirstName, i.LastName, i.NickName, i.Email
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            INNER JOIN GroupAuthorization ga ON ga.IdGroup = mg.IdGroup
            WHERE m.Inactivated = 0
              AND ga.IdAuthorization = 4
            GROUP BY i.Id
            ORDER BY i.FirstName, i.LastName
";
    }

    private function getGetWebmasterEmailSql(): string
    {
        return '
            SELECT i.Email
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            INNER JOIN "Group" g ON g.Id = mg.IdGroup
            INNER JOIN GroupAuthorization ga ON ga.IdGroup = g.Id
            INNER JOIN Authorization a ON a.Id = ga.IdAuthorization
            WHERE a.Name = "Webmaster"
';
    }

    private function getImportUpsertIndividualSql(): string
    {
        return "
                INSERT INTO Individual (Type, Email, FirstName, LastName, Phone)
                VALUES ('Member', :email, :firstName, :lastName, :phone)
                ON CONFLICT(Email) DO UPDATE SET
                    FirstName = excluded.FirstName,
                    LastName  = excluded.LastName,
                    Phone     = excluded.Phone
";
    }

    private function getImportUpsertMemberSql(): string
    {
        return "
                INSERT INTO Member (Id, Imported, Inactivated)
                VALUES (:id, 1, 0)
                ON CONFLICT(Id) DO UPDATE SET
                    Imported    = 1,
                    Inactivated = 0
";
    }

    private function getImportDeactivateSql(): string
    {
        return "
                    UPDATE Member
                    SET Inactivated = 1
                    WHERE Id IN (?,?)
";
    }

    private function getUpdateLastSignOutSql(): string
    {
        return "
                UPDATE Member
                SET LastSignOut = :lastActivity
                WHERE Id = (
                    SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE
                )
";
    }

    private function getUpdateLastSignInSql(): string
    {
        return "
            UPDATE Member
            SET LastSignIn = :now
            WHERE Id = (
                SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE
            )
";
    }
}
