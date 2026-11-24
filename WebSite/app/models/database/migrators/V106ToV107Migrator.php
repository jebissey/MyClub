<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

/**
 * Adds the translation keys reported as missing by TranslationKeysTest:
 * keys used in .php / .latte files but absent from the Languages table.
 *
 *  1. Exercise list: column headers and default title.
 *  2. Article carousel: JavaScript error messages.
 *  3. Visitor insights cross tab: period filter labels.
 *  4. Loan: "all" status filter and reservation user label.
 *  5. Generic labels: Games, User, messages.
 */
final class V106ToV107Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- 1. Exercise list: column headers and default title (ExerciseController)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('exercise.column.created_by',
 'Created by',
 'Créé par',
 'Utworzone przez'),

('exercise.column.title',
 'Title',
 'Titre',
 'Tytuł'),

('exercise.column.detail',
 'Detail',
 'Détail',
 'Szczegóły'),

('exercise.column.last_update',
 'Last update',
 'Dernière mise à jour',
 'Ostatnia aktualizacja'),

('exercise.column.group',
 'Group',
 'Groupe',
 'Grupa'),

('exercise.column.club',
 'Club',
 'Club',
 'Klub'),

('exercise.default_title',
 'Untitled exercise',
 'Exercice sans titre',
 'Ćwiczenie bez tytułu');

-- 2. Article carousel: JavaScript error messages (article_carousel.latte)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('article.carousel.js.url_required',
 'Please enter an image URL.',
 'Veuillez saisir l''URL de l''image.',
 'Wprowadź adres URL obrazu.'),

('article.carousel.js.add_error',
 'Unable to add the image.',
 'Impossible d''ajouter l''image.',
 'Nie można dodać obrazu.'),

('article.carousel.js.delete_error',
 'Unable to delete the image.',
 'Impossible de supprimer l''image.',
 'Nie można usunąć obrazu.');

-- 3. Visitor insights cross tab: period filter labels (crossTab.latte)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('cross_tab.period',
 'Period',
 'Période',
 'Okres'),

('cross_tab.period.today',
 'Today',
 'Aujourd''hui',
 'Dzisiaj'),

('cross_tab.period.yesterday',
 'Yesterday',
 'Hier',
 'Wczoraj'),

('cross_tab.period.before_yesterday',
 'Day before yesterday',
 'Avant-hier',
 'Przedwczoraj'),

('cross_tab.period.week',
 'Week',
 'Semaine',
 'Tydzień'),

('cross_tab.period.month',
 'Month',
 'Mois',
 'Miesiąc'),

('cross_tab.period.quarter',
 'Quarter',
 'Trimestre',
 'Kwartał'),

('cross_tab.period.year',
 'Year',
 'Année',
 'Rok');

-- 4. Loan: "all" status filter (manager.latte) and reservation user label (user.latte)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('loan.record.status.all',
 'All',
 'Tous',
 'Wszystkie'),

('loan.reservation.user',
 'User',
 'Utilisateur',
 'Użytkownik');

-- 5. Generic labels (GameManagerController, UserDashboardController, event_detail.latte)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('Games',
 'Games',
 'Jeux',
 'Gry'),

('User',
 'User',
 'Utilisateur',
 'Użytkownik'),

('messages',
 'Messages',
 'Messages',
 'Wiadomości'),

(
    'auth.forgot_password.email.subject',
    'Password reset request',
    'Demande de réinitialisation du mot de passe',
    'Prośba o zresetowanie hasła'
),
(
    'auth.forgot_password.email.body',
    'To reset your password, click the following link:',
    'Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :',
    'Aby zresetować hasło, kliknij poniższy link:'
),
(
    'events.calendar.welcome.block',
    '<p>Welcome to the events calendar. Here you can view scheduled events and their details.</p>',
    '<p>Bienvenue dans le calendrier des événements. Vous pouvez consulter ici les événements programmés et leurs détails.</p>',
    '<p>Witamy w kalendarzu wydarzeń. Możesz tutaj przeglądać zaplanowane wydarzenia i ich szczegóły.</p>'
)

SQL);

        return 107;
    }
}
