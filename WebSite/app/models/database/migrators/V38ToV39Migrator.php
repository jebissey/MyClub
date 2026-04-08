<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V38ToV39Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('events.filter.by_preferences',
'Only events matching my preferences',
'Uniquement les événements qui correspondent à mes préférences',
'Tylko wydarzenia pasujące do moich preferencji'),

('events.click_to_detail',
'Click on an event row to view details, register or unregister',
'Cliquer sur la ligne d''un événement pour voir le détail, s''inscrire ou se désinscrire',
'Kliknij wiersz wydarzenia, aby zobaczyć szczegóły, zapisać się lub wypisać'),

('events.no_attribute',
'No attribute',
'Aucun attribut',
'Brak atrybutu'),

('events.email.modal_title',
'Send an email',
'Envoyer un courriel',
'Wyślij wiadomość e-mail'),

('events.email.message_type',
'Message type',
'Type de message',
'Typ wiadomości'),

('events.email.select_type',
'Select a type',
'Sélectionnez un type',
'Wybierz typ'),

('events.email.type.new',
'New event',
'Nouvel événement',
'Nowe wydarzenie'),

('events.email.type.reminder',
'Reminder',
'Rappel',
'Przypomnienie'),

('events.email.type.canceled',
'Canceled',
'Annulé',
'Odwołane'),

('events.email.type.modified',
'Modified',
'Modifié',
'Zmodyfikowane'),

('events.email.recipients',
'Recipients',
'Destinataires',
'Odbiorcy'),

('events.email.select_type_first',
'Select a message type first',
'Sélectionnez d''abord un type de message',
'Najpierw wybierz typ wiadomości'),

('events.email.message',
'Message',
'Message',
'Wiadomość'),

('events.email.message_placeholder',
'Enter your message...',
'Saisissez votre message...',
'Wprowadź wiadomość...'),

('events.email.send',
'Send',
'Envoyer',
'Wyślij'),

('events.form.modal_title',
'Manage an event',
'Gérer un événement',
'Zarządzaj wydarzeniem'),

('events.form.title_label',
'Title',
'Titre',
'Tytuł'),

('events.form.title_placeholder',
'Title in the calendar',
'Titre dans le calendrier',
'Tytuł w kalendarzu'),

('events.form.description_placeholder',
'Event details',
'Détails de l''événement',
'Szczegóły wydarzenia'),

('events.form.location_label',
'Location',
'Lieu',
'Miejsce'),

('events.form.location_placeholder',
'Street / place name, city',
'Rue / lieu dit, ville',
'Ulica / nazwa miejsca, miasto'),

('events.form.event_type',
'Event type',
'Type d''événement',
'Typ wydarzenia'),

('events.form.date_time_duration',
'Date / Time / Duration (h)',
'Date / Heure / Durée (h)',
'Data / Godzina / Czas trwania (h)'),

('events.form.attributes',
'Attributes',
'Attributs',
'Atrybuty'),

('events.form.add',
'Add',
'Ajouter',
'Dodaj'),

('events.form.needs',
'Needs',
'Besoins',
'Potrzeby'),

('events.form.need_type_placeholder',
'Need type',
'Type de besoin',
'Typ potrzeby'),

('events.form.select_need_type_first',
'Select a need type first',
'Sélectionnez d''abord un type de besoin',
'Najpierw wybierz typ potrzeby'),

('events.form.max_participants',
'Maximum number of participants',
'Nombre max de participants',
'Maksymalna liczba uczestników'),

('events.form.unlimited',
'0 = unlimited',
'0 = illimité',
'0 = nieograniczone'),

('events.form.audience_label',
'Audience',
'Public',
'Publiczność'),

('events.form.audience.members_only',
'Club members only',
'Membres du club uniquement',
'Tylko członkowie klubu'),

('events.form.audience.guests',
'Club members and by invitation',
'Membres du club et sur « invitation »',
'Członkowie klubu i na zaproszenie'),

('events.form.audience.all',
'Everyone',
'Tous',
'Wszyscy'),

('events.form.create',
'Create',
'Créer',
'Utwórz'),

('events.duplicate.modal_title',
'What would you like to do?',
'Que souhaitez-vous faire ?',
'Co chcesz zrobić?'),

('events.duplicate.today',
'Duplicate today at 23:59',
'Dupliquer aujourd''hui à 23:59',
'Duplikuj dzisiaj o 23:59'),

('events.duplicate.tomorrow',
'Duplicate tomorrow at the same time',
'Dupliquer demain même heure',
'Duplikuj jutro o tej samej godzinie'),

('events.duplicate.next_week',
'Duplicate same day/time next week',
'Dupliquer même jour/heure la semaine prochaine',
'Duplikuj ten sam dzień/godzinę w przyszłym tygodniu'),

('events.duplicate.confirm',
'Confirm',
'Confirmer',
'Potwierdź');
SQL;
        $pdo->exec($sql);

        return 39;
    }
}
