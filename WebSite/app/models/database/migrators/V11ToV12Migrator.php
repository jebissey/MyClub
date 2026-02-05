<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V11ToV12Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR)
VALUES

('article.error.not_found',
 'Article {id} does not exist',
 'L''article {id} n''existe pas'),

('article.error.unknown_author',
 'Unknown author for article {id}',
 'Auteur inconnu pour l''article {id}'),

('article.error.login_required',
 'You must be logged in to view this article',
 'Il faut être connecté pour pouvoir consulter cet article'),

('article.error.update_failed',
 'An error occurred while updating the article',
 'Une erreur est survenue lors de la mise à jour de l''article'),

('article.error.title_content_required',
 'Title and content are required',
 'Le titre et le contenu sont obligatoires'),

('article.success.updated',
 'Article successfully updated',
 'L''article a été mis à jour avec succès'),

('article.success.email_sent',
 'Email sent to subscribers',
 'Un courriel a été envoyé aux abonnés'),

('article.email.new_title',
 'A new article is available on {root}',
 'Un nouvel article est disponible sur le site {root}'),

('article.email.body_intro',
 'According to your preferences, this message informs you about a new article',
 'Conformément à vos souhaits, ce message vous signale la présence d''un nouvel article'),

('article.email.unsubscribe',
 'To stop receiving these emails update your preferences',
 'Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences'),

('article.title.crosstab',
 'Redactors vs audience',
 'Rédacteurs vs audience'),

('article.label.created_by',
 'Created by',
 'Créé par'),

('article.label.title',
 'Title',
 'Titre'),

('article.label.last_update',
 'Last update',
 'Dernière modification'),

('article.label.group',
 'Group',
 'Groupe'),

('article.label.published',
 'Published',
 'Publié'),

('article.label.pool',
 'Survey',
 'Sondage'),

('article.label.content',
 'Content',
 'Contenu');

SQL;
        $pdo->exec($sql);

        return 12;
    }
}
