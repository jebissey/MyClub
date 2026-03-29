<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V30ToV31Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
UPDATE Languages SET en_US =
'<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Administration Zone Tools</h2>
                <p class="lead text-muted mb-4">
                    This page provides access to administration tools based on your permissions.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Access &amp; Permissions</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Visibility:</strong> The yellow key only appears in the top bar for members with authorisations.
                                    </li>
                                    <li>
                                        <strong>Smart navigation:</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Multiple zones → selection menu</li>
                                            <li>→ Single zone → automatic redirect (saves time 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Mobile Optimisation</h3>
                                <p class="text-muted small">
                                    Shortcuts appear directly here as well — no need to open the ☰ menu on narrow screens.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">MyClub Permissions (quick reference)</h2>
                <p class="lead text-muted mb-4">
                    When <strong>MyClub</strong> is installed, a <strong>Webmaster</strong> account and group are created automatically.
                    This group is reserved for initial administration: it cannot be modified and no member can be added to it.
                </p>
                <p class="text-muted mb-4">
                    The system is based on <strong>authorisations</strong> assigned to <strong>groups</strong>.
                    Members inherit the rights of the groups they belong to.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-person-gear fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Initial Setup</h3>
                                <p class="text-muted small mb-2">The <strong>Webmaster</strong> must first:</p>
                                <ul class="list-unstyled text-muted small ms-1">
                                    <li class="mb-1">① Create <strong>groups</strong> (<em>PersonManager</em>)</li>
                                    <li class="mb-1">② Create <strong>members</strong> (<em>PersonManager</em>)</li>
                                    <li>③ Assign members to groups</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-palette fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Site Design</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>HomeDesigner</strong>: create/edit the home page</li>
                                    <li class="mb-1"><strong>MenuDesigner</strong>: create and organise menus</li>
                                    <li><strong>Translator</strong>: translate site texts and pages</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Articles</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>Redactor</strong>: write and edit articles</li>
                                    <li class="mb-1"><strong>Editor</strong>: publish an article visible to everyone</li>
                                    <li>A redactor can publish if the article is restricted to members or a group</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Events</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>EventDesigner</strong>: create event types</li>
                                    <li class="mb-1"><strong>EventManager</strong>: create events</li>
                                    <li>Only the <strong>creator</strong> can edit or cancel their event</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-envelope fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Communication</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>CommunicationManager</strong>: send emails to members</li>
                                    <li>Some messages can be sent automatically (articles, events, password, contact)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Kanban Projects</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>KanbanDesigner</strong>: create and edit your own projects</li>
                                    <li>Other members with this right can only view them</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-bar-chart-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Site Analytics</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>VisitorInsights</strong>: access visitor analytics tools</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <h2 class="h4 mb-3 fw-bold">Key takeaways</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>The <strong>yellow key</strong> is only visible to members with authorisations.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>Single administration zone → automatic redirect.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>On mobile: direct shortcuts on this page.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span><strong>Contextual help:</strong> available in each module via the help icon.</span>
            </div>
        </div>
    </section>
</div>'
WHERE Name = 'Help_Admin'
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET fr_FR =
'<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Les outils des zones d''administration</h2>
                <p class="lead text-muted mb-4">
                    Cette page permet d''accéder aux outils d''administration selon vos droits.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Accès et Permissions</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Visibilité :</strong> La clé jaune n''apparaît dans la barre supérieure que pour les membres ayant des autorisations.
                                    </li>
                                    <li>
                                        <strong>Navigation intelligente :</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Plusieurs zones → menu de sélection</li>
                                            <li>→ Une seule zone → redirection automatique (gain de temps 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Optimisation Mobile</h3>
                                <p class="text-muted small">
                                    Les raccourcis apparaissent aussi directement ici — plus besoin d''ouvrir le menu ☰ sur les écrans étroits.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Les droits dans MyClub (aide rapide)</h2>
                <p class="lead text-muted mb-4">
                    Lors de l''installation de <strong>MyClub</strong>, un compte et un groupe <strong>Webmaster</strong> sont créés automatiquement.
                    Ce groupe est réservé à l''administration initiale : il ne peut pas être modifié et aucun membre ne peut y être ajouté.
                </p>
                <p class="text-muted mb-4">
                    Le système repose sur des <strong>autorisations</strong> attribuées à des <strong>groupes</strong>.
                    Les membres héritent des droits des groupes auxquels ils appartiennent.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-person-gear fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Première configuration</h3>
                                <p class="text-muted small mb-2">Le <strong>Webmaster</strong> doit d''abord :</p>
                                <ul class="list-unstyled text-muted small ms-1">
                                    <li class="mb-1">① Créer les <strong>groupes</strong> (<em>PersonManager</em>)</li>
                                    <li class="mb-1">② Créer les <strong>membres</strong> (<em>PersonManager</em>)</li>
                                    <li>③ Inscrire les membres dans les groupes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-palette fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Conception du site</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>HomeDesigner</strong> : créer/modifier la page d''accueil</li>
                                    <li class="mb-1"><strong>MenuDesigner</strong> : créer et organiser les menus</li>
                                    <li><strong>Translator</strong> : traduire les textes et pages du site</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Articles</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>Redactor</strong> : écrire et modifier des articles</li>
                                    <li class="mb-1"><strong>Editor</strong> : publier un article visible par tous</li>
                                    <li>Le rédacteur peut publier si l''article est limité aux membres ou à un groupe</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Événements</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>EventDesigner</strong> : créer les types d''événements</li>
                                    <li class="mb-1"><strong>EventManager</strong> : créer des événements</li>
                                    <li>Seul le <strong>créateur</strong> peut modifier ou annuler son événement</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-envelope fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Communication</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>CommunicationManager</strong> : envoyer des courriels aux membres</li>
                                    <li>Certains messages peuvent être envoyés automatiquement (articles, événements, mot de passe, contact)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Projets Kanban</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>KanbanDesigner</strong> : créer et modifier ses propres projets</li>
                                    <li>Les autres membres ayant ce droit peuvent seulement les consulter</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-bar-chart-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Analyse du site</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>VisitorInsights</strong> : accès aux outils d''analyse des visiteurs</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <h2 class="h4 mb-3 fw-bold">Ce qu''il faut retenir</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>La <strong>clé jaune</strong> n''est visible que pour les membres ayant des autorisations.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>Une seule zone d''administration → redirection automatique.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>Sur mobile : raccourcis directs sur cette page.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span><strong>Aide contextuelle :</strong> disponible dans chaque module via l''icône d''aide.</span>
            </div>
        </div>
    </section>
</div>'
WHERE Name = 'Help_Admin'
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Narzędzia stref administracyjnych</h2>
                <p class="lead text-muted mb-4">
                    Ta strona umożliwia dostęp do narzędzi administracyjnych zgodnie z posiadanymi uprawnieniami.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Dostęp i uprawnienia</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Widoczność:</strong> Żółty klucz pojawia się na górnym pasku tylko dla członków posiadających uprawnienia.
                                    </li>
                                    <li>
                                        <strong>Inteligentna nawigacja:</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Kilka stref → menu wyboru</li>
                                            <li>→ Jedna strefa → automatyczne przekierowanie (oszczędność czasu 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Optymalizacja mobilna</h3>
                                <p class="text-muted small">
                                    Skróty pojawiają się też bezpośrednio tutaj — nie trzeba otwierać menu ☰ na wąskich ekranach.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Uprawnienia w MyClub (krótki przewodnik)</h2>
                <p class="lead text-muted mb-4">
                    Podczas instalacji <strong>MyClub</strong> automatycznie tworzony jest konto i grupa <strong>Webmaster</strong>.
                    Grupa ta jest zarezerwowana do wstępnej administracji: nie można jej modyfikować ani dodawać do niej członków.
                </p>
                <p class="text-muted mb-4">
                    System opiera się na <strong>uprawnieniach</strong> przypisanych do <strong>grup</strong>.
                    Członkowie dziedziczą prawa grup, do których należą.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-person-gear fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Pierwsza konfiguracja</h3>
                                <p class="text-muted small mb-2"><strong>Webmaster</strong> musi najpierw:</p>
                                <ul class="list-unstyled text-muted small ms-1">
                                    <li class="mb-1">① Utworzyć <strong>grupy</strong> (<em>PersonManager</em>)</li>
                                    <li class="mb-1">② Utworzyć <strong>członków</strong> (<em>PersonManager</em>)</li>
                                    <li>③ Przypisać członków do grup</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-palette fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Projekt strony</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>HomeDesigner</strong>: tworzenie/edycja strony głównej</li>
                                    <li class="mb-1"><strong>MenuDesigner</strong>: tworzenie i organizacja menu</li>
                                    <li><strong>Translator</strong>: tłumaczenie tekstów i stron serwisu</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Artykuły</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>Redactor</strong>: pisanie i edytowanie artykułów</li>
                                    <li class="mb-1"><strong>Editor</strong>: publikowanie artykułu widocznego dla wszystkich</li>
                                    <li>Redaktor może publikować, jeśli artykuł jest ograniczony do członków lub grupy</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Wydarzenia</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>EventDesigner</strong>: tworzenie typów wydarzeń</li>
                                    <li class="mb-1"><strong>EventManager</strong>: tworzenie wydarzeń</li>
                                    <li>Tylko <strong>twórca</strong> może modyfikować lub anulować swoje wydarzenie</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-envelope fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Komunikacja</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>CommunicationManager</strong>: wysyłanie e-maili do członków</li>
                                    <li>Niektóre wiadomości mogą być wysyłane automatycznie (artykuły, wydarzenia, hasło, kontakt)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Projekty Kanban</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>KanbanDesigner</strong>: tworzenie i edytowanie własnych projektów</li>
                                    <li>Inni członkowie z tym prawem mogą je tylko przeglądać</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-bar-chart-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Analityka strony</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>VisitorInsights</strong>: dostęp do narzędzi analizy odwiedzających</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <h2 class="h4 mb-3 fw-bold">Najważniejsze informacje</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span><strong>Żółty klucz</strong> jest widoczny tylko dla członków posiadających uprawnienia.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>Jedna strefa administracyjna → automatyczne przekierowanie.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>Na urządzeniach mobilnych: bezpośrednie skróty na tej stronie.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span><strong>Pomoc kontekstowa:</strong> dostępna w każdym module przez ikonę pomocy.</span>
            </div>
        </div>
    </section>
</div>'
WHERE Name = 'Help_Admin'
SQL);

        return 31;
    }
}