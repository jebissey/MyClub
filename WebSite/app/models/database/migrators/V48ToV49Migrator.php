<?php
declare(strict_types=1);
namespace app\models\database\migrators;
use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V48ToV49Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('user.messages.title',
 'Messages',
 'Les Messages',
 'Wiadomości'),

('user.messages.filter.since_signout',
 'Since your last sign-out',
 'Depuis votre dernière déconnexion',
 'Od ostatniego wylogowania'),

('user.messages.filter.since_signin',
 'Since your sign-in',
 'Depuis votre connexion',
 'Od ostatniego logowania'),

('user.messages.filter.since_week',
 'Last week',
 'Depuis une semaine',
 'Od tygodnia'),

('user.messages.filter.since_month',
 'Last month',
 'Depuis un mois',
 'Od miesiąca'),

('user.messages.filter.since_quarter',
 'Last quarter',
 'Depuis un trimestre',
 'Od kwartału'),

('user.messages.filter.since_year',
 'Last year',
 'Depuis un an',
 'Od roku'),

('user.messages.info.showing_since',
 'Showing messages since',
 'Affichage des messages depuis le',
 'Wyświetlanie wiadomości od'),

('user.messages.info.label_signout',
 '(last sign-out)',
 '(dernière déconnexion)',
 '(ostatnie wylogowanie)'),

('user.messages.info.label_signin',
 '(last sign-in)',
 '(dernière connexion)',
 '(ostatnie logowanie)'),

('user.messages.info.label_week',
 '(1 week)',
 '(1 semaine)',
 '(1 tydzień)'),

('user.messages.info.label_month',
 '(1 month)',
 '(1 mois)',
 '(1 miesiąc)'),

('user.messages.info.label_quarter',
 '(1 quarter)',
 '(1 trimestre)',
 '(1 kwartał)'),

('user.messages.info.label_year',
 '(1 year)',
 '(1 an)',
 '(1 rok)'),

('user.messages.table.message_count',
 'Messages',
 'Nombre de messages',
 'Liczba wiadomości'),

('user.messages.table.last_update',
 'Last update',
 'Dernière mise à jour',
 'Ostatnia aktualizacja'),

('user.messages.table.actions',
 'Actions',
 'Actions',
 'Akcje'),

('user.messages.group.events',
 '📅 Events',
 '📅 Événements',
 '📅 Wydarzenia'),

('user.messages.group.articles',
 '📄 Articles',
 '📄 Articles',
 '📄 Artykuły'),

('user.messages.group.groups',
 '👥 Groups',
 '👥 Groupes',
 '👥 Grupy'),

('user.messages.action.view_chat',
 'View chat',
 'Voir le chat',
 'Zobacz czat'),

('user.messages.empty.title',
 'No messages',
 'Aucun message',
 'Brak wiadomości'),

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
SQL;
        $pdo->exec($sql);

        return 49;
    }
}