<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V51ToV52Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('article.edit.view', 'View', 'Voir', 'Zobacz'),
('article.edit.list', 'Articles list', 'Liste des articles', 'Lista artykułów'),

('article.edit.publish', 'Publish', 'Publier', 'Publikuj'),
('article.edit.group', 'Associated group', 'Groupe associé', 'Powiązana grupa'),
('article.edit.no_group', 'No group', 'Aucun groupe', 'Brak grupy'),
('article.edit.members_only', 'Members only', 'Pour les membres du club uniquement', 'Tylko dla członków'),

('article.edit.title', 'Title', 'Titre', 'Tytuł'),
('article.edit.content', 'Content', 'Contenu', 'Treść'),

('article.edit.published', 'Published', 'Publié', 'Opublikowany'),
('article.edit.not_published', 'Not published', 'Non publié', 'Nieopublikowany'),
('article.edit.group_label', 'Group', 'Groupe', 'Grupa'),

('article.edit.created_by', 'Created by', 'Créé par', 'Utworzony przez'),
('article.edit.modified_on', 'Modified on', 'modifié le', 'zmodyfikowany'),
('article.edit.on', 'on', 'le', 'dnia');

SQL;

        $pdo->exec($sql);

        return 52;
    }
}
