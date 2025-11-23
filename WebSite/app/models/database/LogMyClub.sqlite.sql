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
	PRIMARY KEY("Id")
);
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
COMMIT;
