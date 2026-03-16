<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V23ToV24Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("INSERT INTO 'Authorization' VALUES (12,'CommunicationManager')");
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
  <strong><a href="https://myclub.alwaysdata.net/menu/show/article/28">Tutoriels</a></strong> vous conduit vers le site de 
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
  <strong><a href="https://myclub.alwaysdata.net/menu/show/article/28">Tutorials</a></strong> will take you to the 
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
  <strong><a href="https://myclub.alwaysdata.net/menu/show/article/28">Samouczki</a></strong> prowadzą do strony 
  <strong><i><u>MyClub</u></i></strong>. 
  Znajdziesz tam <strong>wideo</strong>, <strong>artykuły</strong>, 
  <strong>słownik</strong> oraz inne materiały pomocnicze.
</div>
'
WHERE Id = 56;
SQL);

        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('communication.index.title',
 'Communication Manager',
 'Gestionnaire de communication',
 'Menedżer komunikacji'),

('communication.index.today',
 'Today',
 'Aujourd''hui',
 'Dzisiaj'),

('communication.index.this_month',
 'This month',
 'Ce mois',
 'Ten miesiąc'),

('communication.index.dest',
 'dest.',
 'dest.',
 'odb.'),

('communication.index.send',
 'Send',
 'Envoyer',
 'Wyślij'),

('communication.index.cancel',
 'Cancel',
 'Annuler',
 'Anuluj'),

('communication.index.filter_members',
 'Filter members',
 'Filtrer les membres',
 'Filtruj członków'),

('communication.index.group',
 'Group',
 'Groupe',
 'Grupa'),

('communication.index.all_members',
 '— All members —',
 '— Tous les membres —',
 '— Wszyscy członkowie —'),

('communication.index.password',
 'Password',
 'Mot de passe',
 'Hasło'),

('communication.index.filter_all',
 'All',
 'Tous',
 'Wszyscy'),

('communication.index.filter_created',
 'Created',
 'Créé',
 'Utworzone'),

('communication.index.filter_not_created',
 'Not created',
 'Non créé',
 'Nie utworzone'),

('communication.index.presentation',
 'Presentation',
 'Présentation',
 'Prezentacja'),

('communication.index.filter_filled',
 'Filled',
 'Renseignée',
 'Uzupełniona'),

('communication.index.filter_not_filled',
 'Not filled',
 'Non renseignée',
 'Nieuzupełniona'),

('communication.index.in_public_map',
 'In the public map',
 'Dans la carte publique',
 'Na publicznej mapie'),

('communication.index.filter_yes',
 'Yes',
 'Oui',
 'Tak'),

('communication.index.filter_no',
 'No',
 'Non',
 'Nie'),

('communication.index.refresh',
 'Refresh',
 'Actualiser',
 'Odśwież'),

('communication.index.select_all',
 'Select all',
 'Tout sél.',
 'Zaznacz wszystko'),

('communication.index.subject',
 'Subject',
 'Objet',
 'Temat'),

('communication.index.subject_placeholder',
 'Message subject…',
 'Objet du message…',
 'Temat wiadomości…'),

('communication.index.content',
 'Content',
 'Contenu',
 'Treść'),

('communication.index.modal_confirm_title',
 'Confirm sending',
 'Confirmer l''envoi',
 'Potwierdź wysyłkę'),

('communication.index.modal_cancel',
 'Cancel',
 'Annuler',
 'Anuluj'),

('communication.index.modal_confirm',
 'Confirm',
 'Confirmer',
 'Potwierdź'),

('communication.index.sending',
 'Sending…',
 'Envoi en cours…',
 'Wysyłanie…'),

 ('navbar.admin.communication_manager',
 'Communication Manager',
 'Gestionnaire de communication',
 'Menedżer komunikacji');
SQL;
        $pdo->exec($sql);

        return 24;
    }
}
