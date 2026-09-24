<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class PersonDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Type',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
            'Avatar',
            'Phone',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Imported',
            'Inactivated',
            'UseGravatar',
            'Preferences',
            'Availabilities',
            'InPresentationDirectory',
            'ShowPhoneInPresentationDirectory',
            'ShowEmailInPresentationDirectory',
            'PresentationLastUpdate',
            'Password',
            'MyPublicDataInPresentationDirectory',
            'LastSignIn',
            'LastSignOut',
        ]);

        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'IdMember',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'GroupAuthorization', [
            'IdGroup',
            'IdAuthorization',
        ]);

        $this->assertColumnsExist($pdo, 'Authorization', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Survey', [
            'IdArticle',
            'Id',
        ]);

        $this->assertColumnsExist($pdo, 'Order', [
            'IdArticle',
            'Id',
        ]);

        $this->assertColumnsExist($pdo, 'Message', [
            'EventId',
            'PersonId',
            'Text',
            'From',
        ]);

        $this->assertColumnsExist($pdo, 'Contact', [
            'Id',
            'Token',
            'TokenCreatedAt',
        ]);
    }

    public function testGetAllPersonsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAllPersonsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT i.Id, LOWER(i.Email) AS EmailKey', $sql);
        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
    }

    public function testGetActiveMembersContactInfoByEmailSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getActiveMembersContactInfoByEmailSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT i.Email, i.Phone, i.FirstName, i.LastName, i.NickName', $sql);
        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
        $this->assertStringContainsString('WHERE m.Inactivated = 0', $sql);
    }

    public function testGetMembersAlertsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getMembersAlertsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
        $this->assertStringContainsString("WHEN m.Preferences LIKE '%noAlerts%' THEN 'X'", $sql);
    }

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT i.Id, i.Email, i.FirstName, i.LastName, m.PresentationLastUpdate', $sql);
        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
        $this->assertStringContainsString('WHERE m.InPresentationDirectory = 1', $sql);
    }

    public function testGetPersonsForCommunicationSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPersonsForCommunicationSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT DISTINCT i.Id, i.FirstName, i.LastName, i.Email', $sql);
        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
        $this->assertStringContainsString('INNER JOIN MemberGroup mg ON mg.IdMember = m.Id', $sql);
        $this->assertStringContainsString('mg.IdGroup = :groupId', $sql);
    }

    public function testGetPersonsInGroupSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPersonsInGroupSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('i.Id AS PersonId', $sql);
        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
        $this->assertStringContainsString('WHERE m.Inactivated = 0', $sql);
    }

    public function testGetPersonsInGroupForDirectorySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPersonsInGroupForDirectorySql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
        $this->assertStringContainsString('INNER JOIN MemberGroup mg ON mg.IdMember = m.Id', $sql);
        $this->assertStringContainsString('WHERE mg.IdGroup = ?', $sql);
    }

    public function testGetRedactorsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getRedactorsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
        $this->assertStringContainsString('INNER JOIN GroupAuthorization ga ON ga.IdGroup = mg.IdGroup', $sql);
        $this->assertStringContainsString('ga.IdAuthorization = 4', $sql);
    }

    public function testGetWebmasterEmailSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getWebmasterEmailSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString($this->memberIndividualJoin(), $sql);
        $this->assertStringContainsString('INNER JOIN "Group" g ON g.Id = mg.IdGroup', $sql);
        $this->assertStringContainsString('a.Name = "Webmaster"', $sql);
    }

    public function testGetPublisherSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $stmt = $pdo->prepare('SELECT FirstName, LastName FROM Individual WHERE Id = :id');
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testUpdateActivitySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sqlBoth = "
            UPDATE Member
            SET LastSignIn = :now, LastSignOut = :lastActivity
            WHERE Id = (
                SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE
            )
        ";
        $stmtBoth = $pdo->prepare($sqlBoth);
        $this->assertInstanceOf(PDOStatement::class, $stmtBoth);

        $sqlSignInOnly = "
            UPDATE Member
            SET LastSignIn = :now
            WHERE Id = (
                SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE
            )
        ";
        $stmtSignInOnly = $pdo->prepare($sqlSignInOnly);
        $this->assertInstanceOf(PDOStatement::class, $stmtSignInOnly);
    }

    private function memberIndividualJoin(): string
    {
        return 'FROM Member m INNER JOIN Individual i ON i.Id = m.Id';
    }

    private function getAllPersonsSql(): string
    {
        return "SELECT i.Id, LOWER(i.Email) AS EmailKey " . $this->memberIndividualJoin();
    }

    private function getActiveMembersContactInfoByEmailSql(): string
    {
        return "
            SELECT i.Email, i.Phone, i.FirstName, i.LastName, i.NickName
            " . $this->memberIndividualJoin() . "
            WHERE m.Inactivated = 0
        ";
    }

    private function getMembersAlertsSql(): string
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
            " . $this->memberIndividualJoin() . "
            WHERE (m.Preferences LIKE '%noAlerts%' 
               OR m.Preferences LIKE '%newEvent%' 
               OR m.Preferences LIKE '%newArticle%')
              AND m.Inactivated = 0
            ORDER BY clubMember
        ";
    }

    private function getNewsSql(): string
    {
        return "
            SELECT i.Id, i.Email, i.FirstName, i.LastName, m.PresentationLastUpdate
            " . $this->memberIndividualJoin() . "
            WHERE m.InPresentationDirectory = 1
              AND m.PresentationLastUpdate >= :searchFrom
              AND i.Email != :email
            ORDER BY m.PresentationLastUpdate DESC
        ";
    }

    private function getPersonsForCommunicationSql(): string
    {
        return "
            SELECT DISTINCT i.Id, i.FirstName, i.LastName, i.Email
            " . $this->memberIndividualJoin() . "
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            WHERE i.Email != '' AND mg.IdGroup = :groupId
            ORDER BY i.FirstName, i.LastName
        ";
    }

    private function getPersonsInGroupSql(): string
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
            " . $this->memberIndividualJoin() . "
            WHERE m.Inactivated = 0 
            ORDER BY i.FirstName, i.LastName
        ";
    }

    private function getPersonsInGroupForDirectorySql(): string
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
            " . $this->memberIndividualJoin() . "
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            WHERE mg.IdGroup = ?
              AND m.InPresentationDirectory = 1
              AND m.Inactivated = 0
            ORDER BY i.FirstName, i.LastName
        ";
    }

    private function getRedactorsSql(): string
    {
        return "
            SELECT i.Id AS PersonId, i.FirstName, i.LastName, i.NickName, i.Email
            " . $this->memberIndividualJoin() . "
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            INNER JOIN GroupAuthorization ga ON ga.IdGroup = mg.IdGroup
            WHERE m.Inactivated = 0
              AND ga.IdAuthorization = 4
            GROUP BY i.Id
            ORDER BY i.FirstName, i.LastName
        ";
    }

    private function getWebmasterEmailSql(): string
    {
        return '
            SELECT i.Email
            ' . $this->memberIndividualJoin() . '
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            INNER JOIN "Group" g ON g.Id = mg.IdGroup
            INNER JOIN GroupAuthorization ga ON ga.IdGroup = g.Id
            INNER JOIN Authorization a ON a.Id = ga.IdAuthorization
            WHERE a.Name = "Webmaster"
        ';
    }
}
