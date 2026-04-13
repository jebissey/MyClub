<?php
declare(strict_types=1);
namespace app\models\database\migrators;
use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V42ToV43Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('person_manager.registration.title',
'Registrations',
'Inscriptions',
'Rejestracje'),

('person_manager.registration.page_title',
'Group Registration',
'Inscription aux groupes',
'Rejestracja do grup'),

('person_manager.registration.column.last_name',
'Last Name',
'Nom',
'Nazwisko'),

('person_manager.registration.column.first_name',
'First Name',
'Prénom',
'Imię'),

('person_manager.registration.column.nickname',
'Nickname',
'Surnom',
'Pseudonim'),

('person_manager.registration.modal.title',
'Group Management',
'Gestion des groupes',
'Zarządzanie grupami'),

('person_manager.registration.groups.current',
'Current removable groups',
'Groupes actuels supprimables',
'Aktualne grupy możliwe do usunięcia'),

('person_manager.registration.groups.available',
'Available groups to add',
'Groupes disponibles ajoutables',
'Dostępne grupy możliwe do dodania'),

('person_manager.registration.action.remove',
'Remove',
'Retirer',
'Usuń'),

('person_manager.registration.action.add',
'Add',
'Ajouter',
'Dodaj'),

('person_manager.registration.error.load_groups',
'Unable to load groups',
'Impossible de charger les groupes',
'Nie można załadować grup'),

('person_manager.registration.error.generic',
'An error occurred',
'Une erreur est survenue',
'Wystąpił błąd');
SQL;
        $pdo->exec($sql);

        return 43;
    }
}