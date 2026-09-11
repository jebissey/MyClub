<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class PersonStatisticsDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'LastUpdate',
            'CreatedBy',
        ]);

        $this->assertColumnsExist($pdo, 'Survey', [
            'Id',
            'IdArticle',
        ]);

        $this->assertColumnsExist($pdo, 'Reply', [
            'Id',
            'IdPerson',
            'IdSurvey',
        ]);

        $this->assertColumnsExist($pdo, 'Design', [
            'Id',
            'LastUpdate',
            'IdPerson',
        ]);

        $this->assertColumnsExist($pdo, 'DesignVote', [
            'IdDesign',
            'IdPerson',
        ]);

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'IdEventType',
            'StartTime',
            'CreatedBy',
        ]);

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'Guest', [
            'IdEvent',
            'InvitedBy',
        ]);

        $this->assertColumnsExist($pdo, 'Participant', [
            'Id',
            'IdEvent',
            'IdPerson',
        ]);

        $this->assertColumnsExist($pdo, 'ParticipantSupply', [
            'IdParticipant',
        ]);

        $this->assertColumnsExist($pdo, 'Message', [
            'EventId',
            'PersonId',
            'From',
        ]);
    }

    public function testGetAvailableSeasonsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getAvailableSeasonsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT MIN(LastUpdate) as min_date FROM', $sql);
        $this->assertStringContainsString('SELECT MIN(LastUpdate) as LastUpdate FROM Article', $sql);
        $this->assertStringContainsString('SELECT MIN(StartTime) as LastUpdate FROM Event', $sql);
    }

    public function testGetArticleCountSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getArticleCountSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM Article', $sql);
        $this->assertStringContainsString('WHERE LastUpdate BETWEEN :seasonStart AND :seasonEnd', $sql);
        $this->assertStringContainsString('AND CreatedBy = :personId', $sql);
    }

    public function testGetSurveyStatsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $userSql = $this->getUserSurveyStatsSql();
        $totalSql = $this->getTotalSurveyStatsSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($userSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($totalSql));

        $this->assertStringContainsString('SELECT COUNT(s.Id) as count', $userSql);
        $this->assertStringContainsString('FROM Survey s', $userSql);
        $this->assertStringContainsString('JOIN Article a ON s.IdArticle = a.Id', $userSql);
        $this->assertStringContainsString('WHERE a.CreatedBy = ?', $userSql);

        $this->assertStringContainsString('SELECT COUNT(*) as count', $totalSql);
        $this->assertStringContainsString('FROM Survey', $totalSql);
        $this->assertStringContainsString('WHERE IdArticle IN', $totalSql);
    }

    public function testGetSurveyRepliesStatsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $userSql = $this->getUserSurveyRepliesStatsSql();
        $totalSql = $this->getTotalSurveyRepliesStatsSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($userSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($totalSql));

        $this->assertStringContainsString('SELECT COUNT(*) as count', $userSql);
        $this->assertStringContainsString('FROM Reply', $userSql);
        $this->assertStringContainsString('WHERE IdPerson = ?', $userSql);

        $this->assertStringContainsString('SELECT COUNT(*) as count', $totalSql);
        $this->assertStringContainsString('FROM Reply', $totalSql);
    }

    public function testGetDesignCountSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDesignCountSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM Design', $sql);
        $this->assertStringContainsString('WHERE datetime(LastUpdate) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)', $sql);
        $this->assertStringContainsString('AND IdPerson = :personId', $sql);
    }

    public function testGetDesignVoteCountSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDesignVoteCountSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM DesignVote dv', $sql);
        $this->assertStringContainsString('INNER JOIN Design d ON dv.IdDesign = d.Id', $sql);
        $this->assertStringContainsString('WHERE datetime(d.LastUpdate) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)', $sql);
        $this->assertStringContainsString('AND dv.IdPerson = :personId', $sql);
    }

    public function testGetEventStatsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $byTypeSql = $this->getEventStatsByTypeSql();
        $totalSql = $this->getEventStatsTotalSql();
        $invitationSql = $this->getEventStatsInvitationSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($byTypeSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($totalSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($invitationSql));

        $this->assertStringContainsString('FROM EventType et', $byTypeSql);
        $this->assertStringContainsString('LEFT JOIN Event e ON e.IdEventType = et.Id', $byTypeSql);
        $this->assertStringContainsString('SUM(CASE WHEN e.CreatedBy = ? THEN 1 ELSE 0 END) AS user', $byTypeSql);

        $this->assertStringContainsString('FROM Event', $totalSql);
        $this->assertStringContainsString('WHERE datetime(StartTime) BETWEEN datetime(?) AND datetime(?)', $totalSql);

        $this->assertStringContainsString('FROM Guest g', $invitationSql);
        $this->assertStringContainsString('INNER JOIN Event e ON e.Id = g.IdEvent', $invitationSql);
        $this->assertStringContainsString('SUM(CASE WHEN g.InvitedBy = ? THEN 1 ELSE 0 END) AS user', $invitationSql);
    }

    public function testGetEventParticipationStatsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $eventTypesSql = $this->getEventTypesSql();
        $participationSql = $this->getEventParticipationSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($eventTypesSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($participationSql));

        $this->assertStringContainsString('SELECT Id, Name FROM EventType', $eventTypesSql);

        $this->assertStringContainsString('FROM Participant p', $participationSql);
        $this->assertStringContainsString('INNER JOIN Event e ON p.IdEvent = e.Id', $participationSql);
        $this->assertStringContainsString('INNER JOIN EventType et ON e.IdEventType = et.Id', $participationSql);
        $this->assertStringContainsString('WHERE datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', $participationSql);
    }

    public function testGetParticipantSupplyCountSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getParticipantSupplyCountSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM ParticipantSupply ps', $sql);
        $this->assertStringContainsString('INNER JOIN Participant p ON ps.IdParticipant = p.Id', $sql);
        $this->assertStringContainsString('INNER JOIN Event e ON p.IdEvent = e.Id', $sql);
        $this->assertStringContainsString('WHERE datetime(e.StartTime) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)', $sql);
        $this->assertStringContainsString('AND p.IdPerson = :personId', $sql);
    }

    public function testGetParticipantMessageCountSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getParticipantMessageCountSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM Message m', $sql);
        $this->assertStringContainsString('INNER JOIN Event e ON m.EventId = e.Id', $sql);
        $this->assertStringContainsString('WHERE datetime(e.StartTime) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)', $sql);
        $this->assertStringContainsString('AND "From" = :from', $sql);
        $this->assertStringContainsString('AND m.PersonId = :personId', $sql);
    }

    private function getAvailableSeasonsSql(): string
    {
        return "
            SELECT MIN(LastUpdate) as min_date FROM (
                SELECT MIN(LastUpdate) as LastUpdate FROM Article
                UNION
                SELECT MIN(StartTime) as LastUpdate FROM Event
            )
        ";
    }

    private function getArticleCountSql(): string
    {
        return "
            SELECT COUNT(*)
            FROM Article
            WHERE LastUpdate BETWEEN :seasonStart AND :seasonEnd
            AND CreatedBy = :personId
        ";
    }

    private function getUserSurveyStatsSql(): string
    {
        return "
            SELECT COUNT(s.Id) as count 
            FROM Survey s
            JOIN Article a ON s.IdArticle = a.Id
            WHERE a.CreatedBy = ? 
            AND s.Id IN (
                SELECT Id FROM Survey WHERE IdArticle IN (
                    SELECT Id FROM Article WHERE LastUpdate BETWEEN ? AND ?
                )
            )
        ";
    }

    private function getTotalSurveyStatsSql(): string
    {
        return "
            SELECT COUNT(*) as count 
            FROM Survey 
            WHERE IdArticle IN (
                SELECT Id FROM Article WHERE LastUpdate BETWEEN ? AND ?
            )
        ";
    }

    private function getUserSurveyRepliesStatsSql(): string
    {
        return "
            SELECT COUNT(*) as count 
            FROM Reply 
            WHERE IdPerson = ? 
            AND Id IN (
                SELECT r.Id FROM Reply r
                JOIN Survey s ON r.IdSurvey = s.Id
                JOIN Article a ON s.IdArticle = a.Id
                WHERE a.LastUpdate BETWEEN ? AND ?
            )
        ";
    }

    private function getTotalSurveyRepliesStatsSql(): string
    {
        return "
            SELECT COUNT(*) as count 
            FROM Reply 
            WHERE Id IN (
                SELECT r.Id FROM Reply r
                JOIN Survey s ON r.IdSurvey = s.Id
                JOIN Article a ON s.IdArticle = a.Id
                WHERE a.LastUpdate BETWEEN ? AND ?
            )
        ";
    }

    private function getDesignCountSql(): string
    {
        return "
            SELECT COUNT(*)
            FROM Design
            WHERE datetime(LastUpdate) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)
            AND IdPerson = :personId
        ";
    }

    private function getDesignVoteCountSql(): string
    {
        return "
            SELECT COUNT(*)
            FROM DesignVote dv
            INNER JOIN Design d ON dv.IdDesign = d.Id
            WHERE datetime(d.LastUpdate) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)
            AND dv.IdPerson = :personId
        ";
    }

    private function getEventStatsByTypeSql(): string
    {
        return "
            SELECT 
                et.Id,
                et.Name,
                COUNT(e.Id) AS total,
                SUM(CASE WHEN e.CreatedBy = ? THEN 1 ELSE 0 END) AS user
            FROM EventType et
            LEFT JOIN Event e ON e.IdEventType = et.Id 
                AND datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
            GROUP BY et.Id, et.Name
        ";
    }

    private function getEventStatsTotalSql(): string
    {
        return "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN CreatedBy = ? THEN 1 ELSE 0 END) AS user
            FROM Event 
            WHERE datetime(StartTime) BETWEEN datetime(?) AND datetime(?)
        ";
    }

    private function getEventStatsInvitationSql(): string
    {
        return "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN g.InvitedBy = ? THEN 1 ELSE 0 END) AS user
            FROM Guest g
            INNER JOIN Event e ON e.Id = g.IdEvent
            WHERE datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
        ";
    }

    private function getEventTypesSql(): string
    {
        return 'SELECT Id, Name FROM EventType';
    }

    private function getEventParticipationSql(): string
    {
        return "
            SELECT 
                e.IdEventType,
                et.Name AS typeName,
                COUNT(CASE WHEN p.IdPerson = ? THEN 1 END) AS user_count,
                COUNT(*) AS total_users_count,
                COUNT(DISTINCT e.Id) AS event_count
            FROM Participant p
            INNER JOIN Event e ON p.IdEvent = e.Id
            INNER JOIN EventType et ON e.IdEventType = et.Id
            WHERE datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
            GROUP BY e.IdEventType, et.Name";
    }

    private function getParticipantSupplyCountSql(): string
    {
        return "
            SELECT COUNT(*)
            FROM ParticipantSupply ps
            INNER JOIN Participant p ON ps.IdParticipant = p.Id
            INNER JOIN Event e ON p.IdEvent = e.Id
            WHERE datetime(e.StartTime) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)
            AND p.IdPerson = :personId
        ";
    }

    private function getParticipantMessageCountSql(): string
    {
        return "
            SELECT COUNT(*)
            FROM Message m
            INNER JOIN Event e ON m.EventId = e.Id
            WHERE datetime(e.StartTime) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)
            AND \"From\" = :from
            AND m.PersonId = :personId
        ";
    }
}
