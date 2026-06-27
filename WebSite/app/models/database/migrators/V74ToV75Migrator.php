<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V74ToV75Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('Help_Redactor',
'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Editorial space</h1>
    <p class="lead">Manage your articles, media and track your content performance.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Tools overview</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📰</div>
                            <div>
                                <strong>Articles</strong>
                                <p class="text-muted small">
                                    Browse and manage your articles list. Create a new article via the 
                                    <strong>+</strong> button, or edit / view an existing one.
                                    <span class="d-block mt-1 text-dark">
                                        👉 
                                        <em>
                                            <strong>Editor</strong> 
                                            permission required to reassign an article to another writer or publish it for all visitors
                                            (the grey buttons).
                                        </em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">⚖️</div>
                            <div>
                                <strong>
                                    Public articles 
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">Editor</span>
                                </strong>
                                <p class="text-muted small">
                                    Editor-only view. Lists all articles visible to visitors (non-members), sorted by last update date.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📂</div>
                            <div>
                                <strong>Media manager</strong>
                                <p class="text-muted small">Import and organise your files. For each file, you can:</p>
                                <ul class="text-muted small mb-0 ps-3">
                                    <li><strong>Preview</strong> — view in browser</li>
                                    <li><strong>Copy URL</strong> — to insert into an article or carousel</li>
                                    <li><strong>Share</strong> — make accessible to other members</li>
                                    <li><strong>Delete</strong> — permanent deletion</li>
                                    <li><strong>Resize</strong> — available for images only</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📈</div>
                            <div>
                                <strong>Top 50 — most viewed articles</strong>
                                <p class="text-muted small">
                                    Rankings of articles by visit count over a selectable period. 
                                    Shows title, author, URL, response time, visit count and percentage of total traffic.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🧮</div>
                            <div>
                                <strong>Pivot table</strong>
                                <p class="text-muted small">
                                    Crosses 
                                    <strong>writers</strong> (columns) and 
                                    <strong>audiences</strong> (rows) to visualise how many articles each writer 
                                    has published for each audience. Select the desired period in the top right.
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
        <h2 class="h4 mb-4">Key takeaways</h2>
        <p class="text-muted">Navigation in the editorial space is done via the icons in the top bar.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span>
                    The <strong>navigation arrows</strong> (up / back) allow you to return to the editorial space or the previous page.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-shield-check fs-3 text-warning me-3"></i>
                <span>
                    <strong>«Editor» permission:</strong> 
                    certain features are reserved for publishing managers (public articles, reassignment, publishing, pivot table).
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>The <strong>+</strong> button in the articles list allows you to create a new article.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span><strong>Help:</strong> Here you will find all the information needed to use the editorial space.</span>
            </div>
        </div>
    </section>
</div>',

'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Espace rédaction</h1>
    <p class="lead">Gérez vos articles, médias et suivez les performances de votre contenu.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Présentation des outils</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📰</div>
                            <div>
                                <strong>Articles</strong>
                                <p class="text-muted small">
                                    Consultez et gérez la liste de vos articles. 
                                    Créez un nouvel article via le bouton <strong>+</strong>, ou modifiez / visualisez un article existant.
                                    <span class="d-block mt-1 text-dark">
                                        👉 
                                        <em>
                                            Autorisation 
                                            <strong>Éditeur</strong> requise pour réattribuer un article à un autre rédacteur 
                                            ou le publier pour tous les visiteurs (les boutons gris).
                                        </em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">⚖️</div>
                            <div>
                                <strong>
                                    Articles publics <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">Editor</span>
                                </strong>
                                <p class="text-muted small">
                                    Vue réservée aux éditeurs. 
                                    Affiche tous les articles visibles par les visiteurs (non membres), 
                                    classés par date de dernière mise à jour.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📂</div>
                            <div>
                                <strong>Gestionnaire de médias</strong>
                                <p class="text-muted small">Importez et organisez vos fichiers. Pour chaque fichier, vous pouvez :</p>
                                <ul class="text-muted small mb-0 ps-3">
                                    <li><strong>Visualiser</strong> — aperçu dans le navigateur</li>
                                    <li><strong>Copier l''URL</strong> — pour l''insérer dans un article ou un carrousel</li>
                                    <li><strong>Partager</strong> — rendre accessible à d''autres membres</li>
                                    <li><strong>Supprimer</strong> — suppression définitive</li>
                                    <li><strong>Redimensionner</strong> — disponible pour les images uniquement</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📈</div>
                            <div>
                                <strong>Top 50 — articles les plus consultés</strong>
                                <p class="text-muted small">
                                    Classement des articles par nombre de visites sur une période sélectionnable. 
                                    Affiche le titre, l''auteur, l''URL, le temps de réponse, 
                                    le nombre de visites et le pourcentage du trafic total.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🧮</div>
                            <div>
                                <strong>Tableau croisé dynamique</strong>
                                <p class="text-muted small">
                                    Croise les 
                                    <strong>rédacteurs</strong> (colonnes) et les 
                                    <strong>audiences</strong> (lignes) pour visualiser combien d''articles chaque rédacteur 
                                    a publié pour quel public. Sélectionnez la période souhaitée en haut à droite.
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
        <p class="text-muted">La navigation dans l''espace rédaction s''effectue via les icônes de la barre supérieure.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span>
                    Les 
                    <strong>flèches de navigation</strong> 
                    (haut / retour) permettent de revenir à l''espace rédaction ou à la page précédente.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-shield-check fs-3 text-warning me-3"></i>
                <span>
                    <strong>Autorisation « Éditeur » :</strong> 
                    certaines fonctions sont réservées aux responsables de publication (articles publics, 
                    réattribution, publication, tableau croisé).
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>Le bouton <strong>+</strong> dans la liste des articles permet de créer un nouvel article.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Aide :</strong> 
                    Ici vous trouverez toutes les informations nécessaires pour utiliser l''espace rédaction.
                </span>
            </div>
        </div>
    </section>
</div>',

'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Przestrzeń redakcyjna</h1>
    <p class="lead">Zarządzaj artykułami, mediami i śledź wyniki swoich treści.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Przegląd narzędzi</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📰</div>
                            <div>
                                <strong>Artykuły</strong>
                                <p class="text-muted small">
                                    Przeglądaj i zarządzaj listą swoich artykułów. 
                                    Utwórz nowy artykuł za pomocą przycisku 
                                    <strong>+</strong> lub edytuj / wyświetl istniejący.
                                    <span class="d-block mt-1 text-dark">
                                        👉 
                                        <em>
                                            Uprawnienie 
                                            <strong>Redaktora</strong> 
                                            wymagane do przypisania artykułu innemu autorowi lub opublikowania go 
                                            dla wszystkich odwiedzających (szare przyciski).
                                            </em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">⚖️</div>
                            <div>
                                <strong>
                                    Artykuły publiczne 
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">Editor</span>
                                </strong>
                                <p class="text-muted small">
                                    Widok tylko dla redaktorów. 
                                    Wyświetla wszystkie artykuły widoczne dla odwiedzających (niezalogowanych), 
                                    posortowane według daty ostatniej aktualizacji.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📂</div>
                            <div>
                                <strong>Menedżer mediów</strong>
                                <p class="text-muted small">Importuj i organizuj swoje pliki. Dla każdego pliku możesz:</p>
                                <ul class="text-muted small mb-0 ps-3">
                                    <li><strong>Podgląd</strong> — wyświetl w przeglądarce</li>
                                    <li><strong>Kopiuj URL</strong> — aby wstawić do artykułu lub karuzeli</li>
                                    <li><strong>Udostępnij</strong> — udostępnij innym członkom</li>
                                    <li><strong>Usuń</strong> — trwałe usunięcie</li>
                                    <li><strong>Zmień rozmiar</strong> — dostępne tylko dla obrazów</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📈</div>
                            <div>
                                <strong>Top 50 — najczęściej czytane artykuły</strong>
                                <p class="text-muted small">
                                    Ranking artykułów według liczby odwiedzin w wybranym okresie. 
                                    Pokazuje tytuł, autora, URL, czas odpowiedzi, liczbę odwiedzin i procent całkowitego ruchu.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🧮</div>
                            <div>
                                <strong>Tabela przestawna</strong>
                                <p class="text-muted small">
                                    Krzyżuje <strong>autorów</strong> (kolumny) i 
                                    <strong>odbiorców</strong> 
                                    (wiersze), aby pokazać ile artykułów każdy autor opublikował dla danej grupy odbiorców. 
                                    Wybierz żądany okres w prawym górnym rogu.
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
        <p class="text-muted">Nawigacja w przestrzeni redakcyjnej odbywa się za pomocą ikon na górnym pasku.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span>
                    <strong>
                        Strzałki nawigacji</strong> 
                        (góra / wstecz) umożliwiają powrót do przestrzeni redakcyjnej lub poprzedniej strony.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-shield-check fs-3 text-warning me-3"></i>
                <span>
                    <strong>Uprawnienie «Redaktor»:</strong> 
                    niektóre funkcje są zarezerwowane dla menedżerów publikacji 
                    (artykuły publiczne, przypisanie, publikowanie, tabela przestawna).
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>Przycisk <strong>+</strong> na liście artykułów umożliwia utworzenie nowego artykułu.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Pomoc:</strong> Tutaj znajdziesz wszystkie informacje potrzebne do korzystania z przestrzeni redakcyjnej.
                </span>
            </div>
        </div>
    </section>
</div>'
),

('Help_Designer',
'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Design space</h1>
    <p class="lead">Configure the structure, content and appearance of your application.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Tools overview</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🗓️</div>
                            <div>
                                <strong>
                                    Event types
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">EventDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Manage the types of events that can be created (Competition, Training, Meeting…).
                                    Each type can be assigned a 
                                    <strong>group</strong> and a list of 
                                    <strong>attributes</strong> (tags used to qualify events). The 
                                    <strong>Attribute manager</strong> 
                                    at the bottom of the page lets you create, colour and describe each attribute.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                          <div class="me-3" style="font-size:1.75rem; line-height:1;">📋</div>
                          <div>
                            <strong>
                              Needs
                              <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">EventDesigner</span>
                            </strong>
                            <p class="text-muted small">
                              Define the resources an event may require: drinks, food, equipment, speakers, participants…
                              Needs are organised by <strong>type</strong> (left panel) and can be scaled to the number of participants.
                              Each need has a label (emoji), a name and a type.
                            </p>
                          </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🏋️</div>
                            <div>
                                <strong>
                                    Exercises
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">ExerciseDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Build the exercise library used when composing training sessions.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🔁</div>
                            <div>
                                <strong>
                                    Equipment catalogue (Loan)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">LoanDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Declare the equipment available for loan: name, description, type, total quantity and active status.
                                    Members can then consult the catalogue, make reservations and view the availability calendar.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🔧</div>
                            <div>
                                <strong>
                                    Settings
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">HomeDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    General application settings: site name, contact details, default language, etc.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🧠</div>
                            <div>
                                <strong>
                                    Homepage design
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">HomeDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Customise the homepage layout: header, main article, latest articles, footer, accordion.
                                    Click a section in the preview to open its editor. 
                                    You can force a specific language using the selector in the top bar.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🟨</div>
                            <div>
                                <strong>Kanban</strong>
                                <p class="text-muted small">
                                    Configure the Kanban board columns and workflow stages.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📑</div>
                            <div>
                                <strong>Menu items</strong>
                                <p class="text-muted small">
                                    Manage the navigation bar and sidebar links.
                                    Each item has a name, a URL, an optional group restriction and three visibility flags: 
                                    <strong>Members</strong>, 
                                    <strong>Contacts</strong>, 
                                    <strong>Anonymous</strong>.
                                    The preview at the top lets you check the result for each audience.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🌍</div>
                            <div>
                                <strong>Translation manager</strong>
                                <p class="text-muted small">
                                    View and edit all application translations side by side (fr_FR / pl_PL).
                                    Use the <strong>Missing only</strong> filter to find untranslated keys quickly.
                                    Each entry offers an <strong>Edit</strong> tab (raw HTML) and a <strong>Preview</strong> tab.
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
        <h2 class="h4 mb-4">Key takeaways</h2>
        <p class="text-muted">Navigation in the design space is done via the icons in the top bar.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span>
                    The <strong>navigation arrows</strong> (up / back) allow you to return to the design space or the previous page.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-eye fs-3 me-3"></i>
                <span>Many pages include a <strong>live preview</strong> — use it to check the result before saving.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>The <strong>+</strong> button creates a new item (event type, attribute, need, menu item, equipment…).</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span><strong>Help:</strong> Here you will find all the information needed to use the design space.</span>
            </div>
        </div>
    </section>
</div>',

'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Espace design</h1>
    <p class="lead">Configurez la structure, le contenu et l''apparence de votre application.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Présentation des outils</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🗓️</div>
                            <div>
                                <strong>
                                    Types d''événements
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">EventDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Gérez les types d''événements pouvant être créés (Compétition, Entraînement, Réunion…).
                                    Chaque type peut se voir attribuer un <strong>groupe</strong> et une liste d''
                                    <strong>attributs</strong> 
                                    (étiquettes servant à qualifier les événements). Le 
                                    <strong>Gestionnaire d''attributs</strong>
                                    en bas de page permet de créer, coloriser et décrire chaque attribut.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📋</div>
                            <div>
                                <strong>
                                    Besoins
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">EventDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Définissez les ressources qu''un événement peut nécessiter : 
                                    boissons, nourriture, matériel, intervenants, participants…
                                    Les besoins sont organisés par 
                                    <strong>type</strong> (panneau gauche) et peuvent être proportionnels au nombre de participants.
                                    Chaque besoin dispose d''un label (emoji), d''un nom et d''un type.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🏋️</div>
                            <div>
                                <strong>
                                    Exercices
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">ExerciseDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Constituez la bibliothèque d''exercices utilisée lors de la composition des séances d''entraînement.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🔁</div>
                            <div>
                                <strong>
                                    Catalogue du matériel (Prêts)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">LoanDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Déclarez le matériel disponible au prêt : nom, description, type, quantité totale et statut actif.
                                    Les membres peuvent ensuite consulter le catalogue, 
                                    effectuer des réservations et visualiser le calendrier de disponibilité.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🔧</div>
                            <div>
                                <strong>
                                    Paramètres<span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">HomeDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Paramètres généraux de l''application : nom du site, coordonnées, langue par défaut, etc.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🧠</div>
                            <div>
                                <strong>
                                    Design de la page d''accueil
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">HomeDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Personnalisez la mise en page de la page d''accueil : en-tête, article principal, 
                                    derniers articles, pied de page, accordéon.
                                    Cliquez sur une section dans l''aperçu pour ouvrir son éditeur. 
                                    Vous pouvez forcer une langue spécifique via le sélecteur dans la barre supérieure.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🟨</div>
                            <div>
                                <strong>
                                    Kanban
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">KanbanDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Configurez les colonnes et les étapes du tableau Kanban.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📑</div>
                            <div>
                                <strong>
                                    Éléments de menu
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">MenuDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Gérez les liens de la barre de navigation et de la sidebar.
                                    Chaque élément possède un nom, une URL, 
                                    une restriction de groupe optionnelle et trois indicateurs de visibilité : 
                                    <strong>Membres</strong>, 
                                    <strong>Contacts</strong>, 
                                    <strong>Anonymes</strong>.
                                    L''aperçu en haut de page permet de vérifier le rendu pour chaque audience.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🌍</div>
                            <div>
                                <strong>
                                    Gestionnaire de traductions
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">Translator</span>
                                </strong>
                                <p class="text-muted small">
                                    Consultez et modifiez toutes les traductions de l''application côte à côte (fr_FR / pl_PL).
                                    Utilisez le filtre 
                                    <strong>Manquantes uniquement</strong>
                                    pour repérer rapidement les clés non traduites.
                                    Chaque entrée propose un onglet 
                                    <strong>Éditer</strong> (HTML brut) et un onglet 
                                    <strong>Aperçu</strong>.
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
        <p class="text-muted">La navigation dans l''espace design s''effectue via les icônes de la barre supérieure.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span>
                    Les 
                    <strong>flèches de navigation</strong> 
                    (haut / retour) permettent de revenir à l''espace design ou à la page précédente.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-eye fs-3 me-3"></i>
                <span>
                    De nombreuses pages disposent d''un <strong>aperçu en direct</strong> — 
                    utilisez-le pour vérifier le résultat avant d''enregistrer.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>
                    Le bouton 
                    <strong>+</strong> permet de créer un nouvel élément (type d''événement, attribut, besoin, élément de menu, matériel…).
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span><strong>Aide :</strong> Ici vous trouverez toutes les informations nécessaires pour utiliser l''espace design.</span>
            </div>
        </div>
    </section>
</div>',

'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Przestrzeń projektowania</h1>
    <p class="lead">Konfiguruj strukturę, treść i wygląd swojej aplikacji.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Przegląd narzędzi</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🗓️</div>
                            <div>
                                <strong>
                                    Typy wydarzeń
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">EventDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Zarządzaj typami wydarzeń, które można tworzyć (Zawody, Trening, Spotkanie…).
                                    Każdy typ może mieć przypisaną 
                                    <strong>grupę</strong> oraz listę 
                                    <strong>atrybutów</strong> (etykiety służące do kwalifikowania wydarzeń).
                                    <strong>Menedżer atrybutów</strong> 
                                    na dole strony pozwala tworzyć, kolorować i opisywać każdy atrybut.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📋</div>
                            <div>
                                <strong>
                                    Potrzeby
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">EventDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Definiuj zasoby, których wydarzenie może wymagać: napoje, jedzenie, sprzęt, prelegenci, uczestnicy…
                                    Potrzeby są pogrupowane według 
                                    <strong>typu</strong> (lewy panel) i mogą być skalowane względem liczby uczestników.
                                    Każda potrzeba ma etykietę (emoji), nazwę i typ.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🏋️</div>
                            <div>
                                <strong>
                                    Ćwiczenia
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">ExerciseDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Twórz bibliotekę ćwiczeń wykorzystywaną przy układaniu planów treningowych.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🔁</div>
                            <div>
                                <strong>
                                    Katalog sprzętu (Wypożyczenia)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">LoanDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Zadeklaruj sprzęt dostępny do wypożyczenia: nazwa, opis, typ, łączna ilość i status aktywności.
                                    Członkowie mogą następnie przeglądać katalog, składać rezerwacje i sprawdzać kalendarz dostępności.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🔧</div>
                            <div>
                                <strong>
                                    Ustawienia
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">HomeDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Ogólne ustawienia aplikacji: nazwa strony, dane kontaktowe, domyślny język itp.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🧠</div>
                            <div>
                                <strong>
                                    Projekt strony głównej
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">HomeDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Dostosuj układ strony głównej: nagłówek, główny artykuł, najnowsze artykuły, stopka, akordeon.
                                    Kliknij sekcję w podglądzie, aby otworzyć jej edytor. 
                                    Możesz wymusić konkretny język za pomocą selektora na górnym pasku.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🟨</div>
                            <div>
                                <strong>
                                    Kanban
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">KanbanDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Konfiguruj kolumny i etapy tablicy Kanban.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📑</div>
                            <div>
                                <strong>
                                    Elementy menu
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">MenuDesigner</span>
                                </strong>
                                <p class="text-muted small">
                                    Zarządzaj linkami paska nawigacji i paska bocznego.
                                    Każdy element ma nazwę, URL, opcjonalne ograniczenie grupy oraz trzy wskaźniki widoczności: 
                                    <strong>Członkowie</strong>, 
                                    <strong>Kontakty</strong>, 
                                    <strong>Anonimowi</strong>.
                                    Podgląd na górze strony pozwala sprawdzić wynik dla każdej grupy odbiorców.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">🌍</div>
                            <div>
                                <strong>Menedżer tłumaczeń
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">Translator</span>
                                </strong>
                                <p class="text-muted small">
                                    Przeglądaj i edytuj wszystkie tłumaczenia aplikacji obok siebie (fr_FR / pl_PL).
                                    Użyj filtra <strong>Tylko brakujące</strong>, aby szybko znaleźć nieprzetłumaczone klucze.
                                    Każdy wpis oferuje zakładkę <strong>Edytuj</strong> (surowy HTML) i zakładkę <strong>Podgląd</strong>.
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
        <p class="text-muted">Nawigacja w przestrzeni projektowania odbywa się za pomocą ikon na górnym pasku.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span>
                    <strong>Strzałki nawigacji</strong> 
                    (góra / wstecz) umożliwiają powrót do przestrzeni projektowania lub poprzedniej strony.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-eye fs-3 me-3"></i>
                <span>
                    Wiele stron posiada <strong>podgląd na żywo</strong> — używaj go, aby sprawdzić wynik przed zapisaniem.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>
                    Przycisk 
                    <strong>+</strong> tworzy nowy element (typ wydarzenia, atrybut, potrzeba, element menu, sprzęt…).
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Pomoc:</strong> 
                    Tutaj znajdziesz wszystkie informacje potrzebne do korzystania z przestrzeni projektowania.
                </span>
            </div>
        </div>
    </section>
</div>'
),

('Help_EventManager',
'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Event management space</h1>
    <p class="lead">Manage events, schedules and communication with participants.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Tools overview</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📅</div>
                            <div>
                                <strong>Weekly calendar</strong>
                                <p class="text-muted small">
                                    View all events over a rolling 3-week window, laid out day by day.
                                    Each event shows its start time, duration, summary and a coloured attribute badge.
                                    A legend at the bottom explains each attribute colour and the visibility rules 
                                    (members-only, group-restricted, etc.).
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📆</div>
                            <div>
                                <strong>Upcoming events</strong>
                                <p class="text-muted small">
                                    List of events grouped by week, with type, date, duration, attribute, summary, location, 
                                    participant count, message count and audience.
                                    Click a row to view the detail, register or unregister.
                                    Use the <strong>+</strong> button to create a new event.
                                    A checkbox lets you filter to events matching your personal preferences only.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">✉️</div>
                            <div>
                                <strong>Send an invitation</strong>
                                <p class="text-muted small">
                                    Invite an external participant to a specific event by entering their e-mail address, 
                                    an optional name and the target event.
                                    The guest receives a personalised invitation link.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📧</div>
                            <div>
                                <strong>Get emails</strong>
                                <p class="text-muted small">
                                    Extract e-mail addresses of members filtered by 
                                    <strong>group</strong>, 
                                    <strong>event type</strong>, 
                                    <strong>day of the week</strong> and 
                                    <strong>time of day</strong>.
                                    Click 
                                    <strong>Get emails</strong>
                                    to generate the list — useful for targeted communications or bulk invitations.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📊</div>
                            <div>
                                <strong>Pivot table — Leaders vs event types</strong>
                                <p class="text-muted small">
                                    Crosses 
                                    <strong>event leaders</strong> (columns) and 
                                    <strong>event types</strong> (rows) for a selectable period (week / month / quarter / year).
                                    Each cell shows the number of events and the total participants.
                                    Row and column totals are calculated automatically.
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
        <h2 class="h4 mb-4">Key takeaways</h2>
        <p class="text-muted">Navigation in the event management space is done via the icons in the top bar.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span>The <strong>back arrow</strong> allows you to return to the previous page.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-square-fill fs-3 me-3" style="color:#6c757d;"></i>
                <span>
                    <strong>Coloured badges</strong> 
                    on events indicate their attribute (level, type of outing…). Hover over them to read the detail.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>The <strong>+</strong> button in the upcoming events list allows you to create a new event.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span><strong>Help:</strong> Here you will find all the information needed to use the event management space.</span>
            </div>
        </div>
    </section>
</div>',

'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Espace gestion des événements</h1>
    <p class="lead">Gérez les événements, les plannings et la communication avec les participants.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Présentation des outils</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📅</div>
                            <div>
                                <strong>Calendrier hebdomadaire</strong>
                                <p class="text-muted small">
                                    Visualisez tous les événements sur une fenêtre glissante de 3 semaines, disposés jour par jour.
                                    Chaque événement affiche son heure de début, sa durée, son sommaire et un badge coloré d''attribut.
                                    Une légende en bas de page explique chaque couleur d''attribut et les règles de visibilité 
                                    (membres uniquement, groupe restreint, etc.).
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                          <div class="me-3" style="font-size:1.75rem; line-height:1;">📆</div>
                          <div>
                              <strong>Prochains événements</strong>
                              <p class="text-muted small">
                              Liste des événements regroupés par semaine, avec type, date, durée, attribut, sommaire, 
                              lieu, nombre de participants, nombre de messages et audience.
                              Cliquez sur une ligne pour voir le détail, s''inscrire ou se désinscrire.
                              Utilisez le bouton <strong>+</strong> pour créer un nouvel événement.
                              Une case à cocher permet de filtrer uniquement les événements correspondant à vos préférences personnelles.
                              </p>
                          </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">✉️</div>
                            <div>
                                <strong>Envoyer une invitation</strong>
                                <p class="text-muted small">
                                    Invitez un participant externe à un événement spécifique en saisissant son adresse e-mail, 
                                    un nom optionnel et l''événement cible.
                                    L''invité reçoit un lien d''invitation personnalisé.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📧</div>
                            <div>
                                <strong>Get emails</strong>
                                <p class="text-muted small">
                                    Extrayez les adresses e-mail des membres filtrés par 
                                    <strong>groupe</strong>, 
                                    <strong>type d''événement</strong>, 
                                    <strong>jour de la semaine</strong> et 
                                    <strong>moment de la journée</strong>.
                                    Cliquez sur 
                                    <strong>Obtenir les emails</strong> 
                                    pour générer la liste — utile pour des communications ciblées ou des invitations groupées.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📊</div>
                            <div>
                                <strong>Tableau croisé dynamique — Animateurs vs types d''événement</strong>
                                <p class="text-muted small">
                                    Croise les 
                                    <strong>animateurs</strong> 
                                    (colonnes) et les 
                                    <strong>types d''événements</strong> 
                                    (lignes) sur une période sélectionnable (semaine / mois / trimestre / année).
                                    Chaque cellule affiche le nombre d''événements et le total de participants.
                                    Les totaux par ligne et par colonne sont calculés automatiquement.
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
        <p class="text-muted">La navigation dans l''espace gestion des événements s''effectue via les icônes de la barre supérieure.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span>La <strong>flèche retour</strong> permet de revenir à la page précédente.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-square-fill fs-3 me-3" style="color:#6c757d;"></i>
                <span>
                    Les 
                    <strong>badges colorés</strong> 
                    sur les événements indiquent leur attribut (niveau, type de sortie…). Survolez-les pour lire le détail.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>Le bouton <strong>+</strong> dans la liste des prochains événements permet de créer un nouvel événement.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Aide :</strong> 
                    Ici vous trouverez toutes les informations nécessaires pour utiliser l''espace gestion des événements.
                </span>
            </div>
        </div>
    </section>
</div>',

'<div class="container my-5">
    <h1 class="display-5 fw-bold text-primary">Przestrzeń zarządzania wydarzeniami</h1>
    <p class="lead">Zarządzaj wydarzeniami, harmonogramami i komunikacją z uczestnikami.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Przegląd narzędzi</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📅</div>
                            <div>
                                <strong>Kalendarz tygodniowy</strong>
                                <p class="text-muted small">
                                    Przeglądaj wszystkie wydarzenia w ruchomym oknie 3 tygodni, ułożone dzień po dniu.
                                    Każde wydarzenie pokazuje godzinę rozpoczęcia, czas trwania, podsumowanie i kolorową odznakę atrybutu.
                                    Legenda na dole strony wyjaśnia każdy kolor atrybutu oraz zasady widoczności 
                                    (tylko dla członków, ograniczone do grupy itp.).
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📆</div>
                            <div>
                                <strong>Nadchodzące wydarzenia</strong>
                                <p class="text-muted small">
                                    Lista wydarzeń pogrupowanych według tygodnia, z typem, datą, czasem trwania, atrybutem, 
                                    podsumowaniem, miejscem, liczbą uczestników, liczbą wiadomości i odbiorcami.
                                    Kliknij wiersz, aby zobaczyć szczegóły, zapisać się lub wypisać.
                                    Użyj przycisku <strong>+</strong>, aby utworzyć nowe wydarzenie.
                                    Pole wyboru pozwala filtrować tylko wydarzenia odpowiadające Twoim preferencjom.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">✉️</div>
                            <div>
                                <strong>Wyślij zaproszenie</strong>
                                <p class="text-muted small">
                                    Zaproś zewnętrznego uczestnika na konkretne wydarzenie, podając jego adres e-mail, 
                                    opcjonalną nazwę i docelowe wydarzenie.
                                    Gość otrzymuje spersonalizowany link z zaproszeniem.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📧</div>
                            <div>
                                <strong>Pobierz e-maile</strong>
                                <p class="text-muted small">
                                    Wyodrębnij adresy e-mail członków filtrowanych według <strong>grupy</strong>, 
                                    <strong>typu wydarzenia</strong>, <strong>dnia tygodnia</strong> i <strong>pory dnia</strong>.
                                    Kliknij 
                                    <strong>Uzyskaj e-maile</strong>
                                    , aby wygenerować listę — przydatne do ukierunkowanej komunikacji lub zbiorowych zaproszeń.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="me-3" style="font-size:1.75rem; line-height:1;">📊</div>
                            <div>
                                <strong>Tabela przestawna — Animatorzy vs typy wydarzeń</strong>
                                <p class="text-muted small">
                                    Krzyżuje <strong>animatorów</strong> (kolumny) i 
                                    <strong>typy wydarzeń</strong> (wiersze) w wybranym okresie (tydzień / miesiąc / kwartał / rok).
                                    Każda komórka pokazuje liczbę wydarzeń i łączną liczbę uczestników.
                                    Sumy wierszy i kolumn są obliczane automatycznie.
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
        <p class="text-muted">Nawigacja w przestrzeni zarządzania wydarzeniami odbywa się za pomocą ikon na górnym pasku.</p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-left-circle fs-3 me-3"></i>
                <span><strong>Strzałka wstecz</strong> umożliwia powrót do poprzedniej strony.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-square-fill fs-3 me-3" style="color:#6c757d;"></i>
                <span>
                    <strong>Kolorowe odznaki</strong> 
                    na wydarzeniach wskazują ich atrybut (poziom, typ wyjścia…). Najedź na nie kursorem, aby zobaczyć szczegóły.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-plus-circle fs-3 me-3"></i>
                <span>Przycisk <strong>+</strong> na liście nadchodzących wydarzeń umożliwia utworzenie nowego wydarzenia.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Pomoc:</strong> 
                    Tutaj znajdziesz wszystkie informacje potrzebne do korzystania z przestrzeni zarządzania wydarzeniami.
                </span>
            </div>
        </div>
    </section>
</div>'
),

('Help_NextEvents_EventManager',
'<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Contextual Help: Event Management</h1>
        <p class="lead">Create, edit and manage club events.</p>
    </header>

   <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">

                <h2 class="card-title h4 mb-4">Create an Event</h2>

                <div class="d-flex align-items-start">
                    <div class="bg-primary text-white rounded p-2 me-3">
                        <i class="bi bi-plus-lg"></i>
                    </div>
                    <div>
                        <strong>Create a new event</strong>
                        <p class="text-muted small mb-0">
                            The Add button located in the top-right corner allows you to create a new event.
                            You can define the activity type, date, time, duration,
                            location, audience, and maximum number of participants.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">

                <h2 class="card-title h4 mb-4">Other Actions Available to Event Managers</h2>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-info text-white rounded p-2 me-3">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <div>
                        <strong>Notify members</strong>
                        <p class="text-muted small mb-0">
                            Send an email to members who have chosen to receive notifications
                            about events (new event, update, cancellation).
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-primary text-white rounded p-2 me-3">
                        <i class="bi bi-pencil"></i>
                    </div>
                    <div>
                        <strong>Edit an event</strong>
                        <p class="text-muted small mb-0">
                            Only the creator of the event can edit it.
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-danger text-white rounded p-2 me-3">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div>
                        <strong>Delete or cancel an event</strong>
                        <ul class="small text-muted mb-0">
                            <li>No participants registered: the event is permanently deleted.</li>
                            <li>With registered participants: the event is cancelled and displayed with a strikethrough in the list.</li>
                            <li>No new registrations are allowed.</li>
                            <li>Registered participants can still unregister.</li>
                        </ul>
                    </div>
                </div>

                <div class="d-flex align-items-start">
                    <div class="bg-secondary text-white rounded p-2 me-3">
                        <i class="bi bi-files"></i>
                    </div>
                    <div>
                        <strong>Duplicate an event</strong>
                        <p class="text-muted small mb-0">
                            Creates a copy of an existing event to quickly schedule
                            similar or recurring activities.
                        </p>
                    </div>
                </div>

                <hr>

                <span class="d-block mt-1 text-dark">
                    <em>👉 The first 3 buttons are only displayed for events created by the visitor.</em>
                </span>

            </div>
        </div>
    </section>
</div>',
'<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Gestion des événements</h1>
        <p class="lead">Créer, modifier et administrer les événements du club.</p>
    </header>

   <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">

                <h2 class="card-title h4 mb-4">Créer un événement</h2>

                <div class="d-flex align-items-start">
                    <div class="bg-primary text-white rounded p-2 me-3">
                        <i class="bi bi-plus-lg"></i>
                    </div>
                    <div>
                        <strong>Créer un nouvel événement</strong>
                        <p class="text-muted small mb-0">
                            Le bouton Ajouter situé en haut à droite permet de créer un nouvel événement.
                            Vous pouvez définir le type d''activité, la date, l''heure, la durée,
                            le lieu, l''audience et le nombre maximal de participants.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">

                <h2 class="card-title h4 mb-4">Autres actions disponibles pour les EventManagers</h2>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-info text-white rounded p-2 me-3">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <div>
                        <strong>Notifier les membres</strong>
                        <p class="text-muted small mb-0">
                            Envoyer un courriel aux membres qui ont choisi d''être informés
                            des événements (nouvel événement, modification, annulation).
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-primary text-white rounded p-2 me-3">
                        <i class="bi bi-pencil"></i>
                    </div>
                    <div>
                        <strong>Modifier un événement</strong>
                        <p class="text-muted small mb-0">
                            Seul le créateur de l''événement peut le modifier.
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-danger text-white rounded p-2 me-3">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div>
                        <strong>Supprimer ou annuler un événement</strong>
                        <ul class="small text-muted mb-0">
                            <li>Sans inscrit : suppression définitive de l''événement.</li>
                            <li>Avec inscrits : l''événement est annulé et apparaît barré dans la liste.</li>
                            <li>Plus aucune nouvelle inscription n''est possible.</li>
                            <li>Les inscrits peuvent encore se désinscrire.</li>
                        </ul>
                    </div>
                </div>

                <div class="d-flex align-items-start">
                    <div class="bg-secondary text-white rounded p-2 me-3">
                        <i class="bi bi-files"></i>
                    </div>
                    <div>
                        <strong>Dupliquer un événement</strong>
                        <p class="text-muted small mb-0">
                            Crée une copie d''un événement existant afin de planifier rapidement
                            des activités similaires ou récurrentes.
                        </p>
                    </div>
                </div>
                <hr>
                <span class="d-block mt-1 text-dark">
                    <em>👉 Les 3 premiers boutons ne sont présents que devant les événements créés par le visiteur.</em>
                </span>
            </div>
        </div>
    </section>
</div>',
'<div class="container my-5">
        <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Zarządzanie wydarzeniami</h1>
        <p class="lead">Tworzenie, modyfikowanie i zarządzanie wydarzeniami klubu.</p>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">

                <h2 class="card-title h4 mb-4">Tworzenie wydarzenia</h2>

                <div class="d-flex align-items-start">
                    <div class="bg-primary text-white rounded p-2 me-3">
                        <i class="bi bi-plus-lg"></i>
                    </div>
                    <div>
                        <strong>Utwórz nowe wydarzenie</strong>
                        <p class="text-muted small mb-0">
                            Przycisk Dodaj znajdujący się w prawym górnym rogu umożliwia utworzenie nowego wydarzenia.
                            Możesz określić rodzaj aktywności, datę, godzinę, czas trwania,
                            miejsce, grupę odbiorców oraz maksymalną liczbę uczestników.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">

                <h2 class="card-title h4 mb-4">Inne działania dostępne dla menedżerów wydarzeń</h2>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-info text-white rounded p-2 me-3">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <div>
                        <strong>Powiadom członków</strong>
                        <p class="text-muted small mb-0">
                            Wyślij wiadomość e-mail do członków, którzy wybrali opcję otrzymywania
                            informacji o wydarzeniach (nowe wydarzenie, modyfikacja, anulowanie).
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-primary text-white rounded p-2 me-3">
                        <i class="bi bi-pencil"></i>
                    </div>
                    <div>
                        <strong>Edytuj wydarzenie</strong>
                        <p class="text-muted small mb-0">
                            Tylko twórca wydarzenia może je edytować.
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="bg-danger text-white rounded p-2 me-3">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div>
                        <strong>Usuń lub anuluj wydarzenie</strong>
                        <ul class="small text-muted mb-0">
                            <li>Bez zapisanych uczestników: wydarzenie zostaje trwale usunięte.</li>
                            <li>Z zapisanymi uczestnikami: wydarzenie zostaje anulowane i wyświetlane jest jako przekreślone na liście.</li>
                            <li>Nie są już możliwe nowe zapisy.</li>
                            <li>Zapisani uczestnicy mogą nadal się wypisać.</li>
                        </ul>
                    </div>
                </div>

                <div class="d-flex align-items-start">
                    <div class="bg-secondary text-white rounded p-2 me-3">
                        <i class="bi bi-files"></i>
                    </div>
                    <div>
                        <strong>Duplikuj wydarzenie</strong>
                        <p class="text-muted small mb-0">
                            Tworzy kopię istniejącego wydarzenia, aby szybko zaplanować
                            podobne lub cykliczne aktywności.
                        </p>
                    </div>
                </div>

                <hr>

                <span class="d-block mt-1 text-dark">
                    <em>👉 Pierwsze 3 przyciski są widoczne tylko przy wydarzeniach utworzonych przez odwiedzającego.</em>
                </span>

            </div>
        </div>
    </section>
</div>'
);
SQL);

        return 75;
    }
}
