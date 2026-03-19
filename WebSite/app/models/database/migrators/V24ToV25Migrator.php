<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\helpers\TranslationManager;
use app\interfaces\DatabaseMigratorInterface;

class V24ToV25Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("
            DELETE FROM Languages
            WHERE rowid NOT IN (
                SELECT MIN(rowid) FROM Languages GROUP BY Name
            )
        ");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_languages_name ON Languages(Name)");

        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('communication.filters.desactivated_accounts',
 'Deactivated accounts',
 'Comptes désactivés',
 'Konta dezaktywowane'),

('designer.home_settings.title',
 'Home page customization',
 'Personnalisation de la page d''accueil',
 'Personalizacja strony głównej'),

('designer.home_settings.force_language',
 'Force this language',
 'Forcer cette langue',
 'Wymuś ten język'),

('designer.home_settings.preview_hint',
 'Preview — click to edit',
 'Aperçu — cliquez pour éditer',
 'Podgląd — kliknij, aby edytować'),

('designer.home_settings.section_header',
 'Header',
 'En-tête',
 'Nagłówek'),

('designer.home_settings.section_article',
 'Main article',
 'Article principal',
 'Główny artykuł'),

('designer.home_settings.section_latest',
 'Latest articles',
 'Derniers articles',
 'Ostatnie artykuły'),

('designer.home_settings.section_footer',
 'Footer',
 'Pied de page',
 'Stopka'),

('designer.home_settings.preview_empty',
 'Empty',
 'Vide',
 'Pusty'),

('designer.home_settings.preview_article_auto',
 '1st paragraph of the latest article / featured article',
 '1er paragraphe du dernier article / article mis en avant',
 '1. akapit ostatniego artykułu / wyróżnionego artykułu'),

('designer.home_settings.preview_hidden',
 'Section hidden',
 'Section masquée',
 'Sekcja ukryta'),

('designer.home_settings.preview_latest_more',
 'more…',
 'autres…',
 'więcej…'),

('designer.home_settings.editor_placeholder_title',
 'Click on a section',
 'Cliquez sur une section',
 'Kliknij sekcję'),

('designer.home_settings.editor_placeholder_subtitle',
 'to display its editor',
 'pour afficher son éditeur',
 'aby wyświetlić jej edytor'),

('designer.home_settings.editor_select_hint',
 'Select a section in the preview',
 'Sélectionnez une section dans l''aperçu',
 'Wybierz sekcję w podglądzie'),

('designer.home_settings.header_description',
 'HTML content displayed at the top of the home page. Active language:',
 'Contenu HTML affiché en haut de la page d''accueil. Langue active :',
 'Zawartość HTML wyświetlana na górze strony głównej. Aktywny język:'),

('designer.home_settings.header_table_hint',
 '(Languages table)',
 '(table Languages)',
 '(tabela Languages)'),

('designer.home_settings.footer_description',
 'HTML content displayed at the bottom of the home page. Active language:',
 'Contenu HTML affiché en bas de la page d''accueil. Langue active :',
 'Zawartość HTML wyświetlana na dole strony głównej. Aktywny język:'),

('designer.home_settings.article_label',
 'ID of the article to feature',
 'ID de l''article à mettre en avant',
 'ID artykułu do wyróżnienia'),

('designer.home_settings.article_description',
 'Enter the identifier of a specific article. Enter 0 to automatically display the first paragraph of the latest published article or the currently featured article.',
 'Entrez l''identifiant d''un article spécifique. Saisissez 0 pour afficher automatiquement le premier paragraphe du dernier article publié ou de l''article actuellement mis en avant.',
 'Wprowadź identyfikator konkretnego artykułu. Wpisz 0, aby automatycznie wyświetlić pierwszy akapit ostatniego opublikowanego artykułu lub aktualnie wyróżnionego artykułu.'),

('designer.home_settings.article_zero_hint',
 '0 = latest article or featured article',
 '0 = dernier article ou article mis en avant',
 '0 = ostatni artykuł lub wyróżniony artykuł'),

('designer.home_settings.latest_label',
 'Number of latest articles to display',
 'Nombre de derniers articles à afficher',
 'Liczba ostatnich artykułów do wyświetlenia'),

('designer.home_settings.latest_description',
 'Indicate how many recent articles to list. Enter 0 to completely hide this section. Value between 0 and 50.',
 'Indiquez combien d''articles récents lister. Saisissez 0 pour masquer complètement cette section. Valeur entre 0 et 50.',
 'Podaj, ile ostatnich artykułów wyświetlić. Wpisz 0, aby całkowicie ukryć tę sekcję. Wartość od 0 do 50.'),

('designer.home_settings.title_edit_header',
 'Edit the header',
 'Éditer l''en-tête',
 'Edytuj nagłówek'),

('designer.home_settings.title_edit_article',
 'Configure the main article',
 'Configurer l''article principal',
 'Skonfiguruj główny artykuł'),

('designer.home_settings.title_edit_latest',
 'Configure the latest articles list',
 'Configurer la liste des derniers articles',
 'Skonfiguruj listę ostatnich artykułów'),

('designer.home_settings.title_edit_footer',
 'Edit the footer',
 'Éditer le pied de page',
 'Edytuj stopkę'),

('home.index.title',
 'What do you want to do?',
 'Que souhaitez-vous faire ?',
 'Co chcesz zrobić?'),

('home.index.subtitle',
 'Select an action below to get started.',
 'Sélectionnez une action ci-dessous pour commencer.',
 'Wybierz działanie poniżej, aby rozpocząć.'),

('home.index.public_space',
 '🌎 Public space',
 '🌎 L''espace public',
 '🌎 Przestrzeń publiczna'),

('home.index.tab_member',
 '👤 My member space',
 '👤 Mon espace membre',
 '👤 Moja przestrzeń członka'),

('home.index.tab_roles',
 '🔑 My permissions',
 '🔑 Mes autorisations',
 '🔑 Moje uprawnienia'),

('home.index.member_actions',
 '👥 Actions available to all members',
 '👥 Actions disponibles pour tous les membres',
 '👥 Działania dostępne dla wszystkich członków'),

('home.action.contact.title',
 'Contact the Club',
 'Contacter le Club',
 'Skontaktuj się z Klubem'),

('home.action.contact.desc',
 'Send a message to the administrators',
 'Envoyer un message aux responsables',
 'Wyślij wiadomość do administratorów'),

('home.action.signin.title',
 'Sign in',
 'Me connecter',
 'Zaloguj się'),

('home.action.signin.desc',
 'Access my member space',
 'Accéder à mon espace membre',
 'Uzyskaj dostęp do mojej przestrzeni członka'),

('home.action.search_article.title',
 'Search for an article',
 'Rechercher un article',
 'Wyszukaj artykuł'),

('home.action.search_article.desc',
 'Find club articles',
 'Trouver des articles du club',
 'Znajdź artykuły klubu'),

('home.action.calendar.title',
 'View calendar',
 'Voir le calendrier',
 'Zobacz kalendarz'),

('home.action.calendar.desc_public',
 'View upcoming events',
 'Visualiser les événements à venir',
 'Wyświetl nadchodzące wydarzenia'),

('home.action.calendar.desc_member',
 'Browse events',
 'Consulter les événements',
 'Przeglądaj wydarzenia'),

('home.action.profile.title',
 'Profile',
 'Profil',
 'Profil'),

('home.action.profile.desc',
 'Update my information',
 'Mettre à jour mes informations',
 'Zaktualizuj moje informacje'),

('home.action.statistics.title',
 'Statistics',
 'Statistiques',
 'Statystyki'),

('home.action.statistics.desc',
 'History and activity',
 'Historique et activité',
 'Historia i aktywność'),

('home.action.connections.title',
 'Connections',
 'Connexions',
 'Połączenia'),

('home.action.connections.desc',
 'My events in common with other members',
 'Mes événements en commun avec les autres membres',
 'Moje wspólne wydarzenia z innymi członkami'),

('home.action.next_events.title',
 'Upcoming events',
 'Evénements à venir',
 'Nadchodzące wydarzenia'),

('home.action.next_events.desc',
 'Browse and register for upcoming sessions',
 'Consulter et m''inscrire aux prochaines séances',
 'Przeglądaj i zapisuj się na nadchodzące sesje'),

('home.action.directory.title',
 'Member directory',
 'Trombinoscope',
 'Katalog członków'),

('home.action.directory.desc',
 'View club members and create/edit my presentation',
 'Voir les membres du club et créer/modifier ma présentation',
 'Przeglądaj członków klubu i utwórz/edytuj swoją prezentację'),

('home.action.news.title',
 'News',
 'News',
 'Aktualności'),

('home.action.news.desc',
 'View club news (articles, events, surveys, presentations...) from the last 7 days',
 'Voir les nouvelles du club (articles, événements, sondages, présentation ...) des 7 derniers jours',
 'Zobacz nowości klubu (artykuły, wydarzenia, ankiety, prezentacje...) z ostatnich 7 dni'),

('home.action.messages.title',
 'Messages',
 'Messages',
 'Wiadomości'),

('home.action.messages.desc',
 'View messages from the last 7 days',
 'Voir les messages des 7 derniers jours',
 'Zobacz wiadomości z ostatnich 7 dni'),

('home.role.event_manager',
 '🗓️ Event manager',
 '🗓️ Animateur',
 '🗓️ Animator'),

('home.action.manage_sessions.title',
 'Manage sessions',
 'Gérer les séances',
 'Zarządzaj sesjami'),

('home.action.manage_sessions.desc',
 'Create, edit, cancel a session and track registrations',
 'Créer, modifier, annuler une séance et suivre les inscriptions',
 'Twórz, edytuj, anuluj sesję i śledź rejestracje'),

('home.action.send_invitation.title',
 'Send an invitation',
 'Envoyer une invitation',
 'Wyślij zaproszenie'),

('home.action.send_invitation.desc',
 'Send an invitation to a non-member for a session',
 'Envoyer une invitation à une personne non membre du club pour une séance',
 'Wyślij zaproszenie do osoby niebędącej członkiem klubu na sesję'),

('home.action.stats_animators.title',
 'Animator statistics',
 'Statistiques animateurs',
 'Statystyki animatorów'),

('home.action.stats_animators.desc',
 'View animator statistics (number of sessions led by type and participants)',
 'Voir les statistiques des animateurs du club (nombre de séances animées par types et participants)',
 'Zobacz statystyki animatorów klubu (liczba sesji prowadzonych według typów i uczestników)'),

('home.role.designer',
 '🎨 Designer',
 '🎨 Designer',
 '🎨 Projektant'),

('home.action.event_types.title',
 'Event types and their attributes',
 'Types d''événements et leurs attributs',
 'Typy wydarzeń i ich atrybuty'),

('home.action.event_types.desc',
 'Define club event types and associated attributes',
 'Définir les types d''événements du club et les attributs associés',
 'Zdefiniuj typy wydarzeń klubu i powiązane atrybuty'),

('home.action.session_needs.title',
 'Session needs',
 'Les besoins d''une séance',
 'Potrzeby sesji'),

('home.action.session_needs.desc',
 'Define the needs associated with each club event type',
 'Définir les besoins associés à chaque type d''événement du club',
 'Zdefiniuj potrzeby powiązane z każdym typem wydarzenia klubu'),

('home.action.site_settings.title',
 'Site settings',
 'Paramètres du site',
 'Ustawienia witryny'),

('home.action.site_settings.desc',
 'Define general website settings and available language(s)',
 'Définir les paramètres généraux du site web du club et la(les) langues disponibles',
 'Zdefiniuj ogólne ustawienia witryny klubu i dostępne języki'),

('home.action.kanban.title',
 'Kanban',
 'Kanban',
 'Kanban'),

('home.action.kanban.desc',
 'Manage projects via Kanban boards',
 'Gérer des projets via des tableaux Kanban',
 'Zarządzaj projektami za pomocą tablic Kanban'),

('home.action.navigation.title',
 'Navigation',
 'Navigation',
 'Nawigacja'),

('home.action.navigation.desc',
 'Manage the club website navigation bars',
 'Gérer les barres de navigation du site web du club',
 'Zarządzaj paskami nawigacyjnymi witryny klubu'),

('home.role.redactor',
 '✍️ Redactor',
 '✍️ Rédacteur',
 '✍️ Redaktor'),

('home.action.article.title',
 'Article',
 'Article',
 'Artykuł'),

('home.action.article.desc',
 'Write and publish a new article',
 'Rédiger et publier un nouvel article',
 'Napisz i opublikuj nowy artykuł'),

('home.action.medias.title',
 'Media',
 'Medias',
 'Media'),

('home.action.medias.desc',
 'Manage club media (photos, documents...)',
 'Gérer les médias du club (photos, documents ...)',
 'Zarządzaj mediami klubu (zdjęcia, dokumenty...)'),

('home.action.top_articles.title',
 'Popular articles',
 'Articles populaires',
 'Popularne artykuły'),

('home.action.top_articles.desc',
 'View the most popular club articles by period',
 'Voir les articles les plus populaires du club par période',
 'Zobacz najpopularniejsze artykuły klubu według okresu'),

('home.action.stats_redactors.title',
 'Redactor statistics',
 'Statistiques rédacteurs',
 'Statystyki redaktorów'),

('home.action.stats_redactors.desc',
 'View redactor statistics (number of articles published per redactor and period)',
 'Voir les statistiques des rédacteurs du club (nombre d''articles publiés par rédacteur et par période)',
 'Zobacz statystyki redaktorów klubu (liczba artykułów opublikowanych na redaktora i okres)'),

('home.role.secretary',
 '📇 Secretary',
 '📇 Secrétaire',
 '📇 Sekretarz'),

('home.action.manage_members.title',
 'Manage members',
 'Gérer les membres',
 'Zarządzaj członkami'),

('home.action.manage_members.desc',
 'View and administer club members',
 'Consulter et administrer les adhérents',
 'Przeglądaj i administruj członkami klubu'),

('home.action.manage_groups.title',
 'Manage groups',
 'Gérer les groupes',
 'Zarządzaj grupami'),

('home.action.manage_groups.desc',
 'View and administer member groups (without permissions)',
 'Consulter et administrer les groupes (sans autorisation) de membres',
 'Przeglądaj i administruj grupami członków (bez uprawnień)'),

('home.action.manage_registrations.title',
 'Manage registrations',
 'Gérer les inscriptions',
 'Zarządzaj rejestracjami'),

('home.action.manage_registrations.desc',
 'View and administer member registrations to groups without permissions',
 'Consulter et administrer les inscriptions des adhérents aux groupes sans autorisation',
 'Przeglądaj i administruj rejestracjami członków do grup bez uprawnień'),

('home.action.import_members.title',
 'Import members',
 'Importer des membres',
 'Importuj członków'),

('home.action.import_members.desc',
 'Import members from a CSV file',
 'Importer des adhérents à partir d''un fichier CSV',
 'Importuj członków z pliku CSV'),

('home.role.observer',
 '🔍 Observer',
 '🔍 Observateur',
 '🔍 Obserwator'),

('home.action.referrers.title',
 'Referrer sites',
 'Site référents',
 'Witryny odsyłające'),

('home.action.referrers.desc',
 'See where club website visitors come from',
 'Voir d''où viennent les visiteurs du site web du club',
 'Zobacz skąd przychodzą odwiedzający witrynę klubu'),

('home.action.top_pages.title',
 'Popular pages',
 'Pages populaires',
 'Popularne strony'),

('home.action.top_pages.desc',
 'View the most popular club pages by period',
 'Voir les pages les plus populaires du club par période',
 'Zobacz najpopularniejsze strony klubu według okresu'),

('home.action.stats_visitors.title',
 'Visitor statistics (members)',
 'Statistiques visiteurs (membres)',
 'Statystyki odwiedzających (członkowie)'),

('home.action.stats_visitors.desc',
 'View visitor statistics (number of pages viewed per visitor and period)',
 'Voir les statistiques des visiteurs du site web du club (nombre de pages vues par visiteur et par période)',
 'Zobacz statystyki odwiedzających (liczba wyświetlonych stron na odwiedzającego i okres)'),

('home.action.visitor_logs.title',
 'Visitor logs',
 'Logs visiteurs',
 'Logi odwiedzających'),

('home.action.visitor_logs.desc',
 'View club website visitor logs',
 'Voir les logs des visiteurs du site web du club',
 'Zobacz logi odwiedzających witrynę klubu'),

('home.action.visitor_charts.title',
 'Visitor charts',
 'Graphiques visiteurs',
 'Wykresy odwiedzających'),

('home.action.visitor_charts.desc',
 'View club website visitor charts',
 'Voir les graphiques des visiteurs du site web du club',
 'Zobacz wykresy odwiedzających witrynę klubu'),

('home.action.visitor_analytics.title',
 'Visitor analytics',
 'Analyses des visiteurs',
 'Analizy odwiedzających'),

('home.action.visitor_analytics.desc',
 'View visitor charts (OS, browsers, hardware, screen resolution)',
 'Voir les graphiques des visiteurs du site web du club (OS, navigateurs, matériel, résolution d''écran)',
 'Zobacz wykresy odwiedzających (OS, przeglądarki, sprzęt, rozdzielczość ekranu)'),

('home.action.last_visits.title',
 'Last visits',
 'Dernières visites',
 'Ostatnie wizyty'),

('home.action.last_visits.desc',
 'View the last visits of club members',
 'Voir la dernière visites des membres du club',
 'Zobacz ostatnie wizyty członków klubu'),

('home.role.webmaster',
 '🛠️ Webmaster',
 '🛠️ Webmaster',
 '🛠️ Webmaster'),

('home.action.site_config.title',
 'Site configuration',
 'Configuration site',
 'Konfiguracja witryny'),

('home.action.site_config.desc',
 'Technical settings',
 'Paramètres techniques',
 'Ustawienia techniczne');
SQL;
        $pdo->exec($sql);

        $pdo->exec(<<<SQL
UPDATE Languages
SET
    Name = 'Help_Home',
    fr_FR = '
<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : MyClub</h1>
        <p class="lead">Simplifiez la gestion de votre vie associative en quelques clics.</p>
    </header>
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Présentation de l''application</h2>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <strong>Identification sécurisée</strong>
                                <p class="text-muted small">
                                    Identification par e-mail.
                                    <span class="d-block mt-1 text-dark">
                                        👉 <em>Première connexion ? Utilisez "Créer/modifier mon mot de passe" pour initialiser votre compte.</em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-newspaper"></i>
                            </div>
                            <div>
                                <strong>Visualisation des articles</strong>
                                <p class="text-muted small">Lisez et partagez les actualités rédigées par la communauté.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <strong>Gestion des activités</strong>
                                <p class="text-muted small">Inscrivez-vous aux activités et synchronisez votre agenda personnel.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <div>
                                <strong>Préférences &amp; Filtres</strong>
                                <p class="text-muted small">
                                    Paramétrez vos types d''événements favoris et vos disponibilités pour un affichage sur mesure.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <strong>Groupes &amp; Ressources</strong>
                                <p class="text-muted small">
                                    Rejoignez des groupes spécifiques pour accéder à leurs ressources dédiées.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <strong>Trombinoscope</strong>
                                <p class="text-muted small">
                                    Présentez-vous aux autres membres en complétant votre fiche.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr class="my-5">
    <section>
        <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
        <p class="text-muted">
            La navigation se fait principalement via la barre située en haut de l''écran.
        </p>

        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-secondary me-3">[Logo]</span>
                <span>
                    Situé en haut à gauche, il vous ramène instantanément sur la <strong>page d''accueil</strong>.
                </span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-list fs-3 me-3"></i>
                <span>
                    <strong>Menu Burger :</strong> Sur mobile, en haut à droite, il permet d''afficher les options de navigation masquées.
                </span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">👻</span>
                <span><strong>Mode Public :</strong> Vous n''êtes pas connecté. Accès limité aux ressources publiques uniquement.</span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">😊</span>
                <span><strong>Mode Membre :</strong> Vous êtes connecté. Accès complet aux ressources de vos groupes.</span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-box-arrow-right fs-3 text-warning me-3"></i>
                <span><strong>Déconnexion :</strong> Cliquez sur ce bouton pour fermer votre session sécurisée.</span>
            </div>

            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Aide :</strong> C''est ici que vous trouverez toutes les informations pour naviguer sur MyClub.
                </span>
            </div>
        </div>
    </section>
</div>
<div class="mt-4 text-center text-muted">
En pied de page 
  <strong><a href="https://myclub.ovh/menu/show/article/28">Tutoriels</a></strong> vous conduit vers le site de 
  <strong><i><u>MyClub</u></i></strong>. 
  Vous y trouverez des <strong>vidéos</strong>, des <strong>articles</strong>, 
  un <strong>dictionnaire</strong> et d’autres ressources pour vous accompagner.
</div>
',
    en_US = '
<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Contextual Help: MyClub</h1>
        <p class="lead">Simplify the management of your association in just a few clicks.</p>
    </header>
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Application overview</h2>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <strong>Secure authentication</strong>
                                <p class="text-muted small">
                                    Email-based login.
                                    <span class="d-block mt-1 text-dark">
                                        👉 <em>First time logging in? Use "Create / reset my password" to initialize your account.</em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-newspaper"></i>
                            </div>
                            <div>
                                <strong>Article browsing</strong>
                                <p class="text-muted small">
                                    Read and share news written by the community.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <strong>Activity management</strong>
                                <p class="text-muted small">
                                    Register for activities and sync them with your personal calendar.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <div>
                                <strong>Preferences &amp; filters</strong>
                                <p class="text-muted small">
                                    Configure your favorite event types and availability for a tailored view.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <strong>Groups &amp; resources</strong>
                                <p class="text-muted small">
                                    Join specific groups to access their dedicated resources.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <strong>Member directory</strong>
                                <p class="text-muted small">
                                    Introduce yourself to other members by completing your profile.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr class="my-5">
    <section>
        <h2 class="h4 mb-4">Key points to remember</h2>
        <p class="text-muted">
            Navigation mainly happens through the top navigation bar.
        </p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-secondary me-3">[Logo]</span>
                <span>
                    Located in the top-left corner, it instantly brings you back to the <strong>home page</strong>.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-list fs-3 me-3"></i>
                <span>
                    <strong>Burger menu:</strong> On mobile, top-right, it reveals hidden navigation options.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">👻</span>
                <span><strong>Public mode:</strong> You are not logged in. Access is limited to public resources only.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">😊</span>
                <span><strong>Member mode:</strong> You are logged in. Full access to your groups resources.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-box-arrow-right fs-3 text-warning me-3"></i>
                <span><strong>Logout:</strong> Click here to securely end your session.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Help:</strong> This is where you will find all the information you need to use MyClub.
                </span>
            </div>
        </div>
    </section>
</div>
<div class="mt-4 text-center text-muted">
In the footer, 
  <strong><a href="https://myclub.ovh/menu/show/article/28">Tutorials</a></strong> will take you to the 
  <strong><i><u>MyClub</u></i></strong> website. 
  There you will find <strong>videos</strong>, <strong>articles</strong>, 
  a <strong>dictionary</strong>, and other resources to support you.
</div>
',
    pl_PL = '
<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: MyClub</h1>
        <p class="lead">Uprość zarządzanie swoim stowarzyszeniem w kilku kliknięciach.</p>
    </header>
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Przegląd aplikacji</h2>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <strong>Bezpieczne logowanie</strong>
                                <p class="text-muted small">
                                    Logowanie za pomocą adresu e-mail.
                                    <span class="d-block mt-1 text-dark">
                                        👉 <em>Pierwsze logowanie? Użyj opcji "Utwórz / zmień hasło", aby aktywować swoje konto.</em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-newspaper"></i>
                            </div>
                            <div>
                                <strong>Przeglądanie artykułów</strong>
                                <p class="text-muted small">
                                    Czytaj i udostępniaj wiadomości tworzone przez społeczność.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <strong>Zarządzanie wydarzeniami</strong>
                                <p class="text-muted small">
                                    Zapisuj się na wydarzenia i synchronizuj je ze swoim kalendarzem.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <div>
                                <strong>Preferencje i filtry</strong>
                                <p class="text-muted small">
                                    Ustaw ulubione typy wydarzeń oraz dostępność, aby uzyskać spersonalizowany widok.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <strong>Grupy i zasoby</strong>
                                <p class="text-muted small">
                                    Dołącz do określonych grup, aby uzyskać dostęp do ich zasobów.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <strong>Katalog członków</strong>
                                <p class="text-muted small">
                                    Przedstaw się innym członkom, uzupełniając swój profil.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr class="my-5">
    <section>
        <h2 class="h4 mb-4">Najważniejsze informacje</h2>
        <p class="text-muted">
            Nawigacja odbywa się głównie za pomocą paska menu u góry ekranu.
        </p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-secondary me-3">[Logo]</span>
                <span>
                    Znajduje się w lewym górnym rogu i przenosi Cię bezpośrednio na <strong>stronę główną</strong>.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-list fs-3 me-3"></i>
                <span>
                    <strong>Menu Burger:</strong> Na urządzeniach mobilnych, w prawym górnym rogu, pokazuje ukryte opcje nawigacji.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">👻</span>
                <span><strong>Tryb publiczny:</strong> Nie jesteś zalogowany. Dostęp tylko do zasobów publicznych.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">😊</span>
                <span><strong>Tryb członka:</strong> Jesteś zalogowany. Pełny dostęp do zasobów Twoich grup.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-box-arrow-right fs-3 text-warning me-3"></i>
                <span><strong>Wylogowanie:</strong> Kliknij tutaj, aby bezpiecznie zakończyć sesję.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Pomoc:</strong> Tutaj znajdziesz wszystkie informacje potrzebne do korzystania z MyClub.
                </span>
            </div>
        </div>
    </section>
</div>
<div class="mt-4 text-center text-muted">
W stopce strony 
  <strong><a href="https://myclub.ovh/menu/show/article/28">Samouczki</a></strong> prowadzą do strony 
  <strong><i><u>MyClub</u></i></strong>. 
  Znajdziesz tam <strong>wideo</strong>, <strong>artykuły</strong>, 
  <strong>słownik</strong> oraz inne materiały pomocnicze.
</div>
'
WHERE Id = 56;
SQL);

        $lang = TranslationManager::getCurrentLanguage();
        $homeKeys = [
            'Home_header'  => 'Home_Header',
            'Home_footer'  => 'Home_Footer',
            'LegalNotices' => 'LegalNotices',
        ];
        $select = $pdo->prepare("SELECT Value FROM Settings WHERE Name = :name");
        $insert = $pdo->prepare("
            INSERT OR IGNORE INTO Languages (Name, en_US, fr_FR, pl_PL)
            VALUES (:name, '', '', '')
        ");
        $update = $pdo->prepare("UPDATE Languages SET \"$lang\" = :val WHERE Name = :name");
        foreach ($homeKeys as $settingsKey => $langKey) {
            $select->execute([':name' => $settingsKey]);
            $row = $select->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $insert->execute([':name' => $langKey]);
                $update->execute([':val' => $row['Value'], ':name' => $langKey]);
            }
        }
        $pdo->exec("DELETE FROM Settings WHERE Name IN ('Home_header', 'Home_footer', 'LegalNotices')");
        $pdo->exec("DELETE FROM Settings WHERE Name IN ('Help_Admin', 'Help_Designer', 'Help_EventManager', 'Help_Home', 'Help_PersonManager', 'Help_User', 'Help_VisitorInsights', 'Help_Webmaster')");
        $pdo->exec("DELETE FROM Settings WHERE Name IN ('Message_UnknownUser', 'Message_PasswordReset', 'Message_AutoSignInSucceeded', 'Message_SignInSucceeded', 'Message_SignOutSucceeded')");
        $pdo->exec("DELETE FROM Settings WHERE Name IN ('Error_403', 'Error_404', 'Error_500', 'Title')");
        $pdo->exec("
            INSERT INTO Settings (Name, Value) VALUES
            ('Home_LatestArticlesCount', '10'),
            ('Home_FeaturedArticleId', '0')
        ");

        return 25;
    }
}
