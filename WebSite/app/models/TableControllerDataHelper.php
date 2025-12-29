<?php

declare(strict_types=1);

namespace app\models;

use \Envms\FluentPDO\Queries\Select;

use app\helpers\Application;

class TableControllerDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getEventTypesQuery(): Select
    {
        return $this->fluent->from('EventType')
            ->select(null)
            ->select('EventType.Id AS EventTypeId, EventType.Name AS EventTypeName, `Group`.Name AS GroupName')
            ->select('GROUP_CONCAT(Attribute.Name, ", ") AS Attributes')
            ->leftJoin('`Group` ON EventType.IdGroup = `Group`.Id')
            ->leftJoin('EventTypeAttribute ON EventType.Id = EventTypeAttribute.IdEventType')
            ->leftJoin('Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id')
            ->where('EventType.Inactivated = 0')
            ->groupBy('EventType.Id')
            ->orderBy('EventType.Name');
    }

    public function getPersonsQuery(): Select
    {
        return $this->fluent->from('Person')
            ->select(null)
            ->select('Id, FirstName, LastName, NickName, Email, Phone')
            ->orderBy('LastName')
            ->where('Inactivated = 0');
    }

    public function getLeapfrogQuery(): Select
    {
        $stmt = $this->pdoForLog->prepare("
            SELECT name 
            FROM sqlite_master 
            WHERE type = 'view' 
            AND name = 'leapfrog_statistics'
        ");
        $stmt->execute();
        $exists = $stmt->fetchColumn();
        if (!$exists) {
            $sql = "
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
            ORDER BY Date DESC;
            ";
            $this->pdoForLog->exec($sql);
        }
        return $this->fluent->from('leapfrog_statistics');
    }
}
