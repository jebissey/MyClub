<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V8ToV9Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("UPDATE Languages SET Name = 'Help_Designer'        WHERE Name = 'Help_designer';");
        $pdo->exec("UPDATE Languages SET Name = 'Help_EventManager'    WHERE Name = 'Help_eventManager';");

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
  <strong><a href="https://myclub.alwaysdata.net/navbar/show/article/28">Tutoriels</a></strong> vous conduit vers le site de 
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
  <strong><a href="https://myclub.alwaysdata.net/navbar/show/article/28">Tutorials</a></strong> will take you to the 
  <strong><i><u>MyClub</u></i></strong> website. 
  There you will find <strong>videos</strong>, <strong>articles</strong>, 
  a <strong>dictionary</strong>, and other resources to support you.
</div>
'
WHERE Id = 56;
SQL);

        $pdo->exec("UPDATE Languages SET Name = 'Help_PersonManager'   WHERE Name = 'Help_personManager';");
        $pdo->exec("UPDATE Languages SET Name = 'Help_Redactor'        WHERE Name = 'Help_redactor';");
        $pdo->exec("UPDATE Languages SET Name = 'Help_User'            WHERE Name = 'Help_user';");
        $pdo->exec("UPDATE Languages SET Name = 'Help_VisitorInsights' WHERE Name = 'Help_visitorInsights';");
        $pdo->exec("UPDATE Languages SET Name = 'Help_Webmaster'       WHERE Name = 'Help_webmaster';");
        $pdo->exec("UPDATE Languages SET Name = 'Home_Header'          WHERE Name = 'Home_header';");
        $pdo->exec("UPDATE Languages SET Name = 'Home_Footer'          WHERE Name = 'Home_footer';");


        $pdo->exec(<<<'SQL'
INSERT INTO Languages (Name, en_US, fr_FR)
VALUES (
    'User',
    '<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Welcome to your personal space</h5>
        <p>
            Here, you can view and update your information.
        </p>
        <p class="mb-0">
            👉 If the ☰ button is visible in the top right corner, click on it to access the menu.
        </p>
        <p class="mb-0">
            💡 You can also click directly on the links below to access the different options.
        </p>
    </div>
    <div class="user-links mt-3 mb-3">
        <div class="d-flex flex-wrap gap-3">
            <a href="/user/account" class="{if $page == ''account''}active{/if} text-decoration-none">👤 Account</a>
            <a href="/user/availabilities" class="{if $page == ''account''}active{/if} text-decoration-none">🕒 Availability</a>
            <a href="/user/groups" class="{if $page == ''account''}active{/if} text-decoration-none">🔐 Groups</a>
            <a href="/user/preferences" class="{if $page == ''account''}active{/if} text-decoration-none">⭐ Preferences</a>
            <a href="/user/notifications" class="{if $page == ''account''}active{/if} text-decoration-none">🔔 Notifications</a>
            <a href="/user/statistics" class="{if $page == ''account''}active{/if} text-decoration-none">📊 Statistics</a>
            <a href="/user/directory" class="{if $page == ''account''}active{/if} text-decoration-none">🎭 Member Directory</a>
            <a href="/user/news" class="{if $page == ''account''}active{/if} text-decoration-none">📰 News</a>
            <a href="/user/messages" class="{if $page == ''account''}active{/if} text-decoration-none">💬 Messages</a>
            <a href="/user/notepad" class="{if $page == ''account''}active{/if} text-decoration-none">🗒️ Notepad</a>
            <a href="/user/connections" class="{if $page == ''account''}active{/if} text-decoration-none">🕸️ Connections</a>
        </div>
    </div>',
    '<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Bienvenue dans votre espace personnel</h5>
        <p>
            Ici, vous pouvez consulter et mettre à jour vos informations.
        </p>
        <p class="mb-0">
            👉 Si le bouton ☰ est présent en haut à droite, il faut cliquer dessus pour accéder au menu.
        </p>
        <p class="mb-0">
            💡 Vous pouvez aussi cliquer directement sur les liens ci-dessous pour accéder aux différentes options.
        </p>
    </div>
    <div class="user-links mt-3 mb-3">
        <div class="d-flex flex-wrap gap-3">
            <a href="/user/account" class="{if $page == ''account''}active{/if} text-decoration-none">👤 Compte</a>
            <a href="/user/availabilities" class="{if $page == ''account''}active{/if} text-decoration-none">🕒 Disponibilités</a>
            <a href="/user/groups" class="{if $page == ''account''}active{/if} text-decoration-none">🔐 Groupes</a>
            <a href="/user/preferences" class="{if $page == ''account''}active{/if} text-decoration-none">⭐ Préférences</a>
            <a href="/user/notifications" class="{if $page == ''account''}active{/if} text-decoration-none">🔔 Notifications</a>
            <a href="/user/statistics" class="{if $page == ''account''}active{/if} text-decoration-none">📊 Statistiques</a>
            <a href="/user/directory" class="{if $page == ''account''}active{/if} text-decoration-none">🎭 Trombinoscope</a>
            <a href="/user/news" class="{if $page == ''account''}active{/if} text-decoration-none">📰 News</a>
            <a href="/user/messages" class="{if $page == ''account''}active{/if} text-decoration-none">💬 Messages</a>
            <a href="/user/notepad" class="{if $page == ''account''}active{/if} text-decoration-none">🗒️ Bloc-notes</a>
            <a href="/user/connections" class="{if $page == ''account''}active{/if} text-decoration-none">🕸️ Connexions</a>
        </div>
    </div>'
),
('notepad','Notepad','Bloc-notes');
SQL);

        return 9;
    }
}
