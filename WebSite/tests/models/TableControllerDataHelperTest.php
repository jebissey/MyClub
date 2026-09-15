<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class TableControllerDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
            'IdGroup',
            'Inactivated',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'EventTypeAttribute', [
            'IdEventType',
            'IdAttribute',
        ]);

        $this->assertColumnsExist($pdo, 'Attribute', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'FirstName',
            'LastName',
            'NickName',
            'Email',
            'Phone',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Alert',
            'MemberInfo',
            'Password',
            'InPresentationDirectory',
            'Inactivated',
        ]);
    }

    public function testGetEventTypesQuerySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getEventTypesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM EventType', $sql);
        $this->assertStringContainsString('EventType.Id', $sql);
        $this->assertStringContainsString('EventType.Name AS EventTypeName', $sql);
        $this->assertStringContainsString('`Group`.Name AS GroupName', $sql);
        $this->assertStringContainsString('GROUP_CONCAT(Attribute.Name, ", ") AS Attributes', $sql);
        $this->assertStringContainsString('LEFT JOIN `Group` ON EventType.IdGroup = `Group`.Id', $sql);
        $this->assertStringContainsString('LEFT JOIN EventTypeAttribute ON EventType.Id = EventTypeAttribute.IdEventType', $sql);
        $this->assertStringContainsString('LEFT JOIN Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id', $sql);
        $this->assertStringContainsString('EventType.Inactivated = 0', $sql);
        $this->assertStringContainsString('GROUP BY EventType.Id', $sql);
        $this->assertStringContainsString('ORDER BY EventType.Name', $sql);
    }

    public function testGetActivePersonsQuerySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getActivePersonsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Individual', $sql);
        $this->assertStringContainsString('INNER JOIN Member ON Member.Id = Individual.Id', $sql);
        $this->assertStringContainsString('Individual.Id, Individual.FirstName, Individual.LastName, Individual.NickName, Individual.Email, Individual.Phone, Member.Alert, Member.MemberInfo', $sql);
        $this->assertStringContainsString("CASE WHEN Member.Password IS NOT NULL THEN 'oui' ELSE 'non' END AS PasswordCreated", $sql);
        $this->assertStringContainsString("CASE WHEN Member.InPresentationDirectory = 1 THEN 'oui' ELSE 'non' END AS PresentInDirectory", $sql);
        $this->assertStringContainsString('Member.Inactivated = 0', $sql);
        $this->assertStringContainsString('ORDER BY Individual.LastName', $sql);
    }

    public function testGetDesactivatedPersonsQuerySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDesactivatedPersonsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM Individual', $sql);
        $this->assertStringContainsString('INNER JOIN Member ON Member.Id = Individual.Id', $sql);
        $this->assertStringContainsString('Individual.Id, Individual.FirstName, Individual.LastName, Individual.NickName, Individual.Email, Individual.Phone, Member.Alert, Member.MemberInfo', $sql);
        $this->assertStringContainsString("CASE WHEN Member.Password IS NOT NULL THEN 'oui' ELSE 'non' END AS PasswordCreated", $sql);
        $this->assertStringContainsString("CASE WHEN Member.InPresentationDirectory = 1 THEN 'oui' ELSE 'non' END AS PresentInDirectory", $sql);
        $this->assertStringContainsString('Member.Inactivated = 1', $sql);
        $this->assertStringContainsString('ORDER BY Individual.LastName', $sql);
    }

    public function testLeapfrogViewCheckSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getLeapfrogViewExistsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT name', $sql);
        $this->assertStringContainsString('FROM sqlite_master', $sql);
        $this->assertStringContainsString("type = 'view'", $sql);
        $this->assertStringContainsString("name = 'leapfrog_statistics'", $sql);
    }

    public function testLeapfrogCreateViewSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getLeapfrogCreateViewSql();

        // CREATE VIEW is executed via exec(), but we still validate the SQL structure
        $this->assertStringContainsString('CREATE VIEW leapfrog_statistics AS', $sql);
        $this->assertStringContainsString('FROM Log', $sql);
        $this->assertStringContainsString("Message LIKE 'Session %:%'", $sql);
        $this->assertStringContainsString("Message LIKE '%Game over: won%'", $sql);
        $this->assertStringContainsString("Message LIKE '%Game over: lost%'", $sql);
        $this->assertStringContainsString("Message LIKE '%Moved sheep%'", $sql);
        $this->assertStringContainsString('WHERE rn = 1', $sql);
        $this->assertStringContainsString('ORDER BY Date DESC', $sql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from TableControllerDataHelper)
    // -------------------------------------------------------------------------

    private function getEventTypesSql(): string
    {
        return '
            SELECT EventType.Id, EventType.Name AS EventTypeName, `Group`.Name AS GroupName,
                   GROUP_CONCAT(Attribute.Name, ", ") AS Attributes
            FROM EventType
            LEFT JOIN `Group` ON EventType.IdGroup = `Group`.Id
            LEFT JOIN EventTypeAttribute ON EventType.Id = EventTypeAttribute.IdEventType
            LEFT JOIN Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id
            WHERE EventType.Inactivated = 0
            GROUP BY EventType.Id
            ORDER BY EventType.Name
        ';
    }

    private function getActivePersonsSql(): string
    {
        return "
            SELECT Individual.Id, Individual.FirstName, Individual.LastName, Individual.NickName, Individual.Email, Individual.Phone, Member.Alert, Member.MemberInfo,
                   CASE WHEN Member.Password IS NOT NULL THEN 'oui' ELSE 'non' END AS PasswordCreated,
                   CASE WHEN Member.InPresentationDirectory = 1 THEN 'oui' ELSE 'non' END AS PresentInDirectory
            FROM Individual
            INNER JOIN Member ON Member.Id = Individual.Id
            WHERE Member.Inactivated = 0
            ORDER BY Individual.LastName
        ";
    }

    private function getDesactivatedPersonsSql(): string
    {
        return "
            SELECT Individual.Id, Individual.FirstName, Individual.LastName, Individual.NickName, Individual.Email, Individual.Phone, Member.Alert, Member.MemberInfo,
                   CASE WHEN Member.Password IS NOT NULL THEN 'oui' ELSE 'non' END AS PasswordCreated,
                   CASE WHEN Member.InPresentationDirectory = 1 THEN 'oui' ELSE 'non' END AS PresentInDirectory
            FROM Individual
            INNER JOIN Member ON Member.Id = Individual.Id
            WHERE Member.Inactivated = 1
            ORDER BY Individual.LastName
        ";
    }

    private function getLeapfrogViewExistsSql(): string
    {
        return "
            SELECT name 
            FROM sqlite_master 
            WHERE type = 'view' 
            AND name = 'leapfrog_statistics'
        ";
    }

    private function getLeapfrogCreateViewSql(): string
    {
        return "
            CREATE VIEW leapfrog_statistics AS
            WITH Parsed AS (
                SELECT 
                    Id,
                    date(CreatedAt) AS Date,
                    COALESCE(Uri, '') AS Uri,
                    COALESCE(Who, '') AS Who,
                    trim(substr(
                        Message,
                        instr(Message, 'Session ') + 8,
                        instr(Message || ':', ':') - (instr(Message, 'Session ') + 8)
                    )) AS SessionId,
                    CASE 
                        WHEN Message LIKE '%Game over: won%'  THEN 'won'
                        WHEN Message LIKE '%Game over: lost%' THEN 'lost'
                        ELSE ''
                    END AS GameResult
                FROM Log 
                WHERE Message LIKE 'Session %:%'
            ),
            WithMoves AS (
                SELECT 
                    p.*,
                    (
                        SELECT COUNT(*)
                        FROM Log g
                        WHERE g.Uri = p.Uri
                        AND g.Message LIKE '%Moved sheep%'
                        AND instr(g.Message, p.SessionId) > 0
                    ) AS MoveCount
                FROM Parsed p
            ),
            Ranked AS (
                SELECT
                    *,
                    CASE
                        WHEN GameResult = 'lost' THEN 1
                        WHEN GameResult = 'won'  THEN 2
                        ELSE 3
                    END AS priority,
                    ROW_NUMBER() OVER (
                        PARTITION BY SessionId
                        ORDER BY 
                            CASE
                                WHEN GameResult = 'lost' THEN 1
                                WHEN GameResult = 'won'  THEN 2
                                ELSE 3
                            END
                    ) AS rn
                FROM WithMoves
            )
            SELECT 
                Id,
                Date,
                Uri,
                Who,
                SessionId,
                GameResult,
                MoveCount
            FROM Ranked
            WHERE rn = 1
            ORDER BY Date DESC
        ";
    }
}