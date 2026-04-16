<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V44ToV45Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES

('navbar.webmaster.club_customization',
'Club customization',
'Personnalisation du club',
'Dostosowanie klubu'),

('webmaster.clubCustomization.title',
'Club customization',
'Personnalisation du club',
'Dostosowanie klubu'),

('webmaster.clubCustomization.description',
'Configure the appearance of your application (name, colors, PWA branding).',
'Configure l’apparence de ton application (nom, couleurs, branding PWA).',
'Skonfiguruj wygląd aplikacji (nazwa, kolory, branding PWA).'),

('webmaster.clubCustomization.clubName',
'Club name',
'Nom du club',
'Nazwa klubu'),

('webmaster.clubCustomization.clubShortName',
'Short name',
'Nom court',
'Krótka nazwa'),

('webmaster.clubCustomization.themeColor',
'Primary color',
'Couleur principale',
'Kolor główny'),

('webmaster.clubCustomization.backgroundColor',
'Background color',
'Couleur fond',
'Kolor tła');

SQL;

        $pdo->exec($sql);

        return 45;
    }
}
