<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V46ToV47Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
-- Rename existing key
UPDATE Languages
SET 
    Name = 'emailCredentials.smtpAccount',
    en_US = 'SMTP Account',
    fr_FR = 'Compte SMTP',
    pl_PL = 'Konto SMTP'
WHERE Name = 'emailCredentials.email';

-- Insert new keys
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('emailCredentials.smtpAccount_placeholder',
'SMTP username',
'Nom d''utilisateur SMTP',
'Nazwa użytkownika SMTP'),

('emailCredentials.smtpAccount_help',
'Login used to connect to the SMTP server (not necessarily an email address).',
'Identifiant de connexion au serveur SMTP (pas forcément une adresse e-mail).',
'Login do serwera SMTP (niekoniecznie adres e-mail).'),

('emailCredentials.encryption_tls',
'TLS (STARTTLS – port 587)',
'TLS (STARTTLS – port 587)',
'TLS (STARTTLS – port 587)'),

('emailCredentials.encryption_ssl',
'SSL (port 465)',
'SSL (port 465)',
'SSL (port 465)'),

('emailCredentials.smtpFrom',
'SMTP From address',
'Adresse d''expédition SMTP',
'Adres nadawcy SMTP'),

('emailCredentials.smtpFrom_placeholder',
'sender@example.com',
'expediteur@exemple.com',
'nadawca@przyklad.pl'),

('emailCredentials.smtpFrom_help',
'Email address used as sender. Must be allowed by your SMTP server.',
'Adresse e-mail utilisée comme expéditeur. Doit être autorisée par votre serveur SMTP.',
'Adres e-mail nadawcy. Musi być dozwolony przez serwer SMTP.');
SQL;
        $pdo->exec($sql);

        return 47;
    }
}
