<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V57ToV58Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
-- MediaManager messages
('media_manager.file_not_found',
'File not found',
'Fichier non trouvé',
'Plik nie został znaleziony'),

('media_manager.file_deleted_success',
'File deleted successfully',
'Fichier supprimé avec succès',
'Plik został pomyślnie usunięty'),

('media_manager.file_delete_error',
'Error while deleting file',
'Erreur lors de la suppression du fichier',
'Błąd podczas usuwania pliku'),

('media_manager.file_not_exists',
"File doesn't exist",
"Le fichier n'existe pas",
'Plik nie istnieje'),

('media_manager.file_upload_error',
'Error while saving file',
"Erreur lors de l’enregistrement du fichier",
'Błąd podczas zapisywania pliku'),

('chat.current_image',
'Current image',
'Image actuelle',
'Aktualne zdjęcie'),

('chat.delete_image',
'Delete image',
'Supprimer l''image',
'Usuń zdjęcie'),

('chat.attach_image',
'Attach an image',
'Joindre une image',
'Dołącz zdjęcie'),

-- Message image errors
('message.image_not_found',
'Message not found',
'Message introuvable',
'Wiadomość nie została znaleziona'),

('message.image_not_attached',
'No image attached to this message',
"Aucune image associée à ce message",
'Brak obrazu powiązanego z tą wiadomością'),

('message.image_invalid_path',
'Invalid image path',
"Chemin d'image invalide",
'Nieprawidłowa ścieżka obrazu'),

('message.image_invalid_structure',
'Invalid image path structure',
"Structure du chemin d'image invalide",
'Nieprawidłowa struktura ścieżki obrazu');
SQL;
        $pdo->exec($sql);

        $pdo->exec("ALTER TABLE Message ADD COLUMN ImagePath TEXT");

        return 58;
    }
}
