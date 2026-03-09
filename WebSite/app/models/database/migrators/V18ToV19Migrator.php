<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V18ToV19Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES

('emailCredentials.method',
 'Sending method',
 'Méthode d''envoi',
 'Metoda wysyłki'),

('emailCredentials.method_mail',
 'Native PHP mail()',
 'PHP mail() natif',
 'Natywny mail() PHP'),

('emailCredentials.method_smtp',
 'SMTP (PHPMailer)',
 'SMTP (PHPMailer)',
 'SMTP (PHPMailer)'),

('emailCredentials.method_mailjet',
 'Mailjet API',
 'Mailjet API',
 'Mailjet API'),

('emailCredentials.info_mail',
 'Emails sent via mail() may end up in spam or be rejected by some domains (Gmail, Outlook…). Recommended for testing only.',
 'Les courriels envoyés via mail() risquent d''arriver en spam ou d''être rejetés par certains domaines (Gmail, Outlook…). Recommandé uniquement pour les tests.',
 'Wiadomości wysyłane przez mail() mogą trafiać do spamu lub być odrzucane przez niektóre domeny (Gmail, Outlook…). Zalecane wyłącznie do testów.'),

('emailCredentials.info_smtp',
 'Sending limits depend on your SMTP provider (e.g. Gmail: 500/day, OVH: 200/hour). Please check your plan.',
 'Les limites d''envoi dépendent de votre fournisseur SMTP (ex. Gmail : 500/jour, OVH : 200/heure). Vérifiez votre offre.',
 'Limity wysyłki zależą od dostawcy SMTP (np. Gmail: 500/dzień, OVH: 200/godzinę). Sprawdź swój plan.'),

('emailCredentials.info_mailjet',
 'Mailjet free plan: 200 emails/day and 6,000/month. A paid subscription is required beyond these limits.',
 'Plan gratuit Mailjet : 200 e-mails/jour et 6 000/mois. Au-delà, un abonnement payant est nécessaire.',
 'Darmowy plan Mailjet: 200 e-maili/dzień i 6 000/miesiąc. Powyżej tych limitów wymagana jest płatna subskrypcja.'),

('emailCredentials.port',
 'SMTP port',
 'Port SMTP',
 'Port SMTP'),

('emailCredentials.encryption',
 'Encryption',
 'Chiffrement',
 'Szyfrowanie'),

('emailCredentials.no_encryption',
 'None',
 'Aucun',
 'Brak'),

('emailCredentials.mailjet_api_key',
 'Mailjet API key',
 'Clé API Mailjet',
 'Klucz API Mailjet'),

('emailCredentials.mailjet_api_secret',
 'Mailjet API secret',
 'Secret API Mailjet',
 'Sekret API Mailjet'),

('emailCredentials.mailjet_sender',
 'Verified sender address',
 'Adresse expéditeur vérifiée',
 'Zweryfikowany adres nadawcy');

SQL;
        $pdo->exec($sql);

        return 19;
    }
}
