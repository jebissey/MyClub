<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class EventListDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Summary',
            'Description',
            'Location',
            'StartTime',
            'Duration',
            'IdEventType',
            'Audience',
            'CreatedBy',
            'MaxParticipants',
            'Canceled',
        ]);

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
            'IdGroup',
            'Inactivated',
        ]);

        $this->assertColumnsExist($pdo, 'EventAttribute', ['IdEvent', 'IdAttribute']);
        $this->assertColumnsExist($pdo, 'Attribute', ['Id', 'Name', 'Detail', 'Color']);
        $this->assertColumnsExist($pdo, 'Participant', ['Id', 'IdEvent', 'IdIndividual']);
        $this->assertColumnsExist($pdo, 'Message', ['Id', 'EventId', 'From']);
        $this->assertColumnsExist($pdo, 'MemberGroup', ['IdGroup', 'IdMember']);
        $this->assertColumnsExist($pdo, 'Group', ['Id', 'Name']);
        $this->assertColumnsExist($pdo, 'Member', ['Id']);
        $this->assertColumnsExist($pdo, 'Individual', ['Id', 'FirstName', 'LastName', 'NickName']);
    }

    public function testQueriesAreValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        foreach ([
            // getAttributesForNextWeekEvents
            "SELECT DISTINCT a.Id, a.Name, a.Detail, a.Color
             FROM Event e
             INNER JOIN EventType et ON e.IdEventType = et.Id
             LEFT JOIN EventAttribute ea ON e.Id = ea.IdEvent
             LEFT JOIN Attribute a ON ea.IdAttribute = a.Id
             WHERE datetime(e.StartTime) >= :startOfWeek
               AND datetime(e.StartTime) < :endOfWeek
               AND a.Id IS NOT NULL
             ORDER BY e.StartTime, a.Id",
            // getEventsForAllOrGuest
            "SELECT e.Id, e.Summary, e.StartTime,
                    CASE WHEN i.NickName != '' AND i.NickName IS NOT NULL
                         THEN i.FirstName || ' ' || i.LastName || ' (' || i.NickName || ')'
                         ELSE i.FirstName || ' ' || i.LastName END AS PersonName
             FROM Event e
             INNER JOIN Member mb ON mb.Id = e.CreatedBy
             INNER JOIN Individual i ON i.Id = mb.Id
             WHERE e.StartTime > :today AND (e.Audience = 'All' OR e.Audience = 'Guest')
             ORDER BY e.StartTime ASC",
            // getNextEvents / getPassedEvents (common part)
            "SELECT e.*, et.Name AS EventTypeName, et.IdGroup AS EventTypeIdGroup,
                    p.Id AS Booked, COUNT(m.Id) AS MessageCount
             FROM Event e
             LEFT JOIN EventType et ON e.IdEventType = et.Id
             LEFT JOIN Participant p ON e.Id = p.IdEvent AND p.IdIndividual = :personId
             LEFT JOIN Message m ON m.EventId = e.Id AND m.\"From\" = 'User'
             WHERE datetime(replace(e.StartTime, 'T', ' ')) > DATETIME('now')
               AND et.Inactivated = 0
               AND (et.IdGroup IS NULL OR et.IdGroup IN (
                   SELECT IdGroup FROM MemberGroup WHERE IdMember = :personId))
             GROUP BY e.Id",
            // getNextWeekEvents
            "SELECT e.Id, e.Summary, e.Description, e.Location,
                    replace(e.StartTime, 'T', ' ') AS StartTime, e.Duration,
                    e.IdEventType, e.Audience, et.Name AS EventTypeName, g.Name AS GroupName,
                    GROUP_CONCAT(a.Id, char(31)) AS AttributeIds
             FROM Event e
             INNER JOIN EventType et ON e.IdEventType = et.Id
             LEFT JOIN EventAttribute ea ON e.Id = ea.IdEvent
             LEFT JOIN Attribute a ON ea.IdAttribute = a.Id
             LEFT JOIN \"Group\" g ON et.IdGroup = g.Id
             WHERE datetime(replace(e.StartTime, 'T', ' ')) >= :start
               AND datetime(replace(e.StartTime, 'T', ' ')) < :end
               AND et.Inactivated = 0
             GROUP BY e.Id",
            // events(): attributes
            "SELECT ea.IdEvent, a.Id, a.Name, a.Detail, a.Color
             FROM EventAttribute ea
             JOIN Attribute a ON ea.IdAttribute = a.Id
             WHERE ea.IdEvent IN (?,?)",
            // getEventMessagesCount
            'SELECT COUNT(Id) FROM Message m WHERE m.EventId = :eventId AND m."From" = :from',
        ] as $sql) {
            $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($sql), $sql);
        }
    }
}