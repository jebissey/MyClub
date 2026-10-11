<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V90ToV91Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO "Authorization" VALUES (16, 'BackupManager');

ALTER TABLE "Metadata" ADD COLUMN "Backup_lastDate" TEXT
    CHECK("Backup_lastDate" IS NULL OR ("Backup_lastDate" LIKE '____-__-__ __:__:__' AND datetime("Backup_lastDate") IS NOT NULL));

INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('navbar.admin.backup_manager',
 'Backup manager',
 'Gestionnaire de sauvegardes',
 'Menedżer kopii zapasowych'),
 
('backup.title',
 'Backup',
 'Sauvegarde',
 'Kopia zapasowa'),

('backup.help',
 'Download a ZIP archive of the WebSite/data folder.',
 'Téléchargez une archive ZIP du dossier WebSite/data.',
 'Pobierz archiwum ZIP folderu WebSite/data.'),

('backup.default_file_name',
 'backup',
 'sauvegarde',
 'kopia-zapasowa'),

('backup.form.file_name',
 'File name',
 'Nom du fichier',
 'Nazwa pliku'),

('backup.form.submit',
 'Download backup',
 'Télécharger la sauvegarde',
 'Pobierz kopię zapasową'),

('backup.form.submitting',
 'Creating backup…',
 'Création de la sauvegarde…',
 'Tworzenie kopii zapasowej…'),

('backup.form.fallback_note',
 'Your browser does not support the save file picker; the file will be downloaded automatically.',
 'Votre navigateur ne prend pas en charge le sélecteur d''enregistrement ; le fichier sera téléchargé automatiquement.',
 'Twoja przeglądarka nie obsługuje okna zapisu pliku; plik zostanie pobrany automatycznie.'),

('backup.js.done',
 'Backup downloaded successfully.',
 'Sauvegarde téléchargée avec succès.',
 'Kopia zapasowa została pobrana pomyślnie.'),

('backup.js.error',
 'Backup failed',
 'Échec de la sauvegarde',
 'Nie udało się utworzyć kopii zapasowej'),

('backup.js.file_type',
 'ZIP archive',
 'Archive ZIP',
 'Archiwum ZIP'),

('backup.last_backup',
'Last backup',
'Dernière sauvegarde',
'Ostatnia kopia zapasowa'),

('backup.last_backup.never',
'No backup yet',
'Aucune sauvegarde effectuée',
'Brak kopii zapasowych'),

('backup.history.title',
'Last 10 backups',
'10 dernières sauvegardes',
'Ostatnie 10 kopii zapasowych'),

('backup.history.date',
'Date',
'Date',
'Data'),

('backup.history.email',
'Email',
'Courriel',
'E-mail'),

('backup.history.empty',
'No backup found in the logs.',
'Aucune sauvegarde trouvée dans les logs.',
'Nie znaleziono kopii zapasowych w logach.');
SQL);

        return 91;
    }
}
