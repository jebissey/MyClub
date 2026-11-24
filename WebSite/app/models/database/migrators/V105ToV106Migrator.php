<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

/**
 * Adds the translation keys required to fix four latent i18n bugs:
 *
 *  1. Article/mediaEdit.js — the "editSave" key (the save button label in its
 *     resting state) was missing from MediaController::$i18n, so after any
 *     save attempt the button fell back to the literal string "editSave".
 *
 *  2. PersonManager/memberFields.js — the "delete_confirm" key was never
 *     populated in window.i18n. The fallback chain was also broken because
 *     window.t returns the key itself (not undefined) for missing keys,
 *     short-circuiting the ?? operator and showing "delete_confirm" instead
 *     of the French fallback.
 *
 *  3. Loan/calendar.js — Loan/views/calendar.latte never populated
 *     window.i18n, so every window.t('record.*') and window.t('reservation.*')
 *     call displayed the raw key. Adds the static keys plus the four dynamic
 *     status sub-keys (active, returned, overdue, cancelled).
 *
 *  4. Membership/membership.js — membership.payment_success and
 *     membership.payment_error were already translated server-side via
 *     {='...'|translate}, but the JS-side window.t() calls need the same
 *     keys exposed in window.i18n to avoid showing raw keys after a
 *     HelloAsso postMessage event.
 */
final class V105ToV106Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- 1. Media manager: save button label in resting state (mediaEdit.js)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('media.manager.edit.save',
 'Save',
 'Enregistrer',
 'Zapisz');

-- 2. Person manager: delete confirmation for custom fields (memberFields.js)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('person_manager.member_fields.delete_confirm',
 'Are you sure you want to delete this field? All values already entered in this field will be permanently deleted.',
 'Êtes-vous sûr de vouloir supprimer ce champ ? Toutes les valeurs déjà saisies dans ce champ seront définitivement supprimées.',
 'Czy na pewno chcesz usunąć to pole? Wszystkie już wprowadzone wartości w tym polu zostaną trwale usunięte.');

-- 3. Loan calendar: static labels (calendar.js)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('loan.calendar.record.loan_date',
 'Loan date',
 'Date d''emprunt',
 'Data wypożyczenia'),

('loan.calendar.record.due_date',
 'Due date',
 'Date de retour',
 'Termin zwrotu'),

('loan.calendar.record.status',
 'Status',
 'Statut',
 'Status'),

-- 3b. Loan calendar: dynamic status sub-keys (record.status.{status})
('loan.calendar.record.status.active',
 'Active',
 'En cours',
 'Aktywny'),

('loan.calendar.record.status.returned',
 'Returned',
 'Rendu',
 'Zwrócony'),

('loan.calendar.record.status.overdue',
 'Overdue',
 'En retard',
 'Zaległy'),

('loan.calendar.record.status.cancelled',
 'Cancelled',
 'Annulé',
 'Anulowany'),

('loan.calendar.reservation.date',
 'Date',
 'Date',
 'Data'),

('loan.calendar.reservation.start',
 'Start',
 'Début',
 'Początek'),

('loan.calendar.reservation.end',
 'End',
 'Fin',
 'Koniec');

-- 4. Membership: payment feedback shown from JS (membership.js)
--    The keys membership.payment_success and membership.payment_error
--    already exist (used by index.latte via {='...'|translate}); we only
--    need to expose them in window.i18n. No new DB rows needed here.
SQL);

        return 106;
    }
}
