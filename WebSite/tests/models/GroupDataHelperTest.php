<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class GroupDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
            'SelfRegistration',
        ]);

        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'Id',
            'IdGroup',
            'IdMember',
        ]);

        $this->assertColumnsExist($pdo, 'GroupAuthorization', [
            'IdGroup',
            'IdAuthorization',
        ]);

        $this->assertColumnsExist($pdo, 'Authorization', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Inactivated',
            'InPresentationDirectory',
        ]);
    }

    public function testGetAvailableGroupsCurrentGroupsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAvailableGroupsCurrentGroupsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('GROUP_CONCAT(a.Name) AS Authorizations', $sql);
        $this->assertStringContainsString('INNER JOIN MemberGroup', $sql);
        $this->assertStringContainsString('LEFT JOIN GroupAuthorization', $sql);
        $this->assertStringContainsString('LEFT JOIN Authorization', $sql);
        $this->assertStringContainsString('g.Inactivated = 0', $sql);
        $this->assertStringContainsString('g.Id <> 1', $sql);
        $this->assertStringContainsString('g.SelfRegistration = 0', $sql);
        $this->assertStringContainsString('GROUP BY g.Id, g.Name', $sql);
    }

    public function testGetAvailableGroupsLeftSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAvailableGroupsLeftSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('WHERE ag.Id NOT IN', $sql);
        $this->assertStringContainsString('INNER JOIN MemberGroup', $sql);
        $this->assertStringContainsString('mg.IdMember = ?', $sql);
    }

    public function testGetCurrentGroupsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCurrentGroupsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('CASE WHEN mg.Id IS NOT NULL THEN 1 ELSE 0 END as isMember', $sql);
        $this->assertStringContainsString('g.SelfRegistration as canToggle', $sql);
        $this->assertStringContainsString('LEFT JOIN MemberGroup', $sql);
        $this->assertStringContainsString('g.Inactivated = 0', $sql);
        $this->assertStringContainsString('ORDER BY g.SelfRegistration DESC, g.Name', $sql);
    }

    public function testGetGroupsWithAuthorizationsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGroupsWithAuthorizationsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('g.SelfRegistration', $sql);
        $this->assertStringContainsString('Authorizations', $sql);
        $this->assertStringContainsString('LEFT JOIN GroupAuthorization', $sql);
        $this->assertStringContainsString('LEFT JOIN Authorization', $sql);
        $this->assertStringContainsString('g.Inactivated = 0', $sql);
        $this->assertStringContainsString('GROUP BY g.Id, g.Name, g.SelfRegistration', $sql);
        $this->assertStringContainsString('ORDER BY g.Name', $sql);
    }

    public function testGetGroupsWithTypeSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGroupsWithTypeSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString("'joined'", $sql);
        $this->assertStringContainsString("'subscribed'", $sql);
        $this->assertStringContainsString('LEFT JOIN MemberGroup', $sql);
        $this->assertStringContainsString('g.Inactivated = 0', $sql);
        $this->assertStringContainsString('ORDER BY Type, g.Name', $sql);
    }

    public function testGetGroupCountSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGroupCountSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('COUNT(DISTINCT mg.IdMember) as Total', $sql);
        $this->assertStringContainsString('WithPresentation', $sql);
        $this->assertStringContainsString('JOIN MemberGroup', $sql);
        $this->assertStringContainsString('JOIN Member', $sql);
        $this->assertStringContainsString('m.Inactivated = 0', $sql);
        $this->assertStringContainsString('GROUP BY g.Id, g.Name', $sql);
    }

    private function getAvailableGroupsCurrentGroupsSql(): string
    {
        return "
            SELECT 
                g.Id,
                g.Name,
                GROUP_CONCAT(a.Name) AS Authorizations
            FROM `Group` g
            INNER JOIN MemberGroup mg ON mg.IdGroup = g.Id
            LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
            LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
            WHERE mg.IdMember = ?
            AND g.Inactivated = 0
            AND g.Id <> 1
            AND g.SelfRegistration = 0
            GROUP BY g.Id, g.Name
";
    }

    private function getAvailableGroupsLeftSql(): string
    {
        $availableGroupsQuery = "
            SELECT 
                g.Id,
                g.Name,
                GROUP_CONCAT(a.Name) AS Authorizations
            FROM `Group` g
            LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
            LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
            WHERE g.Inactivated = 0
            AND g.SelfRegistration = 0
            AND g.Id <> 1
            GROUP BY g.Id, g.Name
";

        return "
            SELECT ag.*
            FROM (
$availableGroupsQuery
            ) ag
            WHERE ag.Id NOT IN (
                SELECT g.Id
                FROM `Group` g
                INNER JOIN MemberGroup mg ON g.Id = mg.IdGroup
                WHERE mg.IdMember = ?
            )
";
    }

    private function getCurrentGroupsSql(): string
    {
        return '
            SELECT g.*, 
                CASE WHEN mg.Id IS NOT NULL THEN 1 ELSE 0 END as isMember,
                g.SelfRegistration as canToggle
            FROM `Group` g 
            LEFT JOIN MemberGroup mg ON mg.IdGroup = g.Id AND mg.IdMember = ?
            WHERE g.Inactivated = 0 AND (g.SelfRegistration = 1 OR mg.Id IS NOT NULL)
            ORDER BY g.SelfRegistration DESC, g.Name';
    }

    private function getGroupsWithAuthorizationsSql(): string
    {
        return "
            SELECT 
                g.Id,
                g.Name,
                g.SelfRegistration,
                REPLACE(
                    (
                        SELECT GROUP_CONCAT(Name)
                        FROM (
                            SELECT a2.Name
                            FROM Authorization a2
                            JOIN GroupAuthorization ga2 ON ga2.IdAuthorization = a2.Id
                            WHERE ga2.IdGroup = g.Id
                            ORDER BY a2.Name
                        )
                    ),
                    ',', ', '
                ) AS Authorizations                
            FROM `Group` g
            LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
            LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
            WHERE g.Inactivated = 0
            GROUP BY g.Id, g.Name, g.SelfRegistration
            ORDER BY g.Name
";
    }

    private function getGroupsWithTypeSql(): string
    {
        return "
            SELECT 
                g.Id,
                g.Name,
                CASE
                    WHEN mg.Id IS NOT NULL AND g.SelfRegistration = 1 THEN 'joined'
                    WHEN mg.Id IS NOT NULL AND g.SelfRegistration = 0 THEN 'subscribed'
                    ELSE ''
                END AS Type
            FROM `Group` g
            LEFT JOIN MemberGroup mg 
                ON mg.IdGroup = g.Id 
               AND mg.IdMember = :idPerson
            WHERE 
                (g.SelfRegistration = 1 OR mg.Id IS NOT NULL) AND g.Inactivated = 0        
            ORDER BY Type, g.Name
";
    }

    private function getGroupCountSql(): string
    {
        return "
            SELECT 
                g.Id, 
                g.Name,
                COUNT(DISTINCT mg.IdMember) as Total,
                COUNT(DISTINCT CASE WHEN m.InPresentationDirectory = 1 THEN mg.IdMember END) as WithPresentation
            FROM `Group` g
            JOIN MemberGroup mg ON g.Id = mg.IdGroup
            JOIN Member m ON mg.IdMember = m.Id
            WHERE m.Inactivated = 0
            GROUP BY g.Id, g.Name
";
    }
}