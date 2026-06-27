<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V68ToV69Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('media.manager.action.edit',      'Edit image',   "Modifier l'image", 'Edytuj obraz'),
('media.manager.edit.max_size',    'Max size',     'Taille max',        'Maks. rozmiar'),
('media.manager.edit.reset_crop',  'Reset',        'Réinitialiser',     'Resetuj'),
('media.manager.edit.saving',      'Saving…',      'Enregistrement…',   'Zapisywanie…'),
('media.manager.edit.saved',       'Image saved',  'Image enregistrée', 'Obraz zapisany'),
('media.manager.edit.error',       
    'Error while saving the image', 
    "Erreur lors de l'enregistrement de l'image", 
    'Błąd podczas zapisywania obrazu');
SQL);

        return 69;
    }
}
