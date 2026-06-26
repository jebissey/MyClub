<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V78ToV79Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('communication.index.contact_email_tooltip', 'Email address where contact requests will be sent', 'Adresse e-mail où les demandes de contact seront envoyées', 'Adres e-mail, na który będą wysyłane prośby o kontakt');
SQL);

        return 79;
    }
}
