<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V31ToV32Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('attribute_details',
 '🔍 Attribute details',
 '🔍 Détail des attributs',
 '🔍 Szczegóły atrybutów'),

('designer.home_settings.paragraphs_label',
 'Number of paragraphs to display',
 'Nombre de paragraphes à afficher',
 'Liczba akapitów do wyświetlenia'),

('designer.home_settings.paragraphs_description',
 'Limits the number of paragraphs shown from the featured article on the home page.',
 'Limite le nombre de paragraphes affichés de l''article mis en avant sur la page d''accueil.',
 'Ogranicza liczbę akapitów wyświetlanych z wyróżnionego artykułu na stronie głównej.'),

('designer.home_settings.paragraphs_zero_hint',
 '0 = display the entire article',
 '0 = afficher l''article entier',
 '0 = wyświetl cały artykuł'),

('designer.home_settings.section_images',
 'Images',
 'Images',
 'Obrazy'),

('designer.home_settings.article_featured_status',
 'Article #{n} will be displayed on the home page.',
 'L''article #{n} sera affiché en page d''accueil.',
 'Artykuł #{n} zostanie wyświetlony na stronie głównej.'),

('designer.home_settings.article_auto_status',
 'The first paragraph of the last published article (or featured one) will be displayed.',
 'Le 1er paragraphe du dernier article publié (ou celui mis en avant) sera affiché.',
 'Zostanie wyświetlony pierwszy akapit ostatniego opublikowanego artykułu (lub wyróżnionego).'),

('designer.home_settings.paragraphs_all',
 '(entire article)',
 '(article entier)',
 '(cały artykuł)'),

('designer.home_settings.paragraphs_count',
 '({n} paragraph(s))',
 '({n} paragraphe(s))',
 '({n} akapit(y))'),

('designer.home_settings.article_auto_preview_label',
 '1st paragraph of last article / featured article',
 '1er paragraphe du dernier article / article mis en avant',
 '1. akapit ostatniego artykułu / wyróżnionego artykułu'),

('designer.home_settings.latest_hidden_status',
 'The "latest articles" section <strong>will not be displayed</strong>.',
 'La section « derniers articles » <strong>ne sera pas affichée</strong>.',
 'Sekcja „ostatnie artykuły" <strong>nie będzie wyświetlana</strong>.'),

('designer.home_settings.latest_count_status',
 'The <strong>{n}</strong> latest articles will be listed.',
 'Les <strong>{n}</strong> derniers articles seront listés.',
 '<strong>{n}</strong> ostatnich artykułów zostanie wylistowanych.'),

('designer.home_settings.image_processing',
 'Processing…',
 'Traitement en cours…',
 'Przetwarzanie…'),

('designer.home_settings.image_to_save',
 'Unsaved',
 'À sauvegarder',
 'Do zapisania'),

('designer.home_settings.image_read_error',
 'Unable to read the image.',
 'Impossible de lire l''image.',
 'Nie można odczytać obrazu.');
SQL;

        $pdo->exec($sql);

        return 32;
    }
}
