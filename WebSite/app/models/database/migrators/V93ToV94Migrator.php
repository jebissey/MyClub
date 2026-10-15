<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V93ToV94Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
-- Period selector is now shared (Common/views/partials/periodSelect.latte)
DELETE FROM Languages WHERE Name IN (
    'visitor_insights.cross_tab.period',
    'visitor_insights.cross_tab.period.today',
    'visitor_insights.cross_tab.period.yesterday',
    'visitor_insights.cross_tab.period.before_yesterday',
    'visitor_insights.cross_tab.period.week',
    'visitor_insights.cross_tab.period.month',
    'visitor_insights.cross_tab.period.quarter',
    'visitor_insights.cross_tab.period.year'
);

INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('common.period',
'Period',
'Période',
'Okres'),

('common.crosstab.selected_period',
'Selected period',
'Période sélectionnée',
'Wybrany okres'),

('common.crosstab.no_data',
'No results found for the selected period.',
'Aucun résultat trouvé pour la période sélectionnée.',
'Nie znaleziono wyników dla wybranego okresu.'),

('common.crosstab.total',
'Total',
'Total',
'Razem'),

('article.crosstab.title',
'Editors vs audience',
'Rédacteurs vs audience',
'Redaktorzy a odbiorcy'),

('article.crosstab.total.articles',
'articles',
'articles',
'artykuły'),

('event.crosstab.title',
'Organizers vs event type',
'Animateurs vs type d''événement',
'Organizatorzy a typ wydarzenia'),

('event.crosstab.total.events',
'events',
'événements',
'wydarzenia'),

('event.crosstab.total.participants',
'participants',
'participants',
'uczestnicy'),

('user.messages.empty.since_signout',
 'No messages since your last sign-out.',
 'Aucun message depuis votre dernière déconnexion.',
 'Brak wiadomości od ostatniego wylogowania.'),

('user.messages.empty.since_signin',
 'No messages since your last sign-in.',
 'Aucun message depuis votre dernière connexion.',
 'Brak wiadomości od ostatniego logowania.'),

('user.messages.empty.since_week',
 'No messages in the past week.',
 'Aucun message depuis une semaine.',
 'Brak wiadomości od tygodnia.'),

('user.messages.empty.since_month',
 'No messages in the past month.',
 'Aucun message depuis un mois.',
 'Brak wiadomości od miesiąca.'),

('user.messages.empty.since_quarter',
 'No messages in the past quarter.',
 'Aucun message depuis un trimestre.',
 'Brak wiadomości od kwartału.'),

('user.messages.empty.since_year',
 'No messages in the past year.',
 'Aucun message depuis un an.',
 'Brak wiadomości od roku.');
SQL);

        return 94;
    }
}
