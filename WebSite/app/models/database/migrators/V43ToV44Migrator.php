<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V43ToV44Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('media.manager.title',
 'Media Manager',
 'Gestionnaire de médias',
 'Menedżer mediów'),

('media.manager.upload_button',
 'Upload a file',
 'Uploader un fichier',
 'Prześlij plik'),

('media.manager.filtered',
 '(filtered)',
 '(filtrés)',
 '(przefiltrowane)'),

('media.manager.month_placeholder',
 'Month',
 'Mois',
 'Miesiąc'),

('media.manager.type_placeholder',
 'Type',
 'Type',
 'Typ'),

('media.manager.unused_only',
 'Unused only',
 'Non utilisés',
 'Tylko nieużywane'),

('media.manager.search_placeholder',
 'Search...',
 'Rechercher...',
 'Szukaj...'),

('media.manager.table.preview',
 'Preview',
 'Aperçu',
 'Podgląd'),

('media.manager.table.name',
 'Name',
 'Nom',
 'Nazwa'),

('media.manager.table.date',
 'Date',
 'Date',
 'Data'),

('media.manager.table.size',
 'Size',
 'Taille',
 'Rozmiar'),

('media.manager.table.article',
 'Article',
 'Article',
 'Artykuł'),

('media.manager.table.carousel',
 'Carousel',
 'Carousel',
 'Karuzela'),

('media.manager.table.shared',
 'Shared',
 'Partagé',
 'Udostępniony'),

('media.manager.table.actions',
 'Actions',
 'Actions',
 'Akcje'),

('media.manager.table.yes',
 'Yes',
 'Oui',
 'Tak'),

('media.manager.video_unsupported',
 'Your browser does not support video playback.',
 'Votre navigateur ne supporte pas la lecture vidéo.',
 'Twoja przeglądarka nie obsługuje odtwarzania wideo.'),

('media.manager.audio_unsupported',
 'Your browser does not support audio playback.',
 'Votre navigateur ne supporte pas la lecture audio.',
 'Twoja przeglądarka nie obsługuje odtwarzania audio.'),

('media.manager.no_results',
 'No files match your search.',
 'Aucun fichier ne correspond à votre recherche.',
 'Brak plików pasujących do wyszukiwania.'),

('media.manager.action.view_map',
 'View on map',
 'Voir sur carte',
 'Zobacz na mapie'),

('media.manager.action.view',
 'View',
 'Voir',
 'Zobacz'),

('media.manager.action.copy_url',
 'Copy URL',
 'Copier l''URL',
 'Kopiuj URL'),

('media.manager.action.share',
 'Share',
 'Partager',
 'Udostępnij'),

('media.manager.action.delete',
 'Delete',
 'Supprimer',
 'Usuń'),

('media.manager.share.modal_title',
 'Share file',
 'Partager le fichier',
 'Udostępnij plik'),

('media.manager.share.file_label',
 'File:',
 'Fichier :',
 'Plik:'),

('media.manager.share.group_label',
 'Associated group',
 'Groupe associé',
 'Powiązana grupa'),

('media.manager.share.no_group',
 '-- No group --',
 '-- Aucun groupe --',
 '-- Brak grupy --'),

('media.manager.share.members_only',
 'For club members only',
 'Pour les membres du club uniquement',
 'Tylko dla członków klubu'),

('media.manager.share.link_label',
 'Share link:',
 'Lien de partage :',
 'Link udostępniania:'),

('media.manager.share.copy',
 'Copy',
 'Copier',
 'Kopiuj'),

('media.manager.share.close',
 'Close',
 'Fermer',
 'Zamknij'),

('media.manager.share.create',
 'Create share',
 'Créer le partage',
 'Utwórz udostępnienie'),

('media.manager.share.delete',
 'Delete share',
 'Supprimer le partage',
 'Usuń udostępnienie'),

('media.manager.share.url_copied',
 'URL copied!',
 'URL copié !',
 'URL skopiowany!'),

('media.manager.share.link_copied',
 'Link copied!',
 'Lien copié !',
 'Link skopiowany!'),

('media.manager.share.created',
 'Share created successfully.',
 'Partage créé avec succès.',
 'Udostępnienie utworzone pomyślnie.'),

('media.manager.share.deleted',
 'Share deleted.',
 'Partage supprimé.',
 'Udostępnienie usunięte.'),

('media.manager.share.error',
 'An error occurred.',
 'Une erreur est survenue.',
 'Wystąpił błąd.'),

('media.manager.delete.confirm',
 'Are you sure you want to delete this file?',
 'Êtes-vous sûr de vouloir supprimer ce fichier ?',
 'Czy na pewno chcesz usunąć ten plik?'),

('media.manager.delete.success',
 'File deleted.',
 'Fichier supprimé.',
 'Plik usunięty.'),

('media.manager.delete.error',
 'Error deleting file.',
 'Erreur lors de la suppression du fichier.',
 'Błąd podczas usuwania pliku.');
SQL;
        $pdo->exec($sql);

        return 44;
    }
}
