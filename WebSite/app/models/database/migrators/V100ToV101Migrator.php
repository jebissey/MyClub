<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V100ToV101Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- User presentation page: texts previously hard-coded in the view
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('user.presentation.avatar_alt',
 'User avatar',
 'Avatar de l''utilisateur',
 'Awatar użytkownika'),

('user.presentation.edit_mine',
 'Edit my presentation',
 'Modifier ma présentation',
 'Edytuj moją prezentację'),

('user.presentation.neighborhood',
 'Neighborhood',
 'Quartier',
 'Dzielnica'),

('user.presentation.contact',
 'Contact',
 'Contact',
 'Kontakt'),

('user.presentation.contact_not_shared',
 'Contact details not shared',
 'Coordonnées non partagées',
 'Dane kontaktowe nie są udostępniane'),

('user.presentation.title',
 'Presentation',
 'Présentation',
 'Prezentacja'),

('user.presentation.none_available',
 'No presentation available',
 'Aucune présentation disponible',
 'Brak dostępnej prezentacji');

-- Home page: texts previously hard-coded in the view
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('home.banner_alt',
 'Banner',
 'Bannière',
 'Baner'),

('home.pending_survey',
 'Pending survey:',
 'Sondage en attente :',
 'Oczekująca ankieta:'),

('home.survey_closing',
 'Closing:',
 'Fermeture :',
 'Zamknięcie:'),

('home.answer_survey',
 'Answer the survey',
 'Répondre au sondage',
 'Odpowiedz na ankietę'),

('home.pending_design',
 'Pending design:',
 'Design en attente :',
 'Oczekujący projekt:'),

('home.design_hint',
 'Click on the row to give your opinion',
 'Cliquer sur la ligne pour donner votre avis',
 'Kliknij wiersz, aby wyrazić swoją opinię'),

('home.give_opinion',
 'Give your opinion',
 'Donner votre avis',
 'Wyraź swoją opinię'),

('home.see_news',
 'See what''s new',
 'Voir les nouveautés',
 'Zobacz nowości'),

('home.read_article',
 'Read the article',
 'Lire l''article',
 'Przeczytaj artykuł'),

('home.recent_articles',
 'Recent articles',
 'Articles récents',
 'Najnowsze artykuły'),

('home.all_articles',
 'List of all articles',
 'Liste de tous les articles',
 'Lista wszystkich artykułów'),

('home.close',
 'Close',
 'Fermer',
 'Zamknij');

 INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('navbar.user.notifications',
 'Notifications',
 'Notifications',
 'Powiadomienia'),

 ('cross_tab.filters',
 'Filters',
 'Filtres',
 'Filtry'),

('cross_tab.period',
 'Period',
 'Période',
 'Okres'),

('cross_tab.period.today',
 'Today',
 'Aujourd''hui',
 'Dzisiaj'),

('cross_tab.period.yesterday',
 'Yesterday',
 'Hier',
 'Wczoraj'),

('cross_tab.period.before_yesterday',
 'Day before yesterday',
 'Avant hier',
 'Przedwczoraj'),

('cross_tab.period.week',
 'Last 7 days',
 '7 derniers jours',
 'Ostatnie 7 dni'),

('cross_tab.period.month',
 'Last 30 days',
 '30 derniers jours',
 'Ostatnie 30 dni'),

('cross_tab.period.quarter',
 'Last quarter',
 'Dernier trimestre',
 'Ostatni kwartał'),

('cross_tab.period.year',
 'Last year',
 'Dernière année',
 'Ostatni rok');

-- User menu: rename translation keys while preserving existing translations
UPDATE Languages SET Name = 'navbar.user.account'
WHERE Name = 'account';

UPDATE Languages SET Name = 'navbar.user.availabilities'
WHERE Name = 'availabilities';

UPDATE Languages SET Name = 'navbar.user.groups'
WHERE Name = 'groups';

UPDATE Languages SET Name = 'navbar.user.preferences'
WHERE Name = 'preferences';

UPDATE Languages SET Name = 'navbar.user.notifications'
WHERE Name = 'notifications';

UPDATE Languages SET Name = 'navbar.user.statistics'
WHERE Name = 'statistics';

UPDATE Languages SET Name = 'navbar.user.directory'
WHERE Name = 'directory';

UPDATE Languages SET Name = 'navbar.user.news'
WHERE Name = 'news';

UPDATE Languages SET Name = 'navbar.user.messages'
WHERE Name = 'messages';

UPDATE Languages SET Name = 'navbar.user.notepad'
WHERE Name = 'notepad';

UPDATE Languages SET Name = 'navbar.user.connections'
WHERE Name = 'connections';

UPDATE Languages SET Name = 'navbar.user.membership'
WHERE Name = 'membership';

-- Remove the obsolete translation entry
DELETE FROM Languages
WHERE Name = 'User';

-- Forgot password e-mail: texts previously hard-coded in AuthenticationService
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('auth.forgot_password.email.subject',
 'Password reset',
 'Initialisation du mot de passe',
 'Resetowanie hasła'),

('auth.forgot_password.email.body',
 'Click on this link to reset your password:',
 'Cliquez sur ce lien pour initialiser votre mot de passe :',
 'Kliknij ten link, aby zresetować hasło:');


 INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.messages',
 'Messages',
 'Messages',
 'Wiadomości');

-- Article carousel management page: texts previously hard-coded in the view
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('article.carousel.title',
 'Carousel management',
 'Gestion du carousel',
 'Zarządzanie karuzelą'),

('article.carousel.article_label',
 'Article:',
 'Article :',
 'Artykuł:'),

('article.carousel.back_to_list',
 'Article list',
 'Liste des articles',
 'Lista artykułów'),

('article.carousel.add_image',
 'Add an image',
 'Ajouter une image',
 'Dodaj obraz'),

('article.carousel.image_source',
 'Image source (URL)',
 'Source de l''image (URL)',
 'Źródło obrazu (URL)'),

('article.carousel.image_source_hint',
 'Enter the full URL of the image',
 'Entrez l''URL complète de l''image',
 'Wprowadź pełny adres URL obrazu'),

('article.carousel.alt_text',
 'Alternative text',
 'Texte alternatif',
 'Tekst alternatywny'),

('article.carousel.alt_placeholder',
 'Image description',
 'Description de l''image',
 'Opis obrazu'),

('article.carousel.add_image_button',
 'Add the image',
 'Ajouter l''image',
 'Dodaj obraz'),

('article.carousel.gallery_title',
 'Carousel images',
 'Images du carousel',
 'Obrazy karuzeli'),

('article.carousel.images_count',
 'image(s)',
 'image(s)',
 'obraz(y)'),

('article.carousel.delete_image',
 'Delete this image',
 'Supprimer cette image',
 'Usuń ten obraz'),

('article.carousel.empty',
 'No images in the carousel',
 'Aucune image dans le carousel',
 'Brak obrazów w karuzeli'),

('article.carousel.empty_hint',
 'Add your first image with the form above',
 'Ajoutez votre première image avec le formulaire ci-dessus',
 'Dodaj pierwszy obraz za pomocą formularza powyżej'),

('article.carousel.delete_confirm_title',
 'Confirm deletion',
 'Confirmer la suppression',
 'Potwierdź usunięcie'),

('article.carousel.delete_confirm_text',
 'Are you sure you want to remove this image from the carousel?',
 'Êtes-vous sûr de vouloir supprimer cette image du carousel ?',
 'Czy na pewno chcesz usunąć ten obraz z karuzeli?'),

('article.carousel.delete',
 'Delete',
 'Supprimer',
 'Usuń');

-- Article carousel management page: messages previously hard-coded in CarouselManager.js
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('article.carousel.js.url_required',
 'Please enter an image URL',
 'Veuillez saisir une URL d''image',
 'Wprowadź adres URL obrazu'),

('article.carousel.js.add_error',
 'Error while adding the image',
 'Erreur lors de l''ajout',
 'Błąd podczas dodawania obrazu'),

('article.carousel.js.delete_error',
 'Error while deleting the image',
 'Erreur lors de la suppression',
 'Błąd podczas usuwania obrazu');

-- Article edit page: texts previously hard-coded in the view
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('article.edit.page_title',
 'Edit',
 'Édition',
 'Edycja'),

('article.edit.created_by',
 'Created by',
 'Créé par',
 'Utworzony przez'),

('article.edit.on_date',
 'on',
 'le',
 'dnia'),

('article.edit.modified_on',
 'modified on',
 'modifié le',
 'zmodyfikowany dnia'),

('article.edit.no_group',
 '-- No group --',
 '-- Aucun groupe --',
 '-- Brak grupy --');

-- Generic data table and pagination components
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('common.table.actions',
 'Actions',
 'Actions',
 'Akcje'),

('common.pagination.nav',
 'Page navigation',
 'Navigation des pages',
 'Nawigacja stron'),

('common.pagination.first',
 'First page',
 'Première page',
 'Pierwsza strona'),

('common.pagination.previous',
 'Previous page',
 'Page précédente',
 'Poprzednia strona'),

('common.pagination.next',
 'Next page',
 'Page suivante',
 'Następna strona'),

('common.pagination.last',
 'Last page',
 'Dernière page',
 'Ostatnia strona'); 

-- Closable forms (survey / order) add page: texts previously hard-coded in the views
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('article.closable_form.closing_date',
 'Closing date',
 'Date de clôture',
 'Data zamknięcia'),

('article.closable_form.visibility',
 'Visibility',
 'Visibilité',
 'Widoczność'),

('article.closable_form.cancel',
 'Cancel',
 'Annuler',
 'Anuluj'),

('article.survey.add.title_edit',
 'Edit the survey of article:',
 'Modifier le sondage de l''article :',
 'Edytuj ankietę artykułu:'),

('article.survey.add.title_new',
 'Add a survey to article:',
 'Ajouter un sondage à l''article :',
 'Dodaj ankietę do artykułu:'),

('article.survey.add.edit_warning',
 'Only the question and the answers that have no votes can be modified',
 'On ne peut modifier que la question et les réponses pour lesquelles il n''y a aucun vote',
 'Można zmienić tylko pytanie i odpowiedzi, na które nie oddano żadnych głosów'),

('article.survey.add.question',
 'Survey question',
 'Question du sondage',
 'Pytanie ankiety'),

('article.survey.add.options',
 'Options (possible answers)',
 'Options (réponses possibles)',
 'Opcje (możliwe odpowiedzi)'),

('article.survey.add.add_option',
 'Add an option',
 'Ajouter une option',
 'Dodaj opcję'),

('article.survey.add.submit_edit',
 'Update the survey',
 'Modifier le sondage',
 'Zmień ankietę'),

('article.survey.add.submit_new',
 'Create the survey',
 'Créer le sondage',
 'Utwórz ankietę'),

('article.survey.visibility.redactor',
 'Redactor 👁️‍🗨️📝',
 'Rédacteur 👁️‍🗨️📝',
 'Redaktor 👁️‍🗨️📝'),

('article.survey.visibility.all',
 'Everyone 👁️‍🗨️👥',
 'Tout le monde 👁️‍🗨️👥',
 'Wszyscy 👁️‍🗨️👥'),

('article.survey.visibility.allAfterClosing',
 'Everyone after closing 👁️‍🗨️👥📅',
 'Tout le monde après la clôture 👁️‍🗨️👥📅',
 'Wszyscy po zamknięciu 👁️‍🗨️👥📅'),

('article.survey.visibility.voters',
 'Voters 👁️‍🗨️🗳️',
 'Les votants 👁️‍🗨️🗳️',
 'Głosujący 👁️‍🗨️🗳️'),

('article.survey.visibility.votersAfterClosing',
 'Voters after closing 👁️‍🗨️🗳️📅',
 'Les votants après la clôture 👁️‍🗨️🗳️📅',
 'Głosujący po zamknięciu 👁️‍🗨️🗳️📅'),

('article.order.add.title_edit',
 'Edit the group order of article:',
 'Modifier la commande groupée de l''article :',
 'Edytuj zamówienie grupowe artykułu:'),

('article.order.add.title_new',
 'Add a group order to article:',
 'Ajouter une commande groupée à l''article :',
 'Dodaj zamówienie grupowe do artykułu:'),

('article.order.add.edit_warning',
 'Only the description and the items that have no orders can be modified',
 'On ne peut modifier que la description et les articles pour lesquels il n''y a aucune commande',
 'Można zmienić tylko opis i pozycje, na które nie złożono żadnych zamówień'),

('article.order.add.question',
 'Order description',
 'Description de la commande',
 'Opis zamówienia'),

('article.order.add.options',
 'Available items',
 'Articles disponibles',
 'Dostępne pozycje'),

('article.order.add.add_option',
 'Add an item',
 'Ajouter un article',
 'Dodaj pozycję'),

('article.order.add.submit_edit',
 'Update the group order',
 'Modifier la commande groupée',
 'Zmień zamówienie grupowe'),

('article.order.add.submit_new',
 'Create the group order',
 'Créer la commande groupée',
 'Utwórz zamówienie grupowe'),

('article.order.visibility.redactor',
 'Redactor 👁️‍🗨️📝',
 'Rédacteur 👁️‍🗨️📝',
 'Redaktor 👁️‍🗨️📝'),

('article.order.visibility.all',
 'Everyone 👁️‍🗨️👥',
 'Tout le monde 👁️‍🗨️👥',
 'Wszyscy 👁️‍🗨️👥'),

('article.order.visibility.allAfterClosing',
 'Everyone after closing 👁️‍🗨️👥📅',
 'Tout le monde après la clôture 👁️‍🗨️👥📅',
 'Wszyscy po zamknięciu 👁️‍🗨️👥📅'),

('article.order.visibility.orderers',
 'People who answered 👁️‍🗨️🛒',
 'Les personnes ayant répondu 👁️‍🗨️🛒',
 'Osoby, które odpowiedziały 👁️‍🗨️🛒'),

('article.order.visibility.orderersAfterClosing',
 'Orderers after closing 👁️‍🗨️🛒📅',
 'Les commandants après la clôture 👁️‍🗨️🛒📅',
 'Zamawiający po zamknięciu 👁️‍🗨️🛒📅');

 -- Event module controllers: texts previously hard-coded in EventController, EventGuestController and EventTypeController
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.register.error.unknown_event',
 'Unknown event',
 'Événement inconnu',
 'Nieznane wydarzenie'),

('event.register.error.unknown_token',
 'Unknown token',
 'Token inconnu',
 'Nieznany token'),

('event.register.error.expired_token',
 'Token expired',
 'Token expiré',
 'Token wygasł'),

('event.register.error.member_account',
 'This address is already linked to a member account: please register while signed in',
 'Cette adresse est déjà associée à un compte membre : merci de vous inscrire connecté(e)',
 'Ten adres jest już powiązany z kontem członka: zarejestruj się po zalogowaniu'),

('event.register.error.already_registered',
 'Participant already registered',
 'Participant déjà enregistré',
 'Uczestnik jest już zarejestrowany'),

('event.guest.error.invalid_email',
 'Invalid e-mail address',
 'Adresse e-mail invalide',
 'Nieprawidłowy adres e-mail'),

('event.guest.error.select_event',
 'Please select an event',
 'Veuillez sélectionner un événement',
 'Wybierz wydarzenie'),

('event.guest.error.event_not_found',
 'Event not found or not accessible',
 'Événement non trouvé ou non accessible',
 'Nie znaleziono wydarzenia lub brak dostępu'),

('event.guest.error.already_invited',
 'This person is already invited to this event',
 'Cette personne est déjà invitée à cet événement',
 'Ta osoba została już zaproszona na to wydarzenie'),

('event.guest.error.send_failed',
 'Error while sending the invitation to {email}',
 'Erreur lors de l''envoi de l''invitation à {email}',
 'Błąd podczas wysyłania zaproszenia do {email}'),

('event.guest.error.send_exception',
 'Error while sending the invitation. {message}',
 'Erreur lors de l''envoi de l''invitation. {message}',
 'Błąd podczas wysyłania zaproszenia. {message}'),

('event.guest.success.sent',
 'Invitation successfully sent to {email}',
 'Invitation envoyée avec succès à {email}',
 'Zaproszenie zostało wysłane do {email}'),

('event.guest.email.subject',
 'Invitation to the event: {summary}',
 'Invitation à l''événement : {summary}',
 'Zaproszenie na wydarzenie: {summary}'),

('event.guest.email.greeting',
 'Hello,',
 'Bonjour,',
 'Dzień dobry,'),

('event.guest.email.greeting_named',
 'Hello {nickname},',
 'Bonjour {nickname},',
 'Dzień dobry {nickname},'),

('event.guest.email.invited',
 'You are invited to take part in the following event:',
 'Vous êtes invité(e) à participer à l''événement suivant :',
 'Zapraszamy do udziału w następującym wydarzeniu:'),

('event.guest.email.title',
 'Title:',
 'Titre :',
 'Tytuł:'),

('event.guest.email.description',
 'Description:',
 'Description :',
 'Opis:'),

('event.guest.email.location',
 'Location:',
 'Lieu :',
 'Miejsce:'),

('event.guest.email.date',
 'Date: {date} at {time}',
 'Date : {date} à {time}',
 'Data: {date}, godz. {time}'),

('event.guest.email.confirm',
 'To confirm your participation, click on the following link:',
 'Pour confirmer votre participation, cliquez sur le lien suivant :',
 'Aby potwierdzić swój udział, kliknij poniższy link:'),

('event.guest.email.regards',
 'Kind regards,',
 'Cordialement,',
 'Z poważaniem,'),

('event.guest.email.team',
 'The event managers team',
 'L''équipe des animateurs',
 'Zespół organizatorów'),

('event.types.column.name',
 'Name',
 'Nom',
 'Nazwa'),

('event.types.column.group',
 'Group',
 'Groupe',
 'Grupa'),

('event.types.column.attributes',
 'Attributes',
 'Attributs',
 'Atrybuty');

 -- Event needs management page: texts previously hard-coded in the view
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.needs.title',
 'Event needs',
 'Les besoins des événements',
 'Potrzeby wydarzeń'),

('event.needs.types.title',
 'Need types',
 'Types de besoins',
 'Typy potrzeb'),

('event.needs.list.title',
 'Needs',
 'Besoins',
 'Potrzeby'),

('event.needs.add',
 'Add',
 'Ajouter',
 'Dodaj'),

('event.needs.column.name',
 'Name',
 'Nom',
 'Nazwa'),

('event.needs.column.label',
 'Label',
 'Label',
 'Etykieta'),

('event.needs.column.type',
 'Type',
 'Type',
 'Typ'),

('event.needs.column.participant_dependent',
 'Depends on participants',
 'En fonction des participants',
 'Zależy od uczestników'),

('event.needs.yes',
 'Yes',
 'Oui',
 'Tak'),

('event.needs.no',
 'No',
 'Non',
 'Nie');

 -- Generic UI texts
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('common.cancel',
 'Cancel',
 'Annuler',
 'Anuluj'),

('common.save',
 'Save',
 'Enregistrer',
 'Zapisz'),

('common.delete',
 'Delete',
 'Supprimer',
 'Usuń'),

('common.close',
 'Close',
 'Fermer',
 'Zamknij'),

('common.delete_confirm.title',
 'Confirm deletion',
 'Confirmer la suppression',
 'Potwierdź usunięcie');

-- Event needs page: modals and JavaScript messages previously hard-coded
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.needs.modal.type.add',
 'Add a need type',
 'Ajouter un type de besoin',
 'Dodaj typ potrzeby'),

('event.needs.modal.type.edit',
 'Edit a need type',
 'Modifier un type de besoin',
 'Edytuj typ potrzeby'),

('event.needs.modal.need.add',
 'Add a need',
 'Ajouter un besoin',
 'Dodaj potrzebę'),

('event.needs.modal.need.edit',
 'Edit a need',
 'Modifier un besoin',
 'Edytuj potrzebę'),

('event.needs.form.select_type',
 'Select a type',
 'Sélectionnez un type',
 'Wybierz typ'),

('event.needs.form.participant_dependent',
 'Depends on the number of participants',
 'En fonction du nombre de participants',
 'Zależy od liczby uczestników'),

('event.needs.delete.default_message',
 'Are you sure you want to delete this item?',
 'Êtes-vous sûr de vouloir supprimer cet élément ?',
 'Czy na pewno chcesz usunąć ten element?'),

('event.needs.delete.type_confirm',
 'Are you sure you want to delete the need type "{name}"?',
 'Êtes-vous sûr de vouloir supprimer le type de besoin "{name}" ?',
 'Czy na pewno chcesz usunąć typ potrzeby "{name}"?'),

('event.needs.delete.need_confirm',
 'Are you sure you want to delete the need "{name}"?',
 'Êtes-vous sûr de vouloir supprimer le besoin "{name}" ?',
 'Czy na pewno chcesz usunąć potrzebę "{name}"?'),

('event.needs.js.error_prefix',
 'Error:',
 'Erreur :',
 'Błąd:'),

('event.needs.js.generic_error',
 'An error occurred',
 'Une erreur est survenue',
 'Wystąpił błąd'),

('event.needs.js.save_error',
 'An error occurred while saving',
 'Une erreur est survenue lors de l''enregistrement',
 'Wystąpił błąd podczas zapisywania'),

('event.needs.js.delete_error',
 'An error occurred while deleting',
 'Une erreur est survenue lors de la suppression',
 'Wystąpił błąd podczas usuwania');

-- Event types page: texts previously hard-coded in the view
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.types.delete_confirm',
 'Do you really want to delete it?',
 'Voulez-vous vraiment le supprimer ?',
 'Czy na pewno chcesz to usunąć?'),

('event.types.attributes.title',
 'Attributes management',
 'Gestion des attributs',
 'Zarządzanie atrybutami'),

('event.types.attributes.name_placeholder',
 'Attribute name',
 'Nom de l''attribut',
 'Nazwa atrybutu'),

('event.types.attributes.detail_placeholder',
 'Detail',
 'Détail',
 'Szczegóły');


 -- Event types page: JavaScript messages previously hard-coded in eventType/index.js
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.types.js.name_required',
 'The attribute name is required',
 'Le nom de l''attribut est requis',
 'Nazwa atrybutu jest wymagana'),

('event.types.js.error_prefix',
 'Error:',
 'Erreur :',
 'Błąd:'),

('event.types.js.delete_confirm',
 'Are you sure you want to delete this attribute?',
 'Êtes-vous sûr de vouloir supprimer cet attribut ?',
 'Czy na pewno chcesz usunąć ten atrybut?'),

('event.types.js.load_error',
 'Unable to load the attributes list:',
 'Impossible de charger la liste des attributs :',
 'Nie można załadować listy atrybutów:');

-- Karaoke game: texts previously hard-coded in the view and in index.js
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('karaoke.title',
 'Karaoke',
 'Karaoké',
 'Karaoke'),

('karaoke.untitled',
 'Untitled',
 'Sans titre',
 'Bez tytułu'),

('karaoke.unknown_artist',
 'Unknown artist',
 'Artiste inconnu',
 'Nieznany wykonawca'),

('karaoke.press_play',
 'Press Play to start...',
 'Appuyez sur Play pour commencer...',
 'Naciśnij Play, aby rozpocząć...'),

('karaoke.audio_unsupported',
 'Your browser does not support HTML5 audio.',
 'Votre navigateur ne supporte pas l''audio HTML5.',
 'Twoja przeglądarka nie obsługuje dźwięku HTML5.'),

('karaoke.play',
 'Play',
 'Lecture',
 'Odtwórz'),

('karaoke.restart',
 'Restart',
 'Recommencer',
 'Zacznij od nowa'),

('karaoke.synchronized',
 'Synchronized',
 'Synchronisé',
 'Zsynchronizowane'),

('karaoke.launch_for_all',
 'Start for everyone',
 'Lancer pour tous',
 'Uruchom dla wszystkich'),

('karaoke.clients_connected',
 '{count} client(s) connected',
 '{count} client(s) connecté(s)',
 'Połączeni klienci: {count}'),

('karaoke.js.start_error',
 'Startup error. Please reload the page.',
 'Erreur de démarrage. Rechargez la page.',
 'Błąd uruchamiania. Odśwież stronę.');

-- Karaoke game: texts previously hard-coded in KaraokeApp.js and UIController.js
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('karaoke.js.load_failed',
 'Loading failed',
 'Échec du chargement',
 'Ładowanie nie powiodło się'),

('karaoke.js.unknown_error',
 'Unknown error',
 'Erreur inconnue',
 'Nieznany błąd'),

('karaoke.js.error_prefix',
 'Error:',
 'Erreur :',
 'Błąd:'),

('karaoke.js.click_to_play',
 'Click "Play" to enable the sound',
 'Cliquez sur "Lecture" pour activer le son',
 'Kliknij „Odtwórz”, aby włączyć dźwięk'),

('karaoke.js.sync_activation_failed',
 'Synchronization could not be enabled',
 'Échec de l''activation de la synchronisation',
 'Nie udało się włączyć synchronizacji'),

('karaoke.js.countdown_failed',
 'Unable to start the countdown',
 'Impossible de lancer le compte à rebours',
 'Nie udało się rozpocząć odliczania'),

('karaoke.pause',
 'Pause',
 'Pause',
 'Pauza'),

('karaoke.aria.play',
 'Start playback',
 'Lancer la lecture',
 'Rozpocznij odtwarzanie'),

('karaoke.aria.pause',
 'Pause playback',
 'Mettre en pause',
 'Wstrzymaj odtwarzanie'),

('karaoke.clients_connected_one',
 '{count} client connected',
 '{count} client connecté',
 'Połączeni klienci: {count}'),

('karaoke.clients_connected_other',
 '{count} clients connected',
 '{count} clients connectés',
 'Połączeni klienci: {count}'),

('karaoke.waiting_for_host',
 'Waiting for the host to start...',
 'En attente du lancement par l''hôte...',
 'Oczekiwanie na uruchomienie przez gospodarza...'),

('karaoke.launch_for_all',
 'Start playback for everyone',
 'Lancer la lecture pour tous',
 'Uruchom odtwarzanie dla wszystkich'),

('karaoke.stop_for_all',
 'Stop playback for everyone',
 'Arrêter la lecture pour tous',
 'Zatrzymaj odtwarzanie dla wszystkich'),

('karaoke.aria.launch_for_all',
 'Start synchronized playback for everyone',
 'Lancer la lecture synchronisée pour tous',
 'Uruchom synchronizowane odtwarzanie dla wszystkich'),

('karaoke.aria.stop_for_all',
 'Stop synchronized playback',
 'Arrêter la lecture synchronisée',
 'Zatrzymaj synchronizowane odtwarzanie');

-- Obsolete key replaced by karaoke.clients_connected_one / _other
DELETE FROM Languages WHERE Name = 'karaoke.clients_connected';

-- ErrorManager: label previously hard-coded in English
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('error.maintenance.cancel',
 'Cancel maintenance',
 'Annuler la maintenance',
 'Anuluj konserwację');

 -- Leapfrog game: texts previously hard-coded in the view, the controller and leapfrog.js
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('leapfrog.title',
 'Sheep leapfrog',
 'Saute-mouton',
 'Przeskakiwanie owiec'),

('leapfrog.goal',
 'The fat sheep of the meadow must give up their place to the lean sheep of the sheepfold.',
 'Les moutons gras de la prairie doivent céder leur place aux moutons maigres de la bergerie.',
 'Tłuste owce z pastwiska muszą ustąpić miejsca chudym owcom z owczarni.'),

('leapfrog.click_hint',
 'Click on a sheep to make it move',
 'Cliquez sur un mouton pour le faire bouger',
 'Kliknij owcę, aby ją przesunąć'),

('leapfrog.rules_title',
 'Rules of the game:',
 'Règles du jeu :',
 'Zasady gry:'),

('leapfrog.rule1',
 'A sheep can move forward one square if the square in front of it is free',
 'Un mouton peut avancer d''une case si elle est libre devant lui',
 'Owca może przejść o jedno pole do przodu, jeśli jest ono wolne'),

('leapfrog.rule2',
 'A sheep can jump over exactly one other sheep if the square after it is free',
 'Un mouton peut sauter par-dessus 1 seul autre mouton s''il y a une case libre après lui',
 'Owca może przeskoczyć tylko jedną inną owcę, jeśli następne pole jest wolne'),

('leapfrog.rule3',
 'Sheep cannot move backwards',
 'Les moutons ne peuvent pas reculer',
 'Owce nie mogą się cofać'),

('leapfrog.rule4',
 'If a sheep is blocked, it shakes when you click on it',
 'Si un mouton est bloqué, il se secoue quand on clique dessus',
 'Jeśli owca jest zablokowana, otrząsa się po kliknięciu'),

('leapfrog.moves_one',
 '{count} move',
 '{count} mouvement',
 'Ruchy: {count}'),

('leapfrog.moves_other',
 '{count} moves',
 '{count} mouvements',
 'Ruchy: {count}'),

('leapfrog.lean_sheep',
 'Lean sheep',
 'Mouton maigre',
 'Chuda owca'),

('leapfrog.fat_sheep',
 'Fat sheep',
 'Mouton gras',
 'Tłusta owca'),

('leapfrog.won',
 'Won!',
 'Gagné !',
 'Wygrana!'),

('leapfrog.lost',
 'Lost!',
 'Perdu !',
 'Przegrana!'),

('leapfrog.replay',
 'Play again',
 'Rejouer',
 'Zagraj ponownie'),

('leapfrog.statistics_title',
 'Leapfrog statistics',
 'Statistiques du saute-mouton',
 'Statystyki gry w owce'),

('leapfrog.column.date',
 'Date',
 'Date',
 'Data'),

('leapfrog.column.who',
 'Who',
 'Qui',
 'Kto'),

('leapfrog.column.game',
 'Game',
 'Jeu',
 'Gra'),

('leapfrog.column.movements',
 'Moves',
 'Mouvements',
 'Ruchy'),

('leapfrog.column.result',
 'Result',
 'Résultat',
 'Wynik');

-- Solfège game: texts previously hard-coded in the view, the controller and the inline script
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('solfege.title',
 'Solfège learning',
 'Apprentissage du solfège',
 'Nauka solfeżu'),

('solfege.staff',
 'Sheet music',
 'Partition',
 'Nuty'),

('solfege.statistics',
 'Statistics',
 'Statistiques',
 'Statystyki'),

('solfege.current_note',
 'Current note:',
 'Note actuelle :',
 'Bieżąca nuta:'),

('solfege.attempts',
 'Attempts:',
 'Tentatives :',
 'Próby:'),

('solfege.time',
 'Time:',
 'Temps :',
 'Czas:'),

('solfege.total_score',
 'Total score:',
 'Score total :',
 'Łączny wynik:'),

('solfege.next_note',
 'Next note',
 'Note suivante',
 'Następna nuta'),

('solfege.restart',
 'Restart',
 'Recommencer',
 'Zacznij od nowa'),

('solfege.piano',
 'Virtual piano',
 'Piano virtuel',
 'Wirtualne pianino'),

('solfege.note.c', 'Do', 'Do', 'Do'),
('solfege.note.d', 'Re', 'Ré', 'Re'),
('solfege.note.e', 'Mi', 'Mi', 'Mi'),
('solfege.note.f', 'Fa', 'Fa', 'Fa'),
('solfege.note.g', 'Sol', 'Sol', 'Sol'),
('solfege.note.a', 'La', 'La', 'La'),
('solfege.note.b', 'Ti', 'Si', 'Si'),

('solfege.js.correct',
 'Correct! Score: {score}',
 'Correct ! Score : {score}',
 'Dobrze! Wynik: {score}'),

('solfege.js.try_again',
 'Try again!',
 'Essayez encore !',
 'Spróbuj jeszcze raz!'),

('solfege.js.reset',
 'Game reset!',
 'Jeu réinitialisé !',
 'Gra zresetowana!'),

('solfege.js.scores_saved',
 'Scores saved!',
 'Scores sauvegardés !',
 'Wyniki zapisane!');

 INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('event.supply.updated',
 'Supply successfully updated',
 'Apport mis à jour avec succès',
 'Wkład został zaktualizowany'),

('common.error_prefix',
 'Error:',
 'Erreur :',
 'Błąd:');

SQL);

        return 101;
    }
}
