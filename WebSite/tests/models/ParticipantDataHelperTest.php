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
            'IdPerson',
            'IdContact',
        ]);

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'Email',
            'NickName',
            'FirstName',
            'LastName',
            'InPresentationDirectory',
        ]);

        $this->assertColumnsExist($pdo, 'Contact', [
            'Id',
            'Email',
            'NickName',
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
        $this->assertStringContainsString('LEFT JOIN Person pe ON pa.IdPerson = pe.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN Contact c ON pa.IdContact = c.Id', $sql);
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

        $this->assertStringContainsString('SELECT LOWER(p.Email) as Email, COUNT(pa.Id) as ParticipationCount', $sql);
        $this->assertStringContainsString('FROM Participant pa', $sql);
        $this->assertStringContainsString('JOIN Person p ON p.Id = pa.IdPerson', $sql);
        $this->assertStringContainsString('JOIN Event e ON e.Id = pa.IdEvent', $sql);
        $this->assertStringContainsString('WHERE e.StartTime BETWEEN :start AND :end', $sql);
        $this->assertStringContainsString('AND e.Canceled = 0', $sql);
        $this->assertStringContainsString('AND pa.IdPerson IS NOT NULL', $sql);
        $this->assertStringContainsString('GROUP BY p.Email', $sql);
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
        $this->assertStringContainsString('WHERE p1.IdPerson = :idPerson', $connectionsSql);
        $this->assertStringContainsString('AND p2.IdPerson != :idPerson', $connectionsSql);
        $this->assertStringContainsString('JOIN Event e ON e.Id = common.IdEvent', $connectionsSql);
        $this->assertStringContainsString('JOIN Person person ON person.Id = common.IdPerson', $connectionsSql);
        $this->assertStringContainsString('GROUP BY common.IdPerson', $connectionsSql);
        $this->assertStringContainsString('ORDER BY CommonEvents DESC', $connectionsSql);

        $this->assertStringContainsString('SELECT', $personsSql);
        $this->assertStringContainsString('FROM Person', $personsSql);
    }

    private function getGetEventParticipantsSql(): string
    {
        return "
            SELECT
                COALESCE(pe.Email, c.Email) AS Email,
                COALESCE(pe.NickName, c.NickName) AS NickName,
                pe.FirstName,
                pe.LastName,
                pe.Id AS PersonId,
                pe.InPresentationDirectory,
                c.Id AS ContactId
            FROM Participant pa
            LEFT JOIN Person pe ON pa.IdPerson = pe.Id
            LEFT JOIN Contact c ON pa.IdContact = c.Id
            INNER JOIN Event e ON pa.IdEvent = e.Id
            WHERE pa.IdEvent = :eventId
                AND e.Canceled = 0
            ORDER BY pe.FirstName, pe.LastName, c.NickName
        ";
    }

    private function getGetParticipationsSql(): string
    {
        return "
            SELECT LOWER(p.Email) as Email, COUNT(pa.Id) as ParticipationCount
            FROM Participant pa
            JOIN Person p ON p.Id = pa.IdPerson
            JOIN Event e ON e.Id = pa.IdEvent
            WHERE e.StartTime BETWEEN :start AND :end
            AND e.Canceled = 0
            AND pa.IdPerson IS NOT NULL
            GROUP BY p.Email
        ";
    }

    private function getGetConnectionsSql(): string
    {
        return "
            SELECT 
                common.IdPerson AS OtherPerson,
                CASE 
                    WHEN person.InPresentationDirectory = 1 THEN common.IdPerson 
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
                    p2.IdPerson
                FROM Participant p1
                JOIN Participant p2 ON p1.IdEvent = p2.IdEvent
                WHERE p1.IdPerson = :idPerson
                AND p2.IdPerson != :idPerson
            ) AS common
            JOIN Event e ON e.Id = common.IdEvent
            JOIN Person person ON person.Id = common.IdPerson
            GROUP BY common.IdPerson
            ORDER BY CommonEvents DESC
        ";
    }

    private function getGetPersonsSql(): string
    {
        return "
            SELECT 
                Id,     
                FirstName || ' ' || LastName || 
                    CASE 
                        WHEN NickName != '' THEN ' (' || NickName || ')' 
                        ELSE '' 
                    END AS Name
            FROM Person
        ";
    }
}