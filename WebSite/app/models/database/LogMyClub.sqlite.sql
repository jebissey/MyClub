BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "Log" (
	"Id"	INTEGER,
	"IpAddress"	TEXT,
	"Referer"	TEXT,
	"Os"	TEXT,
	"Browser"	TEXT,
	"ScreenResolution"	TEXT,
	"Type"	TEXT,
	"Uri"	TEXT,
	"Token"	TEXT,
	"Who"	TEXT,
	"CreatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
	"Code"	TEXT,
	"Message"	TEXT,
	"Count"	INTEGER NOT NULL DEFAULT 1,
	"Duration"	REAL,
	PRIMARY KEY("Id")
);
CREATE VIEW installation_list_view AS
    SELECT
        Host,
        webappVersions,
        phpVersions,
        status,
        MAX(lastCheck) AS lastCheck,
        SUM(checkCount) AS checkCount
    FROM (
        SELECT
            COALESCE(
                (SELECT
                    CASE
                        WHEN INSTR(l2.Uri, 'url=') > 0 THEN
                            CASE
                                WHEN INSTR(SUBSTR(l2.Uri, INSTR(l2.Uri, 'url=') + 4), '&') > 0 THEN
                                    SUBSTR(
                                        SUBSTR(l2.Uri, INSTR(l2.Uri, 'url=') + 4),
                                        1,
                                        INSTR(SUBSTR(l2.Uri, INSTR(l2.Uri, 'url=') + 4), '&') - 1
                                    )
                                ELSE SUBSTR(l2.Uri, INSTR(l2.Uri, 'url=') + 4)
                            END
                    END
                 FROM Log l2
                 WHERE l2.IpAddress = Log.IpAddress
                   AND l2.Uri LIKE '/api/lastVersion%'
                   AND INSTR(l2.Uri, 'url=') > 0
                 LIMIT 1),
                IpAddress
            ) AS Host,
            MAX(CreatedAt) AS lastCheck,
            COUNT(*) AS checkCount,
            GROUP_CONCAT(DISTINCT
                CASE
                    WHEN INSTR(Uri, 'cv=') > 0 THEN
                        CASE
                            WHEN INSTR(SUBSTR(Uri, INSTR(Uri, 'cv=') + 3), '&') > 0 THEN
                                SUBSTR(
                                    SUBSTR(Uri, INSTR(Uri, 'cv=') + 3),
                                    1,
                                    INSTR(SUBSTR(Uri, INSTR(Uri, 'cv=') + 3), '&') - 1
                                )
                            ELSE SUBSTR(Uri, INSTR(Uri, 'cv=') + 3)
                        END
                END
            ) AS webappVersions,
            GROUP_CONCAT(DISTINCT Message) AS phpVersions,
            CASE
                WHEN julianday('now') - julianday(MAX(CreatedAt)) < 7  THEN 'active'
                WHEN julianday('now') - julianday(MAX(CreatedAt)) < 30 THEN 'recent'
                ELSE 'inactive'
            END AS status
        FROM Log
        WHERE Uri LIKE '/api/lastVersion%'
        GROUP BY IpAddress
    )
    GROUP BY Host, webappVersions, phpVersions, status;
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
CREATE VIEW makethetarget_statistics AS
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
                            WHEN Message LIKE '%Game won%' THEN 'won'
                            ELSE ''
                        END AS GameResult
                    FROM Log 
                    WHERE Message LIKE 'Session %:%'
                    AND (Message LIKE '%makethetarget%' 
                        OR Uri LIKE '%makethetarget%' 
                        OR Message LIKE '%New game drawn%' 
                        OR Message LIKE '%Game won%' 
                        OR Message LIKE '%Operation:%' 
                        OR Message LIKE '%Solution requested%')
                ),
                WithOps AS (
                    SELECT 
                        p.*,
                        (
                            SELECT COUNT(*)
                            FROM Log g
                            WHERE g.Uri = p.Uri
                            AND g.Message LIKE '%Operation:%'
                            AND instr(g.Message, p.SessionId) > 0
                        ) AS OperationCount
                    FROM Parsed p
                ),
                Ranked AS (
                    SELECT
                        *,
                        CASE
                            WHEN GameResult = 'won' THEN 1
                            ELSE 2
                        END AS priority,
                        ROW_NUMBER() OVER (
                            PARTITION BY SessionId
                            ORDER BY 
                                CASE
                                    WHEN GameResult = 'won' THEN 1
                                    ELSE 2
                                END
                        ) AS rn
                    FROM WithOps
                )
                SELECT 
                    Id,
                    Date,
                    Uri,
                    Who,
                    SessionId,
                    GameResult,
                    OperationCount
                FROM Ranked
                WHERE rn = 1
                ORDER BY Date DESC;
COMMIT;
