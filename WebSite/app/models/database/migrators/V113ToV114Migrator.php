<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V113ToV114Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Push notification broadcast (WebmasterApi): previously hard-coded texts
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('webmaster.notification.vapid_not_configured',
    'VAPID keys are not configured yet.',
    'Les clés VAPID ne sont pas encore configurées.',
    'Klucze VAPID nie zostały jeszcze skonfigurowane.'),
('webmaster.notification.default_title', 'New message', 'Nouveau message', 'Nowa wiadomość'),
('webmaster.notification.default_body', 'Click to see!', 'Cliquez pour voir !', 'Kliknij, aby zobaczyć!');
SQL);

        return 114;
    }
}
