<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V112ToV113Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Group list page: messages previously hard-coded in groupsIndex.js
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('person_manager.group.js.no_users',
    'No user in this group.', 'Aucun utilisateur dans ce groupe.', 'Brak użytkowników w tej grupie.'),
('person_manager.group.js.unexpected_response',
    'Unexpected response.', 'Réponse inattendue.', 'Nieoczekiwana odpowiedź.'),
('person_manager.group.js.load_error',
    'Loading error.', 'Erreur de chargement.', 'Błąd ładowania.');

-- Keys no longer referenced: group forms now use the common 'cancel' and 'save' keys,
-- and the member fields page uses custom_fields.delete_confirm
DELETE FROM Languages WHERE Name IN (
    'person_manager.group.create.cancel',
    'person_manager.group.create.save',
    'person_manager.member_fields.delete_confirm'
);
SQL);

        return 113;
    }
}
