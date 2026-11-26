<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V110ToV111Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Fix typo: "arward" -> "award" in translation keys
UPDATE OR IGNORE Languages
SET Name = REPLACE(Name, 'arwards', 'awards')
WHERE Name LIKE 'arwards.%'
   OR Name = 'gameManager.sidebar.other.arwards';

-- Fix typo in the help keys of the /routes page (designer.routes.help.<Class>.<method>)
UPDATE OR IGNORE Languages
SET Name = REPLACE(REPLACE(Name, 'Arwards', 'Awards'), 'Arward', 'Award')
WHERE Name LIKE 'designer.routes.help.%Arward%';

-- Remove any old key left behind because its new name already existed
DELETE FROM Languages
WHERE Name LIKE 'arwards.%'
   OR Name = 'gameManager.sidebar.other.arwards'
   OR Name LIKE 'designer.routes.help.%Arward%';

-- Design creation form: previously hard-coded texts
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('design.create.title', 'Add a new design', 'Ajouter un nouveau design', 'Dodaj nowy projekt'),
('design.create.name', 'Design name', 'Nom du design', 'Nazwa projektu'),
('design.create.detail', 'Details', 'Détails', 'Szczegóły'),
('design.create.navbar', 'Navbar', 'NavBar', 'Pasek nawigacji'),
('design.create.navbar.choose', 'Select a navbar', 'Sélectionnez une NavBar', 'Wybierz pasek nawigacji'),
('design.create.navbar.article', 'Article', 'Article', 'Artykuł'),
('design.create.navbar.award', 'Counter', 'Compteur', 'Licznik'),
('design.create.navbar.get_emails', 'Get emails', 'GetEmails', 'Pobierz e-maile'),
('design.create.audience', 'Who is it for?', 'Pour qui ?', 'Dla kogo?'),
('design.create.audience.all', 'Everyone (All)', 'Pour tous (All)', 'Dla wszystkich (All)'),
('design.create.audience.members', 'Members only (Members)', 'Pour les membres uniquement (Members)', 'Tylko dla członków (Members)'),
('design.create.audience.group', 'A specific group', 'Pour un groupe spécifique', 'Dla wybranej grupy'),
('design.create.group.choose', 'Select a group', 'Sélectionnez un groupe', 'Wybierz grupę'),
('design.create.cancel', 'Cancel', 'Annuler', 'Anuluj'),
('design.create.save', 'Save', 'Enregistrer', 'Zapisz');
SQL);

        return 111;
    }
}
