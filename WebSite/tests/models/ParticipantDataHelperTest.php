<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class ParticipantDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Participant', [
            'Id',
            'IdEvent',
            'IdIndividual',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Type',
            'Email',
            'NickName',
            'FirstName',
            'LastName',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'InPresentationDirectory',
        ]);

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'StartTime',
            'Summary',
            'Canceled',
        ]);
    }

    public function testGetEventParticipantsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetEventParticipantsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Participant pa', $sql);
        $this->assertStringContainsString('INNER JOIN Individual i ON pa.IdIndividual = i.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN Member m ON m.Id = i.Id', $sql);
        $this->assertStringContainsString('INNER JOIN Event e ON pa.IdEvent = e.Id', $sql);
        $this->assertStringContainsString('WHERE pa.IdEvent = :eventId', $sql);
        $this->assertStringContainsString('AND e.Canceled = 0', $sql);
    }

    public function testGetParticipationsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetParticipationsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT LOWER(i.Email) as Email, COUNT(pa.Id) as ParticipationCount', $sql);
        $this->assertStringContainsString('FROM Participant pa', $sql);
        $this->assertStringContainsString('JOIN Individual i ON i.Id = pa.IdIndividual', $sql);
        $this->assertStringContainsString('INNER JOIN Member m ON m.Id = i.Id', $sql);
        $this->assertStringContainsString('JOIN Event e ON e.Id = pa.IdEvent', $sql);
        $this->assertStringContainsString('WHERE e.StartTime BETWEEN :start AND :end', $sql);
        $this->assertStringContainsString('AND e.Canceled = 0', $sql);
        $this->assertStringContainsString('GROUP BY i.Email', $sql);
    }

    public function testGetConnectionsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $connectionsSql = $this->getGetConnectionsSql();
        $personsSql = $this->getGetPersonsSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($connectionsSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($personsSql));

        $this->assertStringContainsString('FROM (', $connectionsSql);
        $this->assertStringContainsString('FROM Participant p1', $connectionsSql);
        $this->assertStringContainsString('JOIN Participant p2 ON p1.IdEvent = p2.IdEvent', $connectionsSql);
        $this->assertStringContainsString('WHERE p1.IdIndividual = :idPerson', $connectionsSql);
        $this->assertStringContainsString('AND p2.IdIndividual != :idPerson', $connectionsSql);
        $this->assertStringContainsString('JOIN Event e ON e.Id = common.IdEvent', $connectionsSql);
        $this->assertStringContainsString('JOIN Individual individual ON individual.Id = common.IdIndividual', $connectionsSql);
        $this->assertStringContainsString('INNER JOIN Member member ON member.Id = individual.Id', $connectionsSql);
        $this->assertStringContainsString('GROUP BY common.IdIndividual', $connectionsSql);
        $this->assertStringContainsString('ORDER BY CommonEvents DESC', $connectionsSql);

        $this->assertStringContainsString('SELECT', $personsSql);
        $this->assertStringContainsString('FROM Individual i', $personsSql);
        $this->assertStringContainsString('INNER JOIN Member m ON m.Id = i.Id', $personsSql);
    }

    private function getGetEventParticipantsSql(): string
    {
        return "
            SELECT
                i.Email,
                i.NickName,
                i.FirstName,
                i.LastName,
                CASE WHEN i.Type = 'Member' THEN i.Id ELSE NULL END AS PersonId,
                m.InPresentationDirectory,
                CASE WHEN i.Type = 'Contact' THEN i.Id ELSE NULL END AS ContactId
            FROM Participant pa
            INNER JOIN Individual i ON pa.IdIndividual = i.Id
            LEFT JOIN Member m ON m.Id = i.Id
            INNER JOIN Event e ON pa.IdEvent = e.Id
            WHERE pa.IdEvent = :eventId
                AND e.Canceled = 0
            ORDER BY i.FirstName, i.LastName, i.NickName
        ";
    }

    private function getGetParticipationsSql(): string
    {
        return "
            SELECT LOWER(i.Email) as Email, COUNT(pa.Id) as ParticipationCount
            FROM Participant pa
            JOIN Individual i ON i.Id = pa.IdIndividual
            INNER JOIN Member m ON m.Id = i.Id
            JOIN Event e ON e.Id = pa.IdEvent
            WHERE e.StartTime BETWEEN :start AND :end
            AND e.Canceled = 0
            GROUP BY i.Email
        ";
    }

    private function getGetConnectionsSql(): string
    {
        return "
            SELECT 
                common.IdIndividual AS OtherPerson,
                CASE 
                    WHEN member.InPresentationDirectory = 1 THEN common.IdIndividual 
                    ELSE 0 
                END AS OtherPersonInPresentationDirectory,
                GROUP_CONCAT(
                    e.Id || '|' || e.StartTime || '|' || e.Summary,
                    ' • '
                ) AS EventList,
                COUNT(DISTINCT e.Id) AS CommonEvents
            FROM (
                SELECT 
                    p1.IdEvent,
                    p2.IdIndividual
                FROM Participant p1
                JOIN Participant p2 ON p1.IdEvent = p2.IdEvent
                WHERE p1.IdIndividual = :idPerson
                AND p2.IdIndividual != :idPerson
            ) AS common
            JOIN Event e ON e.Id = common.IdEvent
            JOIN Individual individual ON individual.Id = common.IdIndividual
            INNER JOIN Member member ON member.Id = individual.Id
            GROUP BY common.IdIndividual
            ORDER BY CommonEvents DESC
        ";
    }

    private function getGetPersonsSql(): string
    {
        return "
            SELECT 
                i.Id,     
                i.FirstName || ' ' || i.LastName || 
                    CASE 
                        WHEN i.NickName != '' THEN ' (' || i.NickName || ')' 
                        ELSE '' 
                    END AS Name
            FROM Individual i
            INNER JOIN Member m ON m.Id = i.Id
        ";
    }
}