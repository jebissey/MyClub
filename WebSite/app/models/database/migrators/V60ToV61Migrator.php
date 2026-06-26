<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V60ToV61Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
-- Email form
('communication.email.subject_required',
'Please enter a subject.',
"Veuillez renseigner l'objet du message.",
'Proszę wprowadzić temat.'),

('communication.email.content_required',
'Please enter message content.',
'Veuillez renseigner le contenu du message.',
'Proszę wprowadzić treść wiadomości.'),

('communication.email.confirm_send',
'You are about to send this message to <strong>%d</strong> recipient(s) in BCC.',
"Vous êtes sur le point d'envoyer ce message à <strong>%d</strong> destinataire(s) en copie cachée (BCC).",
'Zamierzasz wysłać tę wiadomość do <strong>%d</strong> odbiorców w ukrytej kopii (BCC).'),

('communication.email.send_error',
'Sending failed.',
"Échec de l'envoi.",
'Nie udało się wysłać.'),

('communication.email.unexpected_error',
'An unexpected error occurred.',
'Une erreur inattendue est survenue.',
'Wystąpił nieoczekiwany błąd.'),

-- Members
('communication.members.none_found',
'No members found.',
'Aucun membre trouvé.',
'Nie znaleziono członków.'),

-- Quota
('communication.quota.daily_reached',
'Daily limit reached — this send (%d credits) would exceed the limit of %d.',
'Plafond journalier atteint — cet envoi (%d crédit(s)) dépasserait la limite de %d.',
'Osiągnięto dzienny limit — ta wysyłka (%d kredytów) przekroczyłaby limit %d.'),

('communication.quota.monthly_reached',
'Monthly limit reached — this send (%d credits) would exceed the limit of %d.',
'Plafond mensuel atteint — cet envoi (%d crédit(s)) dépasserait la limite de %d.',
'Osiągnięto miesięczny limit — ta wysyłka (%d kredytów) przekroczyłaby limit %d.'),

('communication.quota.almost_exceeded',
'Quota almost exhausted — daily: %s remaining, monthly: %s remaining.',
'Quota presque épuisé — journalier : %s restant(s), mensuel : %s restant(s).',
'Limit prawie wyczerpany — dzienny: %s pozostało, miesięczny: %s pozostało.'),

('communication.quota.daily_label',
'daily',
'journalier',
'dzienny'),

('communication.quota.monthly_label',
'monthly',
'mensuel',
'miesięczny');

SQL;

        $pdo->exec($sql);

        return 61;
    }
}
