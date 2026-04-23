<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V50ToV51Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('user.filter.since_signout',
 'Since your last sign-out',
 'Depuis votre dernière déconnexion',
 'Od ostatniego wylogowania'),

('user.filter.since_signin',
 'Since your sign-in',
 'Depuis votre connexion',
 'Od ostatniego logowania'),

('user.filter.since_week',
 'Since one week',
 'Depuis une semaine',
 'Od tygodnia'),

('user.filter.since_month',
 'Since one month',
 'Depuis un mois',
 'Od miesiąca'),

('user.filter.since_quarter',
 'Since one quarter',
 'Depuis un trimestre',
 'Od kwartału'),

('user.filter.since_year',
 'Since one year',
 'Depuis un an',
 'Od roku'),


('user.filter.info.showing_since',
 'Showing content since',
 'Affichage depuis le',
 'Wyświetlanie od'),

('user.filter.info.label_signout',
 '(last sign-out)',
 '(dernière déconnexion)',
 '(ostatnie wylogowanie)'),

('user.filter.info.label_signin',
 '(last sign-in)',
 '(dernière connexion)',
 '(ostatnie logowanie)'),

('user.filter.info.label_week',
 '(1 week)',
 '(1 semaine)',
 '(1 tydzień)'),

('user.filter.info.label_month',
 '(1 month)',
 '(1 mois)',
 '(1 miesiąc)'),

('user.filter.info.label_quarter',
 '(1 quarter)',
 '(1 trimestre)',
 '(1 kwartał)'),

('user.filter.info.label_year',
 '(1 year)',
 '(1 an)',
 '(1 rok)'),


('user.messages.action.view',
 'View',
 'Voir',
 'Zobacz'),


('user.news.group.articles',
 'Articles',
 'Articles',
 'Artykuły'),

('user.news.group.events',
 'Events',
 'Événements',
 'Wydarzenia'),

('user.news.group.messages',
 'Messages',
 'Messages',
 'Wiadomości'),

('user.news.group.presentations',
 'Presentations',
 'Présentations',
 'Prezentacje'),

('user.news.group.surveys',
 'Surveys',
 'Sondages',
 'Ankiety'),


('user.news.item.by',
 'By',
 'Par',
 'Przez'),


('user.news.empty.title',
 'No news',
 'Aucune nouvelle',
 'Brak nowości'),

('user.news.empty.since_signout',
 'No updates since your last sign-out.',
 'Aucune nouveauté depuis votre dernière déconnexion.',
 'Brak nowości od ostatniego wylogowania.'),

('user.news.empty.since_signin',
 'No updates since your last sign-in.',
 'Aucune nouveauté depuis votre dernière connexion.',
 'Brak nowości od ostatniego logowania.'),

('user.news.empty.since_week',
 'No updates in the past week.',
 'Aucune nouveauté depuis une semaine.',
 'Brak nowości od tygodnia.'),

('user.news.empty.since_month',
 'No updates in the past month.',
 'Aucune nouveauté depuis un mois.',
 'Brak nowości od miesiąca.'),

('user.news.empty.since_quarter',
 'No updates in the past quarter.',
 'Aucune nouveauté depuis un trimestre.',
 'Brak nowości od kwartału.'),

('user.news.empty.since_year',
 'No updates in the past year.',
 'Aucune nouveauté depuis un an.',
 'Brak nowości od roku.');

SQL;

        $pdo->exec($sql);

        $deleteSql = <<<SQL
DELETE FROM Languages
WHERE Name IN (
    'user.messages.filter.since_signout',
    'user.messages.filter.since_signin',
    'user.messages.filter.since_week',
    'user.messages.filter.since_month',
    'user.messages.filter.since_quarter',
    'user.messages.filter.since_year',

    'user.messages.empty.since_signout',
    'user.messages.empty.since_signin',
    'user.messages.empty.since_week',
    'user.messages.empty.since_month',
    'user.messages.empty.since_quarter',
    'user.messages.empty.since_year',

);
SQL;
        $pdo->exec($deleteSql);

        return 51;
    }
}