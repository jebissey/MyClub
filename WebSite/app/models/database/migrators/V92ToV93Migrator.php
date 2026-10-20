<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use RuntimeException;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V92ToV93Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $statement = $pdo->query(<<<'SQL'
SELECT Name, COUNT(*) AS Total
FROM Languages
GROUP BY Name
HAVING COUNT(*) > 1
LIMIT 10
SQL);

        if ($statement === false) {
            throw new RuntimeException('Failed to check for duplicate language names.');
        }
        $duplicates = $statement->fetchAll(PDO::FETCH_ASSOC);

        if ($duplicates !== []) {
            $names = array_column($duplicates, 'Name');

            throw new RuntimeException(
                'Cannot create a unique index on Languages.Name. '
                . 'Duplicate keys: ' . implode(', ', $names)
            );
        }

        $pdo->exec(<<<'SQL'
CREATE UNIQUE INDEX "UX_Languages_Name" ON "Languages" ("Name");

INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('Admin', 'Admin', 'Administration', 'Administrator'),
('Designer', 'Designer', 'Concepteur', 'Projektant'),
('EventManager', 'Event manager', 'Gestionnaire des événements', 'Menedżer wydarzeń'),
('PersonManager', 'Member manager', 'Gestionnaire des membres', 'Menedżer członków'),
('Redactor', 'Editor', 'Rédacteur', 'Redaktor'),
('User', 'User', 'Utilisateur', 'Użytkownik'),
('VisitorInsights', 'Visitor insights', 'Analyse des visiteurs', 'Analiza odwiedzających'),
('Webmaster', 'Webmaster', 'Webmaster', 'Webmaster'),
('account', 'Account', 'Compte', 'Konto'),
('admin_zone', 'Administration area', 'Zone d’administration', 'Strefa administracyjna'),
('attribute_details', 'Attribute details', 'Détails de l’attribut', 'Szczegóły atrybutu'),
('attributes', 'Attributes', 'Attributs', 'Atrybuty'),
('audience', 'Audience', 'Public', 'Odbiorcy'),
('availabilities', 'Availabilities', 'Disponibilités', 'Dostępności'),

('backup.default_file_name', 'backup', 'sauvegarde', 'kopia-zapasowa'),
('backup.form.fallback_note', 
    'Your browser does not support the save file picker; the file will be downloaded automatically.', 
    'Votre navigateur ne prend pas en charge le sélecteur d’enregistrement ; le fichier sera téléchargé automatiquement.', 
    'Twoja przeglądarka nie obsługuje okna zapisu pliku; plik zostanie pobrany automatycznie.'),
('backup.form.file_name', 'File name', 'Nom du fichier', 'Nazwa pliku'),
('backup.form.submit', 'Download backup', 'Télécharger la sauvegarde', 'Pobierz kopię zapasową'),
('backup.form.submitting', 'Creating backup…', 'Création de la sauvegarde…', 'Tworzenie kopii zapasowej…'),
('backup.help', 
    'Download a ZIP archive of the WebSite/data folder.', 
    'Téléchargez une archive ZIP du dossier WebSite/data.', 
    'Pobierz archiwum ZIP folderu WebSite/data.'),
('backup.history.date', 'Date', 'Date', 'Data'),
('backup.history.email', 'Email', 'Courriel', 'E-mail'),
('backup.history.empty', 
    'No backup found in the logs.', 
    'Aucune sauvegarde trouvée dans les journaux.', 
    'Nie znaleziono kopii zapasowych w logach.'),
('backup.history.title', 'Last 10 backups', '10 dernières sauvegardes', 'Ostatnie 10 kopii zapasowych'),
('backup.js.done', 
    'Backup downloaded successfully.', 
    'Sauvegarde téléchargée avec succès.', 
    'Kopia zapasowa została pobrana pomyślnie.'),
('backup.js.error', 'Backup failed', 'Échec de la sauvegarde', 'Nie udało się utworzyć kopii zapasowej'),
('backup.js.file_type', 'ZIP archive', 'Archive ZIP', 'Archiwum ZIP'),
('backup.last_backup', 'Last backup', 'Dernière sauvegarde', 'Ostatnia kopia zapasowa'),
('backup.last_backup.never', 'No backup yet', 'Aucune sauvegarde effectuée', 'Brak kopii zapasowych'),
('backup.title', 'Backup', 'Sauvegarde', 'Kopia zapasowa'),

('cancel', 'Cancel', 'Annuler', 'Anuluj'),
('close', 'Close', 'Fermer', 'Zamknij'),
('communication.api.smtp_not_configured', 
    'Email sending is not configured.', 
    'L’envoi de courriels n’est pas configuré.', 
    'Wysyłanie wiadomości e-mail nie jest skonfigurowane.'),
('connection_required', 
    'You must be logged in to perform this action.', 
    'Vous devez être connecté pour effectuer cette action.', 
    'Aby wykonać tę czynność, musisz się zalogować.'),
('connections', 'Connections', 'Connexions', 'Połączenia'),
('contextual_help', 'Contextual help', 'Aide contextuelle', 'Pomoc kontekstowa'),
('created_by', 'Created by', 'Créé par', 'Utworzone przez'),
('custom_fields.delete_confirm', 
    'Are you sure you want to delete this field?', 
    'Voulez-vous vraiment supprimer ce champ ?', 
    'Czy na pewno chcesz usunąć to pole?'),
('date_time', 'Date and time', 'Date et heure', 'Data i godzina'),
('delete', 'Delete', 'Supprimer', 'Usuń'),
('description', 'Description', 'Description', 'Opis'),
('directory', 'Directory', 'Annuaire', 'Katalog członków'),
('duplicate', 'Duplicate', 'Dupliquer', 'Duplikuj'),
('duration', 'Duration', 'Durée', 'Czas trwania'),
('edit', 'Edit', 'Modifier', 'Edytuj'),
('eventsAvailableForAll', 'Events available to everyone', 'Événements accessibles à tous', 'Wydarzenia dostępne dla wszystkich'),
('eventsAvailableForYou', 'Events available to you', 'Événements accessibles pour vous', 'Wydarzenia dostępne dla Ciebie'),
('filter', 'Filter', 'Filtrer', 'Filtruj'),
('filters', 'Filters', 'Filtres', 'Filtry'),
('fullyBooked', 'Fully booked', 'Complet', 'Brak wolnych miejsc'),
('groups', 'Groups', 'Groupes', 'Grupy'),
('helloasso.field.org_slug', 
    'HelloAsso organization slug', 
    'Identifiant de l’organisation HelloAsso', 
    'Identyfikator organizacji HelloAsso'),
('helloasso.field.org_slug.hint', 
    'Enter the identifier used in your HelloAsso organization URL.', 
    'Saisissez l’identifiant présent dans l’URL de votre organisation HelloAsso.', 
    'Wprowadź identyfikator z adresu URL organizacji HelloAsso.'),
('help_missing', 'Help article not found', 'Article d’aide introuvable', 'Nie znaleziono artykułu pomocy'),
('home', 'Home', 'Accueil', 'Strona główna'),
('language', 'Language', 'Langue', 'Język'),
('loading', 'Loading…', 'Chargement…', 'Ładowanie…'),
('location', 'Location', 'Lieu', 'Miejsce'),
('login', 'Log in', 'Connexion', 'Zaloguj się'),
('logout', 'Log out', 'Déconnexion', 'Wyloguj się'),
('membership', 'Membership', 'Adhésion', 'Członkostwo'),
('message_email_unknown', 'This email address is not known.', 'Cette adresse courriel est inconnue.', 'Ten adres e-mail nie jest znany.'),
('message_helloasso_not_configured', 
    'HelloAsso is not configured.', 
    'HelloAsso n’est pas configuré.', 
    'HelloAsso nie jest skonfigurowane.'),
('message_password_reset_failed', 
    'The password reset request could not be processed.', 
    'La demande de réinitialisation du mot de passe n’a pas pu aboutir.', 
    'Nie udało się przetworzyć żądania resetowania hasła.'),
('message_password_reset_sent', 
    'Instructions to reset your password have been sent by email.', 
    'Les instructions de réinitialisation du mot de passe ont été envoyées par courriel.', 
    'Instrukcje resetowania hasła zostały wysłane e-mailem.'),
('messages', 'Messages', 'Messages', 'Wiadomości'),
('modified_on', 'Modified on', 'Modifié le', 'Data modyfikacji'),
('my_data', 'My data', 'Mes données', 'Moje dane'),
('navbar.admin.backup_manager', 'Backup manager', 'Gestionnaire de sauvegardes', 'Menedżer kopii zapasowych'),
('news', 'News', 'Actualités', 'Aktualności'),
('next', 'Next', 'Suivant', 'Dalej'),
('noAttributes', 'No attributes', 'Aucun attribut', 'Brak atrybutów'),
('noParticipant', 'No participants', 'Aucun participant', 'Brak uczestników'),
('notepad', 'Notepad', 'Bloc-notes', 'Notatnik'),
('on', 'On', 'Le', 'Dnia'),
('participants', 'Participants', 'Participants', 'Uczestnicy'),
('preferences', 'Preferences', 'Préférences', 'Preferencje'),
('previous', 'Previous', 'Précédent', 'Wstecz'),
('quick_actions', 'Quick actions', 'Actions rapides', 'Szybkie działania'),
('register', 'Register', 'S’inscrire', 'Zapisz się'),
('reset', 'Reset', 'Réinitialiser', 'Resetuj'),
('save', 'Save', 'Enregistrer', 'Zapisz'),
('sendEmail', 'Send email', 'Envoyer un courriel', 'Wyślij e-mail'),
('statistics', 'Statistics', 'Statistiques', 'Statystyki'),
('summary', 'Summary', 'Résumé', 'Podsumowanie'),
('type', 'Type', 'Type', 'Typ'),
('unregister', 'Unregister', 'Se désinscrire', 'Wypisz się'),
('vote', 'Vote', 'Voter', 'Głosuj');

SQL);

        return 93;
    }
}
