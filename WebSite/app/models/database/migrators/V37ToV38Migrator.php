<?php
declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V37ToV38Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('events.calendar.page_title',
'Upcoming Events',
'Évènements des prochaines semaines',
'Nadchodzące wydarzenia'),

('events.calendar.heading',
'Events Calendar',
'Calendrier des événements',
'Kalendarz wydarzeń'),

('events.calendar.week',
'Week',
'Semaine',
'Tydzień'),

('events.calendar.no_event',
'No event',
'Aucun événement',
'Brak wydarzeń'),

('events.calendar.no_events_scheduled',
'No events scheduled for the coming weeks.',
'Aucun événement programmé pour les prochaines semaines.',
'Brak zaplanowanych wydarzeń na najbliższe tygodnie.'),

('events.calendar.welcome.block',
'<h5>📅 Welcome to the outings calendar!</h5>
<p>Find all our events here over 3 weeks.</p>
<ul>
    <li><strong>Most are reserved for members, except those in bold with a link.</strong></li>
    <li><strong>Colored squares = special info, hover to see details.</strong></li>
    <li><strong>[Group name] = reserved for members of that group.</strong></li>
</ul>
<h5>Mark your calendars! 📆✨</h5>
<a href="/nextEvents" class="btn btn-primary">
    <i class="bi bi-calendar-event"></i> Register for a public event
</a>',

'<h5>📅 Bienvenue sur le calendrier des sorties !</h5>
<p>Retrouvez ici tous nos événements sur 3 semaines.</p>
<ul>
    <li><strong>La plupart sont réservés aux membres, sauf ceux en gras avec un lien.</strong></li>
    <li><strong>Carrés colorés = infos spéciales, survolez pour voir le détail.</strong></li>
    <li><strong>[Nom de groupe] = réservé aux membres de ce groupe.</strong></li>
</ul>
<h5>À vos agendas ! 📆✨</h5>
<a href="/nextEvents" class="btn btn-primary">
    <i class="bi bi-calendar-event"></i> S''inscrire à un événement public
</a>',

'<h5>📅 Witamy w kalendarzu wyjazdów!</h5>
<p>Znajdź tutaj wszystkie nasze wydarzenia na 3 tygodnie.</p>
<ul>
    <li><strong>Większość jest zarezerwowana dla członków, z wyjątkiem tych pogrubionych z linkiem.</strong></li>
    <li><strong>Kolorowe kwadraty = specjalne informacje, najedź, aby zobaczyć szczegóły.</strong></li>
    <li><strong>[Nazwa grupy] = zarezerwowane dla członków tej grupy.</strong></li>
</ul>
<h5>Do kalendarzy! 📆✨</h5>
<a href="/nextEvents" class="btn btn-primary">
    <i class="bi bi-calendar-event"></i> Zarejestruj się na wydarzenie publiczne
</a>'),

('events.calendar.rss_subscribe',
'Subscribe to RSS feed',
'S''abonner au flux RSS',
'Subskrybuj kanał RSS'),

('user_connections.details.title',
 'Connections details',
 'Détails des connexions',
 'Szczegóły połączeń'),

('user_connections.table.people',
 'People',
 'Personnes',
 'Osoby'),

('user_connections.table.common_events',
 'Common events',
 'Événements communs',
 'Wspólne wydarzenia'),

('user_connections.table.intensity',
 'Intensity',
 'Intensité',
 'Intensywność'),

('user_connections.modal.common_events',
 'Common events',
 'Événements communs',
 'Wspólne wydarzenia'),

('user_connections.modal.close',
 'Close',
 'Fermer',
 'Zamknij');

SQL;

        $pdo->exec($sql);

        return 38;
    }
}