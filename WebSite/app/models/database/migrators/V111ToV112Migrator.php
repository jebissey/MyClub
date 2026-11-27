<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V111ToV112Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Group creation form: previously hard-coded texts
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('person_manager.group.create.title', 'Create a group', 'Créer un groupe', 'Utwórz grupę'),
('person_manager.group.create.name', 'Name', 'Nom', 'Nazwa'),
('person_manager.group.create.self_registration', 
    'Self-registration allowed', 'Auto-inscription autorisée', 'Samodzielna rejestracja dozwolona'),
('person_manager.group.create.authorizations', 'Authorizations', 'Autorisations', 'Uprawnienia'),
('person_manager.group.create.no_authorization', 
    'No authorization available', 'Aucune autorisation disponible', 'Brak dostępnych uprawnień'),
('person_manager.group.create.cancel', 'Cancel', 'Annuler', 'Anuluj'),
('person_manager.group.create.save', 'Save', 'Enregistrer', 'Zapisz');
SQL);

        return 112;
    }
}
