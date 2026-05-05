<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V61ToV62Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('communication.api.missing_fields',
'Required fields are missing.',
'Champs obligatoires manquants.',
'Brakuje wymaganych pól.'),

('communication.api.no_valid_recipients',
'No valid recipient found.',
'Aucun destinataire valide trouvé.',
'Nie znaleziono prawidłowego odbiorcy.'),

('communication.api.quota_daily_exceeded',
'Daily quota exceeded.',
'Quota journalier dépassé.',
'Dzienny limit został przekroczony.'),

('communication.api.quota_monthly_exceeded',
'Monthly quota exceeded.',
'Quota mensuel dépassé.',
'Miesięczny limit został przekroczony.'),

('communication.api.send_success',
'Message successfully sent to %d recipient(s) in blind copy.',
'Message envoyé avec succès à %d destinataire(s) en copie cachée.',
'Wiadomość wysłana pomyślnie do %d odbiorcy/odbiorców w ukrytej kopii.'),

('communication.api.send_failed',
'Sending failed. Please try again or contact the administrator.',
"L'envoi a échoué. Veuillez réessayer ou contacter l'administrateur.",
'Wysyłanie nie powiodło się. Spróbuj ponownie lub skontaktuj się z administratorem.'),

('communication.api.send_impossible',
'Unable to send: ',
'Envoi impossible : ',
'Nie można wysłać: '),

('communication.index.reply_to',
'Reply to',
'Répondre à',
'Odpowiedz do'),

('communication.index.reply_to_noreply',
'No reply',
'Pas de réponse',
'Brak odpowiedzi'),

('communication.index.reply_to_smtp',
'Sending address',
"Adresse d'envoi",
'Adres nadawcy'),

('communication.index.reply_to_user',
'My address',
'Mon adresse',
'Mój adres');
SQL;

        $pdo->exec($sql);

        return 62;
    }
}