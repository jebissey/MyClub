<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V26ToV27Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('communication.index.deactivated_accounts',
 'Deactivated accounts',
 'Comptes désactivés',
 'Dezaktywowane konta'),

('designer.home_settings.image_banner_desc',
 'Image for the banner',
 'Image pour la bannière',
 'Obraz dla bannera'),

('designer.home_settings.image_home_desc',
 'Image for the Home button in the navigation bar',
 'Image pour le bouton Home de la barre de navigation',
 'Obraz przycisku Home na pasku nawigacji'),

('designer.home_settings.image_logo_desc',
 'Watermark image',
 'Image en filigrane',
 'Obraz znaku wodnego'),

('designer.home_settings.title_edit_images',
 'Image editing',
 'Édition des images',
 'Edycja obrazów'),

('navbar.redactor.crossTab',
 'Editors cross-tab',
 'Tableau croisé des rédacteurs',
 'Tabela krzyżowa redaktorów');
SQL;
        $pdo->exec($sql);

        $pdo->exec("
            UPDATE Languages
            SET fr_FR = 'Gestionnaire des membres'
            WHERE Name = 'navbar.admin.person_manager'
        ");

        $pdo->exec("
            UPDATE Languages
            SET fr_FR = 'Gestionnaire d''événement'
            WHERE Name = 'navbar.admin.event_manager'
        ");

        return 27;
    }
}