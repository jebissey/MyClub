<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V77ToV78Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('user.groups.select_prompt', 'Select the groups you wish to join to access the associated resources:', 'Sélectionnez les groupes auxquels vous souhaitez appartenir pour accéder aux ressources associées :', 'Wybierz grupy, do których chcesz należeć, aby uzyskać dostęp do powiązanych zasobów:'),
('user.groups.managed_info',  'An administrator has added you to these groups. If you no longer wish to be a member, you must submit a request to them.', 'Un administrateur vous a inscrit dans ces groupes. Si vous ne souhaitez plus y figurer, vous devez lui en faire la demande.', 'Administrator zapisał Cię do tych grup. Jeśli nie chcesz już w nich figurować, musisz złożyć mu odpowiedni wniosek.');
SQL);

        return 78;
    }
}