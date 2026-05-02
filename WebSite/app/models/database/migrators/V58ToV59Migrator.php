<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V58ToV59Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('media.manager.table.message',
'Message',
'Message',
'Wiadomość'),

('media.uses.title',
'Where is this media used?',
'Où est utilisé ce média ?',
'Gdzie jest używane to medium?'),

('media.uses.in_articles',
'This media is used in the following articles:',
'Ce média est utilisé dans les articles suivants :',
'To medium jest używane w następujących artykułach:'),

('media.uses.no_articles',
'This media is not used in any article.',
'Ce média n''est utilisé dans aucun article.',
'To medium nie jest używane w żadnym artykule.'),

('media.uses.view_article',
'View article',
'Voir l''article',
'Zobacz artykuł'),

('media.uses.in_event_messages',
'This media appears in messages of the following events:',
'Ce média apparaît dans les messages des événements suivants :',
'To medium pojawia się w wiadomościach następujących wydarzeń:'),

('media.uses.in_article_messages',
'This media appears in messages of the following articles:',
'Ce média apparaît dans les messages des articles suivants :',
'To medium pojawia się w wiadomościach następujących artykułów:'),

('media.uses.in_group_messages',
'This media appears in messages of the following groups:',
'Ce média apparaît dans les messages des groupes suivants :',
'To medium pojawia się w wiadomościach następujących grup:'),

('media.uses.no_messages',
'This media is not used in any message.',
'Ce média n''est utilisé dans aucun message.',
'To medium nie jest używane w żadnej wiadomości.'),

('media.uses.view_event',
'View event',
'Voir l''événement',
'Zobacz wydarzenie'),

('media.uses.view_group',
'View group',
'Voir le groupe',
'Zobacz grupę');
SQL;
        $pdo->exec($sql);

        return 59;
    }
}
