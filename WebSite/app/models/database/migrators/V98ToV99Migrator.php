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
        $pdoForLog->exec('DROP VIEW IF EXISTS installation_list_view');

        $pdoForLog->exec("
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
    GROUP BY Host, webappVersions, phpVersions, status
");



        $pdo->exec(<<<'SQL'
-- Webmaster installations: table labels
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('webmaster.installations.label.host',
'Host',
'Hôte',
'Host'),

('webmaster.installations.label.webapp_version',
'Webapp version',
'Version de la webapp',
'Wersja aplikacji'),

('webmaster.installations.label.php_version',
'PHP version',
'Version de PHP',
'Wersja PHP'),

('webmaster.installations.label.last_check',
'Last check',
'Dernière vérification',
'Ostatnie sprawdzenie'),

('webmaster.installations.label.time_ago',
'Time ago',
'Il y a',
'Temu'),

('webmaster.installations.label.check_count',
'Number of checks',
'Nombre de vérifications',
'Liczba sprawdzeń'),

('webmaster.installations.label.status',
'Status',
'Statut',
'Status'),

-- Webmaster installations: status values
('webmaster.installations.status.active',
'Active',
'Actif',
'Aktywna'),

('webmaster.installations.status.recent',
'Recent',
'Récent',
'Niedawna'),

('webmaster.installations.status.inactive',
'Inactive',
'Inactif',
'Nieaktywna'),

-- Webmaster installations: miscellaneous
('webmaster.installations.unknown_version',
'Unknown version',
'Version inconnue',
'Nieznana wersja'),

('webmaster.installations.empty.title',
'No installation detected',
'Aucune installation détectée',
'Nie wykryto żadnej instalacji'),

('webmaster.installations.empty.message',
'No installation of the webapp has checked for updates yet.',
'Aucune installation de la webapp n''a encore vérifié les mises à jour.',
'Żadna instalacja aplikacji nie sprawdzała jeszcze aktualizacji.');
SQL);

        return 99;
    }
}
