<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V72ToV73Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('media.upload.title',           'Media file upload',              'Upload de fichiers médias',       'Przesyłanie plików multimedialnych'),
('media.upload.select_file',     'Select a file',                  'Sélectionner un fichier',         'Wybierz plik'),
('media.upload.success_title',   'Files uploaded successfully',    'Fichiers uploadés avec succès',   'Pliki przesłane pomyślnie'),
('media.upload.col_name',        'Name',                           'Nom',                             'Nazwa'),
('media.upload.col_url',         'URL',                            'URL',                             'URL');
SQL);

        return 73;
    }
}
