<?php

declare(strict_types=1);

namespace tests\models;

use PDO;

class DesignDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Design', [
            'Id',
            'Name',
            'Detail',
            'NavBar',
            'Status',
            'OnlyForMembers',
            'IdGroup',
            'IdPerson',
            'LastUpdate',
        ]);

        $this->assertColumnsExist($pdo, 'DesignVote', [
            'Id',
            'IdDesign',
            'IdPerson',
            'Vote',
        ]);

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'FirstName',
            'LastName',
            'NickName',
            'Email',
            'Inactivated',
        ]);
    }

    public function testGetUsersVotesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUsersVotesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('NameOfDesigner', $sql);
        $this->assertStringContainsString('Votes', $sql);
        $this->assertStringContainsString('LEFT JOIN DesignVote', $sql);
        $this->assertStringContainsString('JOIN Person', $sql);
        $this->assertStringContainsString('GROUP BY d.Id', $sql);
    }

    public function testGetPendingDesignResponsesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPendingDesignResponsesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('CROSS JOIN Design', $sql);
        $this->assertStringContainsString('d.Status = \'UnderReview\'', $sql);
        $this->assertStringContainsString('dv.Id IS NULL', $sql);
        $this->assertStringContainsString('p.Inactivated = 0', $sql);
        $this->assertStringContainsString('ORDER BY d.LastUpdate', $sql);
    }

    private function getUsersVotesSql(): string
    {
        return "
            SELECT d.Id, d.Name, d.Detail, d.NavBar, d.Status, d.OnlyForMembers, d.IdGroup, 
                p.FirstName || ' ' || p.LastName || CASE WHEN p.NickName IS NOT NULL AND p.NickName != '' 
                THEN ' (' || p.NickName || ')' 
                ELSE '' 
                END AS NameOfDesigner,
                CASE WHEN COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) = 0 
                AND COUNT(CASE WHEN dv.Vote = 'voteDown' THEN 1 END) = 0
                AND COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) = 0
                THEN '0/0'
                ELSE COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) || ' / ' || 
                    (COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) + COUNT(CASE WHEN dv.Vote = 'voteDown' THEN 1 END)) ||
                CASE 
                WHEN COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) > 0 
                THEN ' (+' || COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) || ')' 
                ELSE '' 
                END
                END AS Votes
            FROM Design d
            LEFT JOIN DesignVote dv ON d.Id = dv.IdDesign
            JOIN Person p ON d.IdPerson = p.Id
            GROUP BY d.Id
        ";
    }

    private function getPendingDesignResponsesSql(): string
    {
        return "
            SELECT 
                p.Id AS PersonId, 
                p.Email, 
                d.Id AS DesignId, 
                d.Name AS DesignName,
                d.Detail AS DesignDetail
            FROM Person p
            CROSS JOIN Design d
            LEFT JOIN DesignVote dv ON dv.IdDesign = d.Id AND dv.IdPerson = p.Id
            WHERE p.Inactivated = 0
                AND d.Status = 'UnderReview'
                AND dv.Id IS NULL
            ORDER BY d.LastUpdate
        ";
    }
}
