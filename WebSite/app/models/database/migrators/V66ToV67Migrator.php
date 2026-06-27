<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V66ToV67Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('designer.home_settings.section_footer_accordion',    
    'Footer accordion',              
    'Accordéon pied de page',                            
    'Akordeon stopki'),
('designer.home_settings.title_edit_footer_accordion', 
    'Edit footer accordion article', 
    "Modifier l'article de l'accordéon du pied de page", 
    'Edytuj artykuł akordeonu stopki'),
('designer.home_settings.footer_accordion_hint',       
    '0 = no article; otherwise enter the article ID to display in the footer accordion.', 
    "0 = aucun article ; sinon, saisir l'ID de l'article à afficher dans l'accordéon du pied de page.", 
    '0 = brak artykułu; w przeciwnym razie podaj ID artykułu do wyświetlenia w akordeonie stopki.'),
('previous', 'Previous', 'Précédent', 'Poprzedni'),
('next',     'Next',     'Suivant',   'Następny');

SQL);

        $pdo->exec("INSERT INTO Languages (Id, Name, en_US, fr_FR, pl_PL) VALUES (24,'ClubMembersOnly','Members','Membres','Członkowie')");

        return 67;
    }
}
