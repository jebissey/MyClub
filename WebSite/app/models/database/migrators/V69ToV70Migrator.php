<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V69ToV70Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('emailCredentials.method_brevo',  'Brevo (API)',    'Brevo (API)',           'Brevo (API)'),
('emailCredentials.info_brevo',    'Free Brevo plan: 300 emails/day and 6,000/month. A paid subscription is required beyond that.', "Plan gratuit Brevo : 300 courriels/jour et 6 000/mois. Au-delà, un abonnement payant est nécessaire.", 'Plan darmowy Brevo: 300 e-maili dziennie i 6 000 miesięcznie. Powyżej tego limitu wymagany jest płatny abonament.'),
('emailCredentials.brevo_api_key', 'API Key',        'Clé API',               'Klucz API'),
('emailCredentials.brevo_sender',  'Sender address', "Adresse d''expédition", 'Adres nadawcy');
SQL);

        return 70;
    }
}
