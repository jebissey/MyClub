<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V91ToV92Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.carpooling',
'Carpooling',
'Covoiturage',
'Wspólne dojazdy'),
('event.participants',
'Participants:',
'Participants :',
'Uczestnicy:'),
('event.calendar_app',
'Calendar',
'Calendrier',
'Kalendarz'),
('user.map.title',
'Members map',
'Carte des membres',
'Mapa członków'),
('user.map.public_title',
'Public members map',
'Carte des membres publics',
'Mapa członków publicznych'),
('user.map.members_located',
'members located',
'membres localisés',
'członków zlokalizowanych'),

('designer.home_settings.header',
'Home page header',
'En-tête de la page d''accueil',
'Nagłówek strony głównej'),
('designer.home_settings.footer',
'Home page footer',
'Pied de page de la page d''accueil',
'Stopka strony głównej'),
('designer.home_settings.latest_articles_count',
'Number of latest articles to display',
'Nombre de derniers articles à afficher',
'Liczba ostatnich artykułów do wyświetlenia'),
('designer.home_settings.featured_article_id',
'Featured article ID (0 = latest article)',
'ID de l''article mis en avant (0 = dernier article)',
'ID wyróżnionego artykułu (0 = najnowszy artykuł)'),
('designer.home_settings.featured_article_paragraphs',
'Number of paragraphs to display (0 = whole article)',
'Nombre de paragraphes à afficher (0 = article entier)',
'Liczba akapitów do wyświetlenia (0 = cały artykuł)'),
('home.signpost.title',
'What would you like to do?',
'Que souhaitez-vous faire ?',
'Co chcesz zrobić?');
SQL);

        return 92;
    }
}
