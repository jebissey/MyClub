<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V87ToV88Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
ALTER TABLE Member ADD COLUMN CustomFields TEXT NOT NULL DEFAULT '{}';        
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('custom_fields.title',
'Custom member fields',
'Champs personnalisés des membres',
'Pola niestandardowe członków'),
('custom_fields.help',
'Define the additional fields you want to fill in for each member of the association.',
'Définissez les champs supplémentaires que vous souhaitez renseigner pour chaque membre de l''association.',
'Zdefiniuj dodatkowe pola, które chcesz wypełniać dla każdego członka stowarzyszenia.'),
('custom_fields.label',
'Field name',
'Nom du champ',
'Nazwa pola'),
('custom_fields.add',
'Add a field',
'Ajouter un champ',
'Dodaj pole'),
('custom_fields.remove',
'Remove field',
'Supprimer le champ',
'Usuń pole'),
('custom_fields.type.string',
'Text',
'Texte',
'Tekst'),
('custom_fields.type.number',
'Number',
'Nombre',
'Liczba'),
('custom_fields.type.date',
'Date',
'Date',
'Data'),
('custom_fields.type.date',
'Date',
'Date',
'Data'),
('navbar.person_manager.settings',
'Custom member fields',
'Champs personnalisés des membres',
'Pola niestandardowe członków'),
('custom_fields.delete_confirm',
'Are you sure you want to delete this field? All values already entered for this field will be permanently deleted.',
'Êtes-vous sûr de vouloir supprimer ce champ ? Toutes les valeurs déjà saisies dans ce champ seront définitivement supprimées.',
'Czy na pewno chcesz usunąć to pole? Wszystkie wartości już wprowadzone dla tego pola zostaną trwale usunięte.'),
('custom_fields.delete_failed',
'Failed to delete the field.',
'La suppression du champ a échoué.',
'Nie udało się usunąć pola.');
SQL);

        return 88;
    }
}
