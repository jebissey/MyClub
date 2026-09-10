<?php

declare(strict_types=1);

namespace tests\models;

use PDO;

class EventDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Summary',
            'Description',
            'Location',
            'StartTime',
            'Duration',
            'IdEventType',
            'CreatedBy',
            'MaxParticipants',
            'Audience',
            'LastUpdate',
            'Canceled',
        ]);

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
            'IdGroup',
            'Inactivated',
        ]);

        $this->assertColumnsExist($pdo, 'EventAttribute', [
            'IdEvent',
            'IdAttribute',
        ]);

        $this->assertColumnsExist($pdo, 'Attribute', [
            'Id',
            'Name',
            'Detail',
            'Color',
        ]);

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
        ]);

        $this->assertColumnsExist($pdo, 'PersonGroup', [
            'IdPerson',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Participant', [
            'Id',
            'IdEvent',
            'IdPerson',
        ]);

        $this->assertColumnsExist($pdo, 'ParticipantSupply', [
            'Id',
            'IdParticipant',
            'IdNeed',
            'Supply',
        ]);

        $this->assertColumnsExist($pdo, 'Need', [
            'Id',
            'Label',
            'Name',
            'ParticipantDependent',
            'IdNeedType',
        ]);

        $this->assertColumnsExist($pdo, 'EventNeed', [
            'IdEvent',
            'IdNeed',
            'Counter',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'Message', [
            'Id',
            'EventId',
            'From',
        ]);
    }

    public function testDuplicateSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();
        $sql = $this->getDuplicateSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Event', $sql);
        $this->assertStringContainsString('WHERE Id = :id', $sql);
    }

    public function testGetEventSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();
        $sql = $this->getEventSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('EventTypeName', $sql);
        $this->assertStringContainsString('INNER JOIN EventType', $sql);
    }

    public function testGetNextWeekEventsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();
        $sql = $this->getNextWeekEventsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('AttributeIds', $sql);
        $this->assertStringContainsString('GROUP_CONCAT', $sql);
        $this->assertStringContainsString('LEFT JOIN "Group"', $sql);
        $this->assertStringContainsString('et.Inactivated = 0', $sql);
    }

    public function testGetEventNeedsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();
        $sql = $this->getEventNeedsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('RequiredQuantity', $sql);
        $this->assertStringContainsString('ProvidedQuantity', $sql);
        $this->assertStringContainsString('ParticipantDependent', $sql);
    }

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();
        $sql = $this->getNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(\PDOStatement::class, $stmt);

        $this->assertStringContainsString('LEFT JOIN PersonGroup', $sql);
        $this->assertStringContainsString('e.LastUpdate >= :searchFrom', $sql);
    }

    private function getDuplicateSql(): string
    {
        return "
            SELECT
                Id, Summary, Description, Location, StartTime, Duration,
                IdEventType, CreatedBy, MaxParticipants, Audience
            FROM Event
            WHERE Id = :id
            LIMIT 1
        ";
    }

    private function getEventSql(): string
    {
        return "
            SELECT
                e.Id, e.Summary, e.Description, e.Location, e.StartTime, e.Duration,
                e.IdEventType, e.CreatedBy, e.MaxParticipants, e.Audience, e.LastUpdate, e.Canceled,
                et.Name AS EventTypeName
            FROM Event e
            INNER JOIN EventType et ON e.IdEventType = et.Id
            WHERE e.Id = :eventId
            LIMIT 1
        ";
    }

    private function getNextWeekEventsSql(): string
    {
        $sep = 'char(31)';

        return "
            SELECT
                e.Id,
                e.Summary,
                e.Description,
                e.Location,
                replace(e.StartTime, 'T', ' ') AS StartTime,
                e.Duration,
                e.IdEventType,
                e.Audience,
                et.Name AS EventTypeName,
                g.Name  AS GroupName,
                GROUP_CONCAT(a.Id,     $sep) AS AttributeIds,
                GROUP_CONCAT(a.Name,   $sep) AS AttributeNames,
                GROUP_CONCAT(a.Detail, $sep) AS AttributeDetails,
                GROUP_CONCAT(a.Color,  $sep) AS AttributeColors
            FROM Event e
            INNER JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN EventAttribute ea ON e.Id = ea.IdEvent
            LEFT JOIN Attribute a ON ea.IdAttribute = a.Id
            LEFT JOIN \"Group\" g ON et.IdGroup = g.Id
            WHERE datetime(replace(e.StartTime, 'T', ' ')) >= :start
            AND datetime(replace(e.StartTime, 'T', ' ')) < :end
            AND et.Inactivated = 0
            GROUP BY e.Id
            ORDER BY datetime(replace(e.StartTime, 'T', ' '))
        ";
    }

    private function getEventNeedsSql(): string
    {
        return "
            SELECT 
                n.Id,
                n.Label,
                n.Name,
                n.ParticipantDependent,
                en.Counter,
                CASE 
                    WHEN n.ParticipantDependent = 1 THEN 
                        (SELECT COUNT(*) FROM Participant WHERE IdEvent = ?)
                    ELSE 
                        COALESCE(en.Counter, 0)
                END as RequiredQuantity,
                COALESCE(SUM(ps.Supply), 0) as ProvidedQuantity
            FROM Need n
            INNER JOIN EventNeed en ON n.Id = en.IdNeed
            LEFT JOIN ParticipantSupply ps ON n.Id = ps.IdNeed 
                AND ps.IdParticipant IN (
                    SELECT Id FROM Participant WHERE IdEvent = ?
                )
            WHERE en.IdEvent = ?
            GROUP BY n.Id, n.Label, n.Name, n.ParticipantDependent, en.Counter
            ORDER BY n.IdNeedType, n.Name
        ";
    }

    private function getNewsSql(): string
    {
        return "
            SELECT e.Id, e.Summary, e.LastUpdate
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN PersonGroup pg 
                ON et.IdGroup = pg.IdGroup 
                AND pg.IdPerson = :personId
            WHERE e.LastUpdate >= :searchFrom
            AND (
                et.IdGroup IS NULL
                OR pg.IdPerson IS NOT NULL
            )
            ORDER BY e.LastUpdate DESC
        ";
    }
}
