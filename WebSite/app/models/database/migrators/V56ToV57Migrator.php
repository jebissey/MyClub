<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V56ToV57Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('layout.alert.test_site.title',
'ATTENTION: Test Site',
'ATTENTION : Site de test',
'UWAGA: Witryna testowa'),

('layout.alert.test_site.message',
'You are on a test environment.',
'Vous êtes sur un environnement de test.',
'Jesteś w środowisku testowym.'),

('layout.alert.test_site.link',
'Access the production site',
'Accéder au site de production',
'Przejdź do witryny produkcyjnej'),

('layout.sidebar.toggle.title',
'Show/hide menu',
'Afficher/masquer le menu',
'Pokaż/ukryj menu'),

('layout.save_indicator.title',
'Remember to save your changes',
'Penser à enregistrer les modifications',
'Pamiętaj o zapisaniu zmian'),

('layout.footer.legal_notice',
'Legal notice',
'Mentions légales',
'Informacje prawne'),

('layout.footer.tutorials',
'Tutorials',
'Tutoriels',
'Samouczki'),

('layout.save_guard.unsaved_warning',
'Unsaved changes will be lost. Do you want to leave the page?',
'Des modifications non enregistrées seront perdues. Voulez-vous quitter la page ?',
'Niezapisane zmiany zostaną utracone. Czy chcesz opuścić stronę?'),

('layout.pwa.ios_install.message',
'Install <strong>MyClub</strong> on your iPhone: tap <strong>⎋ Share</strong> then <strong>«Add to Home Screen»</strong>',
'Installez <strong>MyClub</strong> sur votre iPhone : appuyez sur <strong>⎋ Partager</strong> puis <strong>« Sur l''écran d''accueil »</strong>',
'Zainstaluj <strong>MyClub</strong> na swoim iPhonie: naciśnij <strong>⎋ Udostępnij</strong> a następnie <strong>«Na ekranie głównym»</strong>');

SQL;
        $pdo->exec($sql);

        return 57;
    }
}
