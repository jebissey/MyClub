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

SQL);

        return 101;
    }
}
