<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class MessageDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Message', [
            'Id',
            'ArticleId',
            'EventId',
            'GroupId',
            'PersonId',
            'Text',
            'From',
            'ImagePath',
            'LastUpdate',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'FirstName',
            'LastName',
            'NickName',
            'Avatar',
            'Email',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'UseGravatar',
        ]);

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Summary',
            'StartTime',
            'IdEventType',
        ]);

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Inactivated',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Title',
            'PublishedBy',
            'CreatedBy',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'SelfRegistration',
            'Inactivated',
        ]);

        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'Id',
            'IdGroup',
            'IdMember',
        ]);

        $this->assertColumnsExist($pdo, 'Participant', [
            'IdEvent',
            'IdIndividual',
        ]);

        $logPdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);

        $this->assertColumnsExist($logPdo, 'Log', [
            'Id',
            'CreatedAt',
            'Uri',
        ]);
    }

    public function testGetArticleMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getArticleMessagesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('Message.*,', $sql);
        $this->assertStringContainsString('Individual.FirstName,', $sql);
        $this->assertStringContainsString('Individual.Email', $sql);
        $this->assertStringContainsString('FROM Message', $sql);
        $this->assertStringContainsString('LEFT JOIN Member     ON Message.PersonId = Member.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN Individual ON Member.Id = Individual.Id', $sql);
        $this->assertStringContainsString('WHERE Message.ArticleId = :articleId', $sql);
        $this->assertStringContainsString('ORDER BY Message.Id ASC', $sql);
    }

    public function testGetEventMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getEventMessagesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('Message.*,', $sql);
        $this->assertStringContainsString('FROM Message', $sql);
        $this->assertStringContainsString('LEFT JOIN Member     ON Message.PersonId = Member.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN Individual ON Member.Id = Individual.Id', $sql);
        $this->assertStringContainsString('WHERE Message.EventId = :eventId AND Message."From" = \'User\'', $sql);
        $this->assertStringContainsString('ORDER BY Message.Id ASC', $sql);
    }

    public function testGetGroupMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGroupMessagesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('Message.Id,', $sql);
        $this->assertStringContainsString('Message.PersonId,', $sql);
        $this->assertStringContainsString('Message.Text,', $sql);
        $this->assertStringContainsString('Message.ImagePath,', $sql);
        $this->assertStringContainsString('Message.LastUpdate,', $sql);
        $this->assertStringContainsString('FROM Message', $sql);
        $this->assertStringContainsString('LEFT JOIN Member     ON Message.PersonId = Member.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN Individual ON Member.Id = Individual.Id', $sql);
        $this->assertStringContainsString('WHERE Message.GroupId = :groupId', $sql);
        $this->assertStringContainsString('ORDER BY Message.Id ASC', $sql);
    }

    public function testGetMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getMessagesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT LOWER(i.Email) AS Email, COUNT(m.Id) AS MessageCount', $sql);
        $this->assertStringContainsString('FROM Message m', $sql);
        $this->assertStringContainsString('JOIN Member     mb ON mb.Id = m.PersonId', $sql);
        $this->assertStringContainsString('JOIN Individual i  ON i.Id  = mb.Id', $sql);
        $this->assertStringContainsString('WHERE m.LastUpdate BETWEEN :start AND :end', $sql);
        $this->assertStringContainsString('GROUP BY i.Email', $sql);
    }

    public function testGetMessageUsesEventsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getMessageUsesEventsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT DISTINCT e.Id, e.Summary AS Title, e.StartTime', $sql);
        $this->assertStringContainsString('FROM Message m', $sql);
        $this->assertStringContainsString('JOIN Event e ON e.Id = m.EventId', $sql);
        $this->assertStringContainsString('WHERE m.EventId IS NOT NULL', $sql);
        $this->assertStringContainsString('AND m.ImagePath LIKE :path', $sql);
        $this->assertStringContainsString('ORDER BY e.StartTime DESC', $sql);
    }

    public function testGetMessageUsesArticlesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getMessageUsesArticlesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT DISTINCT a.Id, a.Title', $sql);
        $this->assertStringContainsString('FROM Message m', $sql);
        $this->assertStringContainsString('JOIN Article a ON a.Id = m.ArticleId', $sql);
        $this->assertStringContainsString('WHERE m.ArticleId IS NOT NULL', $sql);
        $this->assertStringContainsString('AND m.ImagePath LIKE :path', $sql);
        $this->assertStringContainsString('ORDER BY a.Title ASC', $sql);
    }

    public function testGetMessageUsesGroupsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getMessageUsesGroupsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT DISTINCT g.Id, g.Name AS Title', $sql);
        $this->assertStringContainsString('FROM Message m', $sql);
        $this->assertStringContainsString('JOIN `Group` g ON g.Id = m.GroupId', $sql);
        $this->assertStringContainsString('WHERE m.GroupId IS NOT NULL', $sql);
        $this->assertStringContainsString('AND m.ImagePath LIKE :path', $sql);
        $this->assertStringContainsString('ORDER BY g.Name ASC', $sql);
    }

    public function testGetNewsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getNewsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT m.Id, m.Text, m.LastUpdate, m.EventId,', $sql);
        $this->assertStringContainsString('i.FirstName, i.LastName, i.NickName,', $sql);
        $this->assertStringContainsString('e.Summary, e.StartTime', $sql);
        $this->assertStringContainsString('FROM Message m', $sql);
        $this->assertStringContainsString('JOIN Member     mb ON mb.Id = m.PersonId', $sql);
        $this->assertStringContainsString('JOIN Individual i  ON i.Id  = mb.Id', $sql);
        $this->assertStringContainsString('JOIN Event      e  ON e.Id  = m.EventId', $sql);
        $this->assertStringContainsString('WHERE m.LastUpdate > :searchFrom', $sql);
        $this->assertStringContainsString('AND m."From" = \'User\'', $sql);
        $this->assertStringContainsString('AND m.EventId IN (', $sql);
        $this->assertStringContainsString('SELECT IdEvent FROM Participant WHERE IdIndividual = :personId', $sql);
        $this->assertStringContainsString('ORDER BY m.LastUpdate DESC', $sql);
    }

    public function testGetPathsUsedInMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPathsUsedInMessagesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT ImagePath FROM Message WHERE ImagePath IS NOT NULL', $sql);
    }

    public function testDoGetGroupedMessagesSqlWithoutSearchFromIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDoGetGroupedMessagesSql(false);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringNotContainsString('AND m.LastUpdate >=', $sql);
        $this->assertStringContainsString('UNION ALL', $sql);
    }

    public function testDoGetGroupedMessagesSqlWithSearchFromIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDoGetGroupedMessagesSql(true);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('e.Summary AS title,', $sql);
        $this->assertStringContainsString('FROM Event e', $sql);
        $this->assertStringContainsString('INNER JOIN EventType et ON e.IdEventType = et.Id', $sql);
        $this->assertStringContainsString('INNER JOIN Message m ON m.EventId = e.Id AND m."From" = \'User\'', $sql);
        $this->assertStringContainsString('FROM Article a', $sql);
        $this->assertStringContainsString('INNER JOIN Message m ON m.ArticleId = a.Id AND m."From" = \'User\'', $sql);
        $this->assertStringContainsString('FROM `Group` g', $sql);
        $this->assertStringContainsString('LEFT JOIN MemberGroup mg ON mg.IdGroup = g.Id AND mg.IdMember = ?', $sql);
        $this->assertStringContainsString('INNER JOIN Message m ON m.GroupId = g.Id AND m."From" = \'User\'', $sql);
        $this->assertStringContainsString('AND m.LastUpdate >= ?', $sql);
        $this->assertStringContainsString('UNION ALL', $sql);
        $this->assertStringContainsString('ORDER BY LastUpdate DESC', $sql);
        $this->assertStringContainsString('HAVING message_count > 0', $sql);
    }

    public function testGetLastCreatedAtSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getLastCreatedAtSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT CreatedAt FROM Log WHERE Id = ?', $sql);
    }

    public function testHasRecentMessageApiCallSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getHasRecentMessageApiCallSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString('WHERE Id > ?', $sql);
        $this->assertStringContainsString("AND (Uri = '/api/message/add (POST)' OR Uri = '/api/message/update (POST)')", $sql);
    }

    private function getArticleMessagesSql(): string
    {
        return "
            SELECT 
                Message.*,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.ArticleId = :articleId
            ORDER BY Message.Id ASC
        ";
    }

    private function getEventMessagesSql(): string
    {
        return "
            SELECT 
                Message.*,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.EventId = :eventId AND Message.\"From\" = 'User'
            ORDER BY Message.Id ASC
        ";
    }

    private function getGroupMessagesSql(): string
    {
        return "
            SELECT 
                Message.Id,
                Message.PersonId,
                Message.Text,
                Message.ImagePath,
                Message.LastUpdate,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.GroupId = :groupId
            ORDER BY Message.Id ASC
        ";
    }

    private function getMessagesSql(): string
    {
        return "
            SELECT LOWER(i.Email) AS Email, COUNT(m.Id) AS MessageCount
            FROM Message m
            JOIN Member     mb ON mb.Id = m.PersonId
            JOIN Individual i  ON i.Id  = mb.Id
            WHERE m.LastUpdate BETWEEN :start AND :end
            GROUP BY i.Email
        ";
    }

    private function getMessageUsesEventsSql(): string
    {
        return "
            SELECT DISTINCT e.Id, e.Summary AS Title, e.StartTime
            FROM Message m
            JOIN Event e ON e.Id = m.EventId
            WHERE m.EventId IS NOT NULL
            AND m.ImagePath LIKE :path
            ORDER BY e.StartTime DESC
        ";
    }

    private function getMessageUsesArticlesSql(): string
    {
        return "
            SELECT DISTINCT a.Id, a.Title
            FROM Message m
            JOIN Article a ON a.Id = m.ArticleId
            WHERE m.ArticleId IS NOT NULL
            AND m.ImagePath LIKE :path
            ORDER BY a.Title ASC
        ";
    }

    private function getMessageUsesGroupsSql(): string
    {
        return "
            SELECT DISTINCT g.Id, g.Name AS Title
            FROM Message m
            JOIN `Group` g ON g.Id = m.GroupId
            WHERE m.GroupId IS NOT NULL
            AND m.ImagePath LIKE :path
            ORDER BY g.Name ASC
        ";
    }

    private function getNewsSql(): string
    {
        return "
            SELECT m.Id, m.Text, m.LastUpdate, m.EventId,
                   i.FirstName, i.LastName, i.NickName,
                   e.Summary, e.StartTime
            FROM Message m
            JOIN Member     mb ON mb.Id = m.PersonId
            JOIN Individual i  ON i.Id  = mb.Id
            JOIN Event      e  ON e.Id  = m.EventId
            WHERE m.LastUpdate > :searchFrom
              AND m.\"From\" = 'User'
              AND m.EventId IN (
                  SELECT IdEvent FROM Participant WHERE IdIndividual = :personId
              )
            ORDER BY m.LastUpdate DESC
        ";
    }

    private function getPathsUsedInMessagesSql(): string
    {
        return "SELECT ImagePath FROM Message WHERE ImagePath IS NOT NULL";
    }

    private function getDoGetGroupedMessagesSql(bool $withSearchFrom): string
    {
        $whereClause = $withSearchFrom ? "AND m.LastUpdate >= ?" : '';

        $eventsQuery = "
        SELECT 
            e.Id,
            e.Summary AS title,
            datetime(MAX(m.LastUpdate), 'localtime') AS LastUpdate,
            COUNT(m.Id) AS message_count,
            'event' AS type,
            lp_e.Avatar,
            lp_e.UseGravatar,
            lp_e.Email
        FROM Event e
        INNER JOIN EventType et ON e.IdEventType = et.Id
        INNER JOIN Message m ON m.EventId = e.Id AND m.\"From\" = 'User'
        LEFT JOIN (
            SELECT m2.EventId, i.Avatar, mb.UseGravatar, i.Email
            FROM Message m2
            INNER JOIN Member     mb ON mb.Id = m2.PersonId
            INNER JOIN Individual i  ON i.Id  = mb.Id
            WHERE m2.\"From\" = 'User'
              AND m2.LastUpdate = (
                  SELECT MAX(m3.LastUpdate)
                  FROM Message m3
                  WHERE m3.EventId = m2.EventId
                    AND m3.\"From\" = 'User'
              )
        ) lp_e ON lp_e.EventId = e.Id
        WHERE et.Inactivated = 0
          AND (
              et.IdGroup IS NULL 
              OR et.IdGroup IN (
                  SELECT IdGroup 
                  FROM MemberGroup 
                  WHERE IdMember = ?
              )
          )
          $whereClause
        GROUP BY e.Id, e.Summary, lp_e.Avatar, lp_e.UseGravatar, lp_e.Email
        HAVING message_count > 0";

        $articlesQuery = "
        SELECT 
            a.Id,
            a.Title AS title,
            datetime(MAX(m.LastUpdate), 'localtime') AS LastUpdate,
            COUNT(m.Id) AS message_count,
            'article' AS type,
            lp_a.Avatar,
            lp_a.UseGravatar,
            lp_a.Email
        FROM Article a
        INNER JOIN Message m ON m.ArticleId = a.Id AND m.\"From\" = 'User'
        LEFT JOIN (
            SELECT m2.ArticleId, i.Avatar, mb.UseGravatar, i.Email
            FROM Message m2
            INNER JOIN Member     mb ON mb.Id = m2.PersonId
            INNER JOIN Individual i  ON i.Id  = mb.Id
            WHERE m2.\"From\" = 'User'
              AND m2.LastUpdate = (
                  SELECT MAX(m3.LastUpdate)
                  FROM Message m3
                  WHERE m3.ArticleId = m2.ArticleId
                    AND m3.\"From\" = 'User'
              )
        ) lp_a ON lp_a.ArticleId = a.Id
        WHERE a.PublishedBy IS NOT NULL
          AND (
              a.CreatedBy = ?
              OR a.IdGroup IS NULL
              OR a.IdGroup IN (
                  SELECT IdGroup 
                  FROM MemberGroup 
                  WHERE IdMember = ?
              )
          )
          $whereClause
        GROUP BY a.Id, a.Title, lp_a.Avatar, lp_a.UseGravatar, lp_a.Email
        HAVING message_count > 0";

        $groupsQuery = "
        SELECT 
            g.Id,
            g.Name AS title,
            datetime(MAX(m.LastUpdate), 'localtime') AS LastUpdate,
            COUNT(m.Id) AS message_count,
            'group' AS type,
            lp_g.Avatar,
            lp_g.UseGravatar,
            lp_g.Email
        FROM `Group` g
        LEFT JOIN MemberGroup mg ON mg.IdGroup = g.Id AND mg.IdMember = ?
        INNER JOIN Message m ON m.GroupId = g.Id AND m.\"From\" = 'User'
        LEFT JOIN (
            SELECT m2.GroupId, i.Avatar, mb.UseGravatar, i.Email
            FROM Message m2
            INNER JOIN Member     mb ON mb.Id = m2.PersonId
            INNER JOIN Individual i  ON i.Id  = mb.Id
            WHERE m2.\"From\" = 'User'
              AND m2.LastUpdate = (
                  SELECT MAX(m3.LastUpdate)
                  FROM Message m3
                  WHERE m3.GroupId = m2.GroupId
                    AND m3.\"From\" = 'User'
              )
        ) lp_g ON lp_g.GroupId = g.Id
        WHERE g.Inactivated = 0
          AND (g.SelfRegistration = 1 OR mg.Id IS NOT NULL)
          $whereClause
        GROUP BY g.Id, g.Name, lp_g.Avatar, lp_g.UseGravatar, lp_g.Email
        HAVING message_count > 0";

        return "
        $eventsQuery
        UNION ALL
        $articlesQuery
        UNION ALL
        $groupsQuery
        ORDER BY LastUpdate DESC";
    }

    private function getLastCreatedAtSql(): string
    {
        return "SELECT CreatedAt FROM Log WHERE Id = ?";
    }

    private function getHasRecentMessageApiCallSql(): string
    {
        return "
            SELECT COUNT(*)
            FROM Log
            WHERE Id > ?
            AND (Uri = '/api/message/add (POST)' OR Uri = '/api/message/update (POST)')
        ";
    }
}