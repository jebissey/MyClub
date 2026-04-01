<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V32ToV33Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('article.change_owner.title',
 'Change owner',
 'Changer le propriétaire',
 'Zmień właściciela'),

('article.change_owner.current_owner',
 'Current owner:',
 'Propriétaire actuel :',
 'Aktualny właściciel:'),

('article.change_owner.new_owner',
 'New owner',
 'Nouveau propriétaire',
 'Nowy właściciel'),

('article.change_owner.select_redactor',
 '-- Select a redactor --',
 '-- Sélectionner un rédacteur --',
 '-- Wybierz redaktora --'),

('article.publish.title',
 'Article publication',
 'Publication de l''article',
 'Publikacja artykułu'),

('article.publish.created_by',
 'Created by:',
 'Créé par :',
 'Utworzony przez:'),

('article.publish.is_published',
 'Article published',
 'Article publié',
 'Artykuł opublikowany'),

('article.publish.do_publish',
 'Publish this article',
 'Publier cet article',
 'Opublikuj ten artykuł'),

('article.publish.spotlight',
 'Feature this article',
 'Mettre à la une',
 'Wyróżnij ten artykuł'),

('article.publish.spotlight_date',
 'Select the date until which the article will be featured',
 'Sélectionnez la date jusqu''à laquelle l''article sera mis à la une',
 'Wybierz datę, do której artykuł będzie wyróżniony');
SQL;

        $pdo->exec($sql);

        return 33;
    }
}