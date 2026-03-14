<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V21ToV22Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("
        ALTER TABLE Person 
        ADD COLUMN MyPublicDataInPresentationDirectory TEXT
    ");

        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('presentation.edit.title',
 'Edit my presentation',
 'Édition de ma présentation',
 'Edytuj moją prezentację'),

('presentation.edit.inDirectory',
 'I wish to appear in the directory',
 'Je souhaite figurer dans le trombinoscope',
 'Chcę figurować w katalogu'),

('presentation.edit.inDirectory.hint',
 'Your presentation will be visible to other members',
 'Votre présentation sera visible par les autres membres',
 'Twoja prezentacja będzie widoczna dla innych członków'),

('presentation.edit.showPhone',
 'Display my phone number in the directory',
 'Afficher mon numéro de téléphone dans le trombinoscope',
 'Wyświetl mój numer telefonu w katalogu'),

('presentation.edit.showEmail',
 'Display my email address in the directory',
 'Afficher mon adresse e-mail dans le trombinoscope',
 'Wyświetl mój adres e-mail w katalogu'),

('presentation.edit.showLocation',
 'Display my location on the public map',
 'Afficher ma localisation sur la carte publique',
 'Wyświetl moją lokalizację na publicznej mapie'),

('presentation.edit.publicLocation.label',
 'Description of your public location',
 'Description de votre localisation publique',
 'Opis Twojej publicznej lokalizacji'),

('presentation.edit.publicLocation.placeholder',
 'You can contact me at 06... if a swarm is near my neighborhood and accessible',
 'Vous pouvez me contacter au 06... si un essaim est proche de mon quartier et accessible',
 'Możesz skontaktować się ze mną pod nr 06... jeśli rój jest blisko mojej dzielnicy i dostępny'),

('presentation.edit.publicLocation.hint',
 'This text will be displayed when clicking on your pin in the directory',
 'Ce texte sera affiché lors du clic sur votre punaise dans le trombinoscope',
 'Ten tekst będzie wyświetlany po kliknięciu pinezki w katalogu'),

('presentation.edit.content.label',
 'My presentation',
 'Ma présentation',
 'Moja prezentacja'),

('presentation.edit.location.label',
 'Place of residence (neighborhood)',
 'Lieu d''habitation (quartier)',
 'Miejsce zamieszkania (dzielnica)'),

('presentation.edit.location.hint',
 'Click on the map to indicate your neighborhood',
 'Cliquez sur la carte pour indiquer votre quartier d''habitation',
 'Kliknij na mapę, aby wskazać swoją dzielnicę'),

('presentation.edit.validation.noContent',
 'Please write your presentation before appearing in the directory',
 'Veuillez rédiger votre présentation avant de figurer dans le trombinoscope',
 'Proszę napisać swoją prezentację przed pojawieniem się w katalogu'),
 
('directory.index.title',
 'Trombinoscope',
 'Trombinoscope',
 'Trombinoskop'),

('directory.index.subtitle',
 'Browse members who have chosen to share their profile',
 'Découvrez les membres qui ont choisi de partager leur présentation',
 'Przeglądaj członków, którzy zdecydowali się udostępnić swój profil'),

('directory.index.locate_public',
 'Locate public members',
 'Localiser les membres publics',
 'Znajdź publicznych członków'),

('directory.index.locate_members',
 'Locate members',
 'Localiser les membres',
 'Znajdź członków'),

('directory.index.edit_presentation',
 'Edit my presentation',
 'Modifier ma présentation',
 'Edytuj moją prezentację'),

('directory.index.create_presentation',
 'Create my presentation',
 'Créer ma présentation',
 'Utwórz moją prezentację'),

('directory.index.filter_by_group',
 'Filter by group',
 'Filtrer par groupe',
 'Filtruj według grupy'),

('directory.index.all',
 'All',
 'Tous',
 'Wszyscy'),

('directory.index.view_profile',
 'View profile',
 'Voir le profil',
 'Zobacz profil'),

('directory.index.no_members',
 'No member has yet created a presentation in the directory.',
 'Aucun membre n''a encore créé de présentation dans le trombinoscope.',
 'Żaden członek nie utworzył jeszcze prezentacji w katalogu.'),

('directory.index.no_members_group',
 'No member of this group has yet created a presentation in the directory.',
 'Aucun membre de ce groupe n''a encore créé de présentation dans le trombinoscope.',
 'Żaden członek tej grupy nie utworzył jeszcze prezentacji w katalogu.');
SQL;
        $pdo->exec($sql);

        return 22;
    }
}
