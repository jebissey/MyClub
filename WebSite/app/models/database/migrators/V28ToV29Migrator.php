<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V28ToV29Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('Help_KanbanDesigner',
 '<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Kanban Board</h1>
    <p class="lead">Manage your projects visually by moving cards between 4 columns.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">The 4 columns</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">💡</span>
              <div><strong>Ideas / Backlog</strong>
              <p class="text-muted small">All ideas to explore. No commitment to implement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">✅</span>
              <div><strong>To do</strong>
              <p class="text-muted small">Validated tasks ready to be handled.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🔧</span>
              <div><strong>In progress</strong>
              <p class="text-muted small">Work currently in development or processing.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🏁</span>
              <div><strong>Done</strong>
              <p class="text-muted small">Completed tasks, but also <strong>rejected ideas</strong> archived here for reference.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Cards</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-tags-fill"></i></div>
              <div><strong>Customisable types</strong>
              <p class="text-muted small">Each project defines its own card types (e.g. 🚀 Feature, 🐛 Bug…). Unlimited types.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-palette-fill"></i></div>
              <div><strong>Bootstrap colours</strong>
              <p class="text-muted small">All standard Bootstrap colours are available. 👉 <em>Colour meaning is specific to each project.</em></p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hand-index-thumb-fill"></i></div>
              <div><strong>Moving a card</strong>
              <p class="text-muted small">Drag &amp; drop a card to the destination column. It moves there immediately.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-history"></i></div>
              <div><strong>Movement history</strong>
              <p class="text-muted small">Every move is recorded with date and time (e.g. 💡 → ✅). You can add a <strong>note</strong> to each step. 👉 <em>Available via the 👁️ View button.</em></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <p class="text-muted">The three action buttons on each card:</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3"><i class="bi bi-eye-fill"></i></span>
        <span><strong>View:</strong> Shows the full card details and its movement <strong>history</strong> with notes.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-warning text-dark fs-6 me-3"><i class="bi bi-pencil-fill"></i></span>
        <span><strong>Edit:</strong> Edit the title, description and type of the card.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3"><i class="bi bi-trash-fill"></i></span>
        <span><strong>Delete:</strong> Permanently deletes the card after confirmation.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Only members with the <strong>KanbanDesigner</strong> authorisation can access the Kanban Board. Only the <strong>project creator</strong> can modify or configure it.</span>
      </div>
    </div>
  </section>
</div>',

 '<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Kanban Board</h1>
    <p class="lead">Gérez vos projets visuellement en déplaçant des cartes entre 4 colonnes.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les 4 colonnes</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">💡</span>
              <div><strong>Idées / Backlog</strong>
              <p class="text-muted small">Toutes les idées à explorer. Aucun engagement de réalisation.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">✅</span>
              <div><strong>À faire</strong>
              <p class="text-muted small">Tâches validées et prêtes à être prises en charge.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🔧</span>
              <div><strong>En cours</strong>
              <p class="text-muted small">Travaux actuellement en développement ou en traitement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🏁</span>
              <div><strong>Terminé</strong>
              <p class="text-muted small">Tâches finalisées, mais aussi les <strong>idées non retenues</strong> que l''on archive ici pour mémoire.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les cartes</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-tags-fill"></i></div>
              <div><strong>Types personnalisables</strong>
              <p class="text-muted small">Chaque projet définit ses propres types de carte (ex. 🚀 Fonctionnalité, 🐛 Bug…). Le nombre de types est illimité.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-palette-fill"></i></div>
              <div><strong>Couleurs Bootstrap</strong>
              <p class="text-muted small">Toutes les couleurs standard Bootstrap sont disponibles. 👉 <em>La signification d''une couleur est propre à chaque projet.</em></p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hand-index-thumb-fill"></i></div>
              <div><strong>Déplacer une carte</strong>
              <p class="text-muted small">Faites glisser une carte par <strong>drag &amp; drop</strong> vers la colonne de destination. Elle s''y positionne immédiatement.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-history"></i></div>
              <div><strong>Historique des déplacements</strong>
              <p class="text-muted small">Chaque déplacement est enregistré automatiquement avec sa date et heure (ex. 💡 → ✅). Vous pouvez ajouter une <strong>remarque</strong> à chaque étape. 👉 <em>Accessible via le bouton 👁️ Visualiser de la carte.</em></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <p class="text-muted">Les trois boutons d''action présents sur chaque carte :</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3"><i class="bi bi-eye-fill"></i></span>
        <span><strong>Visualiser :</strong> Affiche le détail complet de la carte et son <strong>historique</strong> de déplacements avec les remarques.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-warning text-dark fs-6 me-3"><i class="bi bi-pencil-fill"></i></span>
        <span><strong>Modifier :</strong> Édite le titre, le détail et le type de la carte.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3"><i class="bi bi-trash-fill"></i></span>
        <span><strong>Supprimer :</strong> Supprime définitivement la carte après confirmation.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> Seuls les membres avec l''autorisation <strong>KanbanDesigner</strong> accèdent au Kanban Board. Seul le <strong>créateur d''un projet</strong> peut le modifier ou le configurer.</span>
      </div>
    </div>
  </section>
</div>',

 '<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Kanban Board</h1>
    <p class="lead">Zarządzaj projektami wizualnie, przesuwając karty między 4 kolumnami.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">4 kolumny</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">💡</span>
              <div><strong>Pomysły / Backlog</strong>
              <p class="text-muted small">Wszystkie pomysły do zbadania. Brak zobowiązania do realizacji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">✅</span>
              <div><strong>Do zrobienia</strong>
              <p class="text-muted small">Zatwierdzone zadania gotowe do realizacji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🔧</span>
              <div><strong>W toku</strong>
              <p class="text-muted small">Prace aktualnie w trakcie realizacji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🏁</span>
              <div><strong>Ukończone</strong>
              <p class="text-muted small">Ukończone zadania, ale też <strong>odrzucone pomysły</strong> zarchiwizowane tutaj dla pamięci.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Karty</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-tags-fill"></i></div>
              <div><strong>Konfigurowalne typy</strong>
              <p class="text-muted small">Każdy projekt definiuje własne typy kart (np. 🚀 Funkcja, 🐛 Błąd…). Liczba typów jest nieograniczona.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-palette-fill"></i></div>
              <div><strong>Kolory Bootstrap</strong>
              <p class="text-muted small">Dostępne są wszystkie standardowe kolory Bootstrap. 👉 <em>Znaczenie koloru jest specyficzne dla każdego projektu.</em></p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hand-index-thumb-fill"></i></div>
              <div><strong>Przenoszenie karty</strong>
              <p class="text-muted small">Przeciągnij kartę metodą <strong>drag &amp; drop</strong> do docelowej kolumny. Karta zostaje tam natychmiast.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-history"></i></div>
              <div><strong>Historia przesunięć</strong>
              <p class="text-muted small">Każde przesunięcie jest automatycznie rejestrowane z datą i godziną (np. 💡 → ✅). Do każdego kroku można dodać <strong>uwagę</strong>. 👉 <em>Dostępne przez przycisk 👁️ Podgląd karty.</em></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <p class="text-muted">Trzy przyciski akcji na każdej karcie:</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3"><i class="bi bi-eye-fill"></i></span>
        <span><strong>Podgląd:</strong> Wyświetla pełne szczegóły karty i jej <strong>historię</strong> przesunięć z uwagami.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-warning text-dark fs-6 me-3"><i class="bi bi-pencil-fill"></i></span>
        <span><strong>Edytuj:</strong> Edytuje tytuł, opis i typ karty.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3"><i class="bi bi-trash-fill"></i></span>
        <span><strong>Usuń:</strong> Trwale usuwa kartę po potwierdzeniu.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Tylko członkowie z uprawnieniem <strong>KanbanDesigner</strong> mają dostęp do Kanban Board. Tylko <strong>twórca projektu</strong> może go modyfikować lub konfigurować.</span>
      </div>
    </div>
  </section>
</div>');
SQL;
        $pdo->exec($sql);

        return 29;
    }
}