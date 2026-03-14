<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V22ToV23Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('article.show.title',
 'Articles',
 'Articles',
 'Artykuły'),

('article.show.notify_subscribers',
 'Notify subscribers',
 'Prévenir les abonnés',
 'Powiadom subskrybentów'),

('article.show.manage_gallery',
 'Manage gallery',
 'Gérer la galerie',
 'Zarządzaj galerią'),

('article.show.edit_survey',
 'Edit survey',
 'Modifier le sondage',
 'Edytuj ankietę'),

('article.show.add_survey',
 'Add a survey',
 'Ajouter un sondage',
 'Dodaj ankietę'),

('article.show.edit_order',
 'Edit group order',
 'Modifier la commande groupée',
 'Edytuj zamówienie grupowe'),

('article.show.add_order',
 'Add a group order',
 'Ajouter une commande groupée',
 'Dodaj zamówienie grupowe'),

('article.show.only_creator_can_edit',
 'Only the article creator can edit it',
 'Seul le créateur de l''article peut le modifier',
 'Tylko twórca artykułu może go edytować'),

('article.show.view_survey_results',
 'View survey results',
 'Voir résultats sondage',
 'Zobacz wyniki ankiety'),

('article.show.reply_survey',
 'Reply to survey',
 'Répondre au sondage',
 'Odpowiedz na ankietę'),

('article.show.view_order_results',
 'View order results',
 'Voir résultats commande',
 'Zobacz wyniki zamówienia'),

('article.show.reply_order',
 'Reply to group order',
 'Répondre pour la commande groupée',
 'Odpowiedz na zamówienie grupowe'),

('article.show.created_by',
 'Created by',
 'Créé par',
 'Utworzony przez'),

('article.show.on_date',
 'on',
 'le',
 'dnia'),

('article.show.modified_on',
 'modified on',
 'modifié le',
 'zmodyfikowano dnia'),

('article.show.published',
 'Published',
 'Publié',
 'Opublikowany'),

('article.show.not_published',
 'Not published',
 'Non publié',
 'Nieopublikowany'),

('article.show.group_label',
 'Group:',
 'Groupe:',
 'Grupa:'),

('article.show.gallery',
 'Gallery',
 'Galerie',
 'Galeria'),

('article.show.previous',
 'Previous',
 'Précédent',
 'Poprzedni'),

('article.show.next',
 'Next',
 'Suivant',
 'Następny'),

('article.show.modal_survey_title',
 'Reply to survey',
 'Répondre au sondage',
 'Odpowiedz na ankietę'),

('article.show.modal_survey_loading',
 'Loading survey...',
 'Chargement du sondage...',
 'Ładowanie ankiety...'),

('article.show.modal_order_title',
 'Reply to order',
 'Répondre à la commande',
 'Odpowiedz na zamówienie'),

('article.show.modal_order_loading',
 'Loading order...',
 'Chargement de la commande...',
 'Ładowanie zamówienia...');
SQL;
        $pdo->exec($sql);

        return 23;
    }
}
