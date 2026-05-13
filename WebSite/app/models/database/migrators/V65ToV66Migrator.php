<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V65ToV66Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('article.label.for_members',                  'For members',              'Pour les membres',            'Dla członków'),
('article.label.menu',                         'Menu',                     'Menu',                        'Menu'),
('article.label.messages',                     'Messages',                 'Messages',                    'Wiadomości'),
('article.label.pool_detail',                  'Pool detail',              'Détail du pool',              'Szczegóły puli'),
('article.label.published',                    'Published',                'Publié',                      'Opublikowany'),
('exercise.msg.invalid_json',                  'Invalid JSON format',      'Format JSON invalide',        'Nieprawidłowy format JSON');
SQL);

        $pdo->exec("DELETE FROM Languages WHERE Name IN (
            'visitor_insights.analytics.period.year', 
            'visitor_insights.analytics.period.week',
            'visitor_insights.analytics.period.month',
            'visitor_insights.analytics.period.day',
            'user.filter.info.label_year',
            'user.filter.info.label_week',
            'user.filter.info.label_signout',
            'user.filter.info.label_signin',
            'user.filter.info.label_quarter',       
            'user.filter.info.label_month'
        )");

        return 66;
    }
}
