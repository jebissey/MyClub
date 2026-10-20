<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V98ToV99Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        // One row per calling IP address, built from the /api/lastVersion calls.
        // Host is the raw "url" query parameter of the first call that provided one,
        // or the IP address when none did (reverse DNS is resolved in PHP).
        $pdoForLog->exec("
            CREATE VIEW installation_list_view AS
            SELECT
                IpAddress,
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
                GROUP_CONCAT(DISTINCT Message) AS phpVersions
            FROM Log
            WHERE Uri LIKE '/api/lastVersion%'
            GROUP BY IpAddress
        ");

        return 99;
    }
}
