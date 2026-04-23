<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V49ToV50Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS LoanItem (
    Id          INTEGER PRIMARY KEY AUTOINCREMENT,
    Name        TEXT    NOT NULL,
    Description TEXT    NOT NULL DEFAULT '',
    Type        TEXT    NOT NULL DEFAULT 'both' CHECK(Type IN ('loan','reservation','both')),
    Quantity    INTEGER NOT NULL DEFAULT 1,
    IsActive    INTEGER NOT NULL DEFAULT 1,
    CreatedAt   TEXT    NOT NULL DEFAULT (datetime('now')),
    UpdatedAt   TEXT    NOT NULL DEFAULT (datetime('now'))
);
SQL);

        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS LoanRecord (
    Id            INTEGER PRIMARY KEY AUTOINCREMENT,
    ItemId        INTEGER NOT NULL REFERENCES LoanItem(Id),
    BorrowerId    INTEGER NOT NULL REFERENCES Person(Id),
    LenderId      INTEGER NOT NULL REFERENCES Person(Id),
    LoanDate      TEXT    NOT NULL,
    DueDate       TEXT    NOT NULL,
    ReturnDate    TEXT,
    ReturnedToId  INTEGER REFERENCES Person(Id),
    QuantityLent  INTEGER NOT NULL DEFAULT 1,
    Notes         TEXT    NOT NULL DEFAULT '',
    Status        TEXT    NOT NULL DEFAULT 'active'
                  CHECK(Status IN ('active','returned','overdue','cancelled')),
    CreatedAt     TEXT    NOT NULL DEFAULT (datetime('now'))
);
SQL);

        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS LoanReservation (
    Id                INTEGER PRIMARY KEY AUTOINCREMENT,
    ItemId            INTEGER NOT NULL REFERENCES LoanItem(Id),
    UserId            INTEGER NOT NULL REFERENCES Person(Id),
    ReservationDate   TEXT    NOT NULL,
    StartTime         TEXT    NOT NULL,
    EndTime           TEXT    NOT NULL,
    QuantityReserved  INTEGER NOT NULL DEFAULT 1,
    Notes             TEXT    NOT NULL DEFAULT '',
    Status            TEXT    NOT NULL DEFAULT 'active'
                      CHECK(Status IN ('active','cancelled')),
    CreatedAt         TEXT    NOT NULL DEFAULT (datetime('now'))
);
SQL);

        $pdo->exec(<<<SQL
INSERT INTO Authorization (Id, Name) VALUES (13, 'LoanDesigner');
INSERT INTO Authorization (Id, Name) VALUES (14, 'LoanManager');
SQL);

        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('loan.nav.designer',    'Material Catalog',              'Catalogue du matériel',            'Katalog materiałów'),
('loan.nav.manager',     'Loan Management',               'Gestion des prêts',                'Zarządzanie pożyczkami'),
('loan.nav.user',        'My Reservations',               'Mes réservations',                 'Moje rezerwacje'),
('loan.nav.calendar',    'Calendar',                      'Calendrier',                       'Kalendarz'),

('loan.item.title',              'Material Catalog',      'Catalogue du matériel',            'Katalog materiałów'),
('loan.item.add',                'Add material',          'Ajouter un matériel',              'Dodaj materiał'),
('loan.item.edit',               'Edit material',         'Modifier le matériel',             'Edytuj materiał'),
('loan.item.name',               'Name',                  'Nom',                              'Nazwa'),
('loan.item.description',        'Description',           'Description',                      'Opis'),
('loan.item.type',               'Type',                  'Type',                             'Typ'),
('loan.item.type.loan',          'Loan (take away)',      'Prêt (à emporter)',                'Pożyczka (do zabrania)'),
('loan.item.type.reservation',   'Reservation (on-site)', 'Réservation (sur place)',         'Rezerwacja (na miejscu)'),
('loan.item.type.both',          'Both',                  'Les deux',                         'Oba'),
('loan.item.quantity',           'Total quantity',        'Quantité totale',                  'Łączna ilość'),
('loan.item.active',             'Active',                'Actif',                            'Aktywny'),
('loan.item.delete_confirm',     'Delete this material?', 'Supprimer ce matériel ?',          'Usunąć ten materiał?'),
('loan.item.no_items',           'No materials defined.', 'Aucun matériel défini.',           'Brak zdefiniowanych materiałów.'),

('loan.record.title',            'Loans',                 'Prêts',                            'Pożyczki'),
('loan.record.add',              'New loan',              'Nouveau prêt',                     'Nowa pożyczka'),
('loan.record.item',             'Material',              'Matériel',                         'Materiał'),
('loan.record.borrower',         'Borrower',              'Emprunteur',                       'Pożyczkobiorca'),
('loan.record.lender',           'Lent by',               'Prêté par',                        'Pożyczone przez'),
('loan.record.loan_date',        'Loan date',             'Date de prêt',                     'Data pożyczki'),
('loan.record.due_date',         'Due date',              'Date de retour prévue',            'Termin zwrotu'),
('loan.record.return_date',      'Return date',           'Date de retour effectif',          'Data zwrotu'),
('loan.record.returned_to',      'Returned to',           'Rendu à',                          'Zwrócono do'),
('loan.record.quantity',         'Quantity',              'Quantité',                         'Ilość'),
('loan.record.notes',            'Notes',                 'Notes',                            'Uwagi'),
('loan.record.status',           'Status',                'Statut',                           'Status'),
('loan.record.status.active',    'Active',                'En cours',                         'Aktywna'),
('loan.record.status.returned',  'Returned',              'Rendu',                            'Zwrócona'),
('loan.record.status.overdue',   'Overdue',               'En retard',                        'Przeterminowana'),
('loan.record.status.cancelled', 'Cancelled',             'Annulé',                           'Anulowana'),
('loan.record.return_action',    'Register return',       'Enregistrer le retour',            'Zarejestruj zwrot'),
('loan.record.no_records',       'No loans recorded.',    'Aucun prêt enregistré.',           'Brak zarejestrowanych pożyczek.'),

('loan.reservation.title',            'Reservations',          'Réservations',                 'Rezerwacje'),
('loan.reservation.add',              'New reservation',       'Nouvelle réservation',         'Nowa rezerwacja'),
('loan.reservation.item',             'Material',              'Matériel',                     'Materiał'),
('loan.reservation.date',             'Date',                  'Date',                         'Data'),
('loan.reservation.start',            'Start time',            'Heure de début',               'Godzina rozpoczęcia'),
('loan.reservation.end',              'End time',              'Heure de fin',                 'Godzina zakończenia'),
('loan.reservation.quantity',         'Quantity',              'Quantité',                     'Ilość'),
('loan.reservation.notes',            'Notes',                 'Notes',                        'Uwagi'),
('loan.reservation.status',           'Status',                'Statut',                       'Status'),
('loan.reservation.status.active',    'Active',                'Active',                       'Aktywna'),
('loan.reservation.status.cancelled', 'Cancelled',             'Annulée',                      'Anulowana'),
('loan.reservation.cancel_confirm',   'Cancel this reservation?', 'Annuler cette réservation ?', 'Anulować tę rezerwację?'),
('loan.reservation.no_reservations',  'No reservations.',      'Aucune réservation.',          'Brak rezerwacji.'),

('loan.calendar.title',        'Loans & Reservations',      'Prêts et réservations',            'Pożyczki i rezerwacje'),
('loan.calendar.loans',        'Loans',                     'Prêts',                            'Pożyczki'),
('loan.calendar.reservations', 'Reservations',              'Réservations',                     'Rezerwacje'),

('loan.availability.available',   'Available',               'Disponible',                       'Dostępny'),
('loan.availability.unavailable', 'Unavailable',             'Indisponible',                     'Niedostępny'),
('loan.availability.partial',     'Partially available',     'Partiellement disponible',         'Częściowo dostępny'),

('loan.msg.saved',          'Saved successfully.',   'Enregistré avec succès.',   'Zapisano pomyślnie.'),
('loan.msg.deleted',        'Deleted successfully.', 'Supprimé avec succès.',     'Usunięto pomyślnie.'),
('loan.msg.returned',       'Return registered.',    'Retour enregistré.',        'Zwrot zarejestrowany.'),
('loan.msg.cancelled',      'Cancelled.',            'Annulé.',                   'Anulowano.'),
('loan.msg.error',          'An error occurred.',    'Une erreur est survenue.',  'Wystąpił błąd.'),
('loan.msg.qty_exceeded',   'Requested quantity exceeds available stock.', 'La quantité demandée dépasse le stock disponible.', 'Żądana ilość przekracza dostępne zapasy.'),

('Help_LoanDesigner',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Material Loan &amp; Reservation</h1>
    <p class="lead">Manage your club''s equipment: lend items to take away or reserve them for on-site use.</p>
  </header>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">The 3 roles</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🎨</span>
              <div><strong>Designer</strong>
              <p class="text-muted small">Creates and manages the material catalogue: name, description, type (loan / reservation / both) and total available quantity.</p></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">📋</span>
              <div><strong>Manager</strong>
              <p class="text-muted small">Handles loans of items to take away: records who lends what to whom, for how long, and registers returns.</p></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">👤</span>
              <div><strong>User</strong>
              <p class="text-muted small">Books items for on-site use: chooses a date, a time slot and a quantity. No return step required.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Material catalogue <span class="badge bg-primary ms-2 fs-6">Designer</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-seam-fill"></i></div>
              <div><strong>Item type</strong>
              <p class="text-muted small">Each item is tagged <em>Loan (take away)</em>, <em>Reservation (on-site)</em> or <em>Both</em>. This controls where it appears in the Manager and User views.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-123"></i></div>
              <div><strong>Total quantity</strong>
              <p class="text-muted small">Defines the maximum number of units available simultaneously. The system prevents overbooking automatically.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-toggle-on"></i></div>
              <div><strong>Active / Inactive</strong>
              <p class="text-muted small">An inactive item no longer appears in loan or reservation forms, but its history is preserved.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
              <div><strong>Deletion</strong>
              <p class="text-muted small">An item cannot be deleted if it has active loans or reservations. Deactivate it instead.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Loans <span class="badge bg-primary ms-2 fs-6">Manager</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>Creating a loan</strong>
              <p class="text-muted small">Record who lends (lender), who borrows (borrower), the loan date, the expected return date and the quantity. Real-time availability is shown before saving.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-check2-circle"></i></div>
              <div><strong>Registering a return</strong>
              <p class="text-muted small">Click <strong>Register return</strong> <i class="bi bi-check2-circle"></i> on an active loan. Enter the actual return date and the person who received the item.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-danger text-white rounded p-2 me-3"><i class="bi bi-exclamation-circle-fill"></i></div>
              <div><strong>Overdue loans</strong>
              <p class="text-muted small">Loans past their due date are automatically flagged <em>Overdue</em> <i class="bi bi-exclamation-triangle-fill text-danger"></i>. They remain visible and can still be returned.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Status filter</strong>
              <p class="text-muted small">Filter the list by status: <em>Active</em>, <em>Overdue</em>, <em>Returned</em> or <em>Cancelled</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reservations <span class="badge bg-warning text-dark ms-2 fs-6">User</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-calendar-plus-fill"></i></div>
              <div><strong>Creating a reservation</strong>
              <p class="text-muted small">Choose an item, a date, a start time, an end time and a quantity. The system checks availability in real time for the selected slot.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>No return required</strong>
              <p class="text-muted small">On-site reservations have no return step. Simply cancel a reservation if it is no longer needed.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-person-badge-fill"></i></div>
              <div><strong>Manager view</strong>
              <p class="text-muted small">Managers see all users'' reservations and can cancel any of them. Standard users only see their own.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Calendar</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-primary fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Blue – Active loan:</strong> item currently out on loan.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Green – Returned loan:</strong> item successfully returned.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Red – Overdue loan:</strong> item not returned by the due date.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge fs-6 me-3" style="background:#fd7e14">&nbsp;&nbsp;</span>
        <span><strong>Orange – Reservation:</strong> on-site time slot booked.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> <strong>LoanDesigner</strong> manages the catalogue. <strong>LoanManager</strong> manages loans and can view all reservations. Any connected member can book an on-site reservation and view the calendar.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Prêt et Réservation de Matériel</h1>
    <p class="lead">Gérez le matériel du club : prêtez des articles à emporter ou réservez-les pour une utilisation sur place.</p>
  </header>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les 3 rôles</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🎨</span>
              <div><strong>Designer</strong>
              <p class="text-muted small">Crée et gère le catalogue du matériel : nom, description, type (prêt / réservation / les deux) et quantité totale disponible.</p></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">📋</span>
              <div><strong>Manager</strong>
              <p class="text-muted small">Gère les prêts à emporter : enregistre qui prête quoi à qui, pour combien de temps, et gère les retours.</p></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">👤</span>
              <div><strong>Utilisateur</strong>
              <p class="text-muted small">Réserve du matériel pour une utilisation sur place : choisit une date, un créneau horaire et une quantité. Aucune remise à gérer.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Catalogue du matériel <span class="badge bg-primary ms-2 fs-6">Designer</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-seam-fill"></i></div>
              <div><strong>Type de matériel</strong>
              <p class="text-muted small">Chaque article est classé <em>Prêt (à emporter)</em>, <em>Réservation (sur place)</em> ou <em>Les deux</em>. Ce paramètre détermine où il apparaît dans les vues Manager et Utilisateur.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-123"></i></div>
              <div><strong>Quantité totale</strong>
              <p class="text-muted small">Définit le nombre maximum d''unités disponibles simultanément. Le système empêche automatiquement le survol de stock.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-toggle-on"></i></div>
              <div><strong>Actif / Inactif</strong>
              <p class="text-muted small">Un article inactif n''apparaît plus dans les formulaires de prêt ou de réservation, mais son historique est conservé.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
              <div><strong>Suppression</strong>
              <p class="text-muted small">Un article ne peut pas être supprimé s''il possède des prêts ou réservations actifs. Désactivez-le à la place.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Prêts <span class="badge bg-primary ms-2 fs-6">Manager</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>Créer un prêt</strong>
              <p class="text-muted small">Enregistrez qui prête (prêteur), qui emprunte (emprunteur), la date de prêt, la date de retour prévue et la quantité. La disponibilité est vérifiée en temps réel avant l''enregistrement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-check2-circle"></i></div>
              <div><strong>Enregistrer un retour</strong>
              <p class="text-muted small">Cliquez sur <strong>Enregistrer le retour</strong> <i class="bi bi-check2-circle"></i> sur un prêt actif. Saisissez la date de retour effective et la personne qui récupère le matériel.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-danger text-white rounded p-2 me-3"><i class="bi bi-exclamation-circle-fill"></i></div>
              <div><strong>Prêts en retard</strong>
              <p class="text-muted small">Les prêts dont la date de retour prévue est dépassée passent automatiquement en statut <em>En retard</em> <i class="bi bi-exclamation-triangle-fill text-danger"></i>. Ils restent visibles et peuvent toujours être clôturés.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Filtre par statut</strong>
              <p class="text-muted small">Filtrez la liste par statut : <em>En cours</em>, <em>En retard</em>, <em>Rendu</em> ou <em>Annulé</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Réservations <span class="badge bg-warning text-dark ms-2 fs-6">Utilisateur</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-calendar-plus-fill"></i></div>
              <div><strong>Créer une réservation</strong>
              <p class="text-muted small">Choisissez un matériel, une date, une heure de début, une heure de fin et une quantité. La disponibilité est vérifiée en temps réel pour le créneau sélectionné.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>Pas de retour à gérer</strong>
              <p class="text-muted small">Les réservations sur place n''ont pas d''étape de remise. Il suffit d''annuler une réservation si elle n''est plus nécessaire.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-person-badge-fill"></i></div>
              <div><strong>Vue Manager</strong>
              <p class="text-muted small">Les managers voient les réservations de tous les utilisateurs et peuvent en annuler n''importe laquelle. Les utilisateurs simples ne voient que les leurs.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <hr class="my-5">

  <section>
    <h2 class="h4 mb-4">Calendrier</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-primary fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Bleu – Prêt actif :</strong> matériel actuellement sorti en prêt.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Vert – Prêt rendu :</strong> matériel retourné avec succès.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Rouge – Prêt en retard :</strong> matériel non rendu à la date prévue.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge fs-6 me-3" style="background:#fd7e14">&nbsp;&nbsp;</span>
        <span><strong>Orange – Réservation :</strong> créneau sur place réservé.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> <strong>LoanDesigner</strong> gère le catalogue. <strong>LoanManager</strong> gère les prêts et peut consulter toutes les réservations. Tout membre connecté peut créer une réservation sur place et consulter le calendrier.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Pożyczki i Rezerwacje Sprzętu</h1>
    <p class="lead">Zarządzaj sprzętem klubu: pożyczaj przedmioty do zabrania lub rezerwuj je do użytku na miejscu.</p>
  </header>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">3 role</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🎨</span>
              <div><strong>Designer</strong>
              <p class="text-muted small">Tworzy katalog sprzętu i nim zarządza: nazwa, opis, typ (pożyczka / rezerwacja / oba) oraz łączna dostępna ilość.</p></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">📋</span>
              <div><strong>Manager</strong>
              <p class="text-muted small">Zarządza pożyczkami sprzętu do zabrania: rejestruje kto pożycza co komu, na jak długo oraz obsługuje zwroty.</p></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">👤</span>
              <div><strong>Użytkownik</strong>
              <p class="text-muted small">Rezerwuje sprzęt do użytku na miejscu: wybiera datę, przedział czasowy i ilość. Nie ma etapu zwrotu.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Katalog sprzętu <span class="badge bg-primary ms-2 fs-6">Designer</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-seam-fill"></i></div>
              <div><strong>Typ przedmiotu</strong>
              <p class="text-muted small">Każdy przedmiot jest oznaczony jako <em>Pożyczka (do zabrania)</em>, <em>Rezerwacja (na miejscu)</em> lub <em>Oba</em>. Decyduje to o tym, gdzie pojawia się w widokach Managera i Użytkownika.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-123"></i></div>
              <div><strong>Łączna ilość</strong>
              <p class="text-muted small">Określa maksymalną liczbę jednostek dostępnych jednocześnie. System automatycznie zapobiega nadrezerwacji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-toggle-on"></i></div>
              <div><strong>Aktywny / Nieaktywny</strong>
              <p class="text-muted small">Nieaktywny przedmiot nie pojawia się w formularzach pożyczek ani rezerwacji, ale jego historia jest zachowana.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
              <div><strong>Usuwanie</strong>
              <p class="text-muted small">Przedmiotu nie można usunąć, jeśli ma aktywne pożyczki lub rezerwacje. Zamiast tego należy go dezaktywować.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Pożyczki <span class="badge bg-primary ms-2 fs-6">Manager</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>Tworzenie pożyczki</strong>
              <p class="text-muted small">Zarejestruj kto pożycza (pożyczkodawca), kto bierze (pożyczkobiorca), datę pożyczki, planowany termin zwrotu i ilość. Dostępność jest sprawdzana w czasie rzeczywistym przed zapisem.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-check2-circle"></i></div>
              <div><strong>Rejestrowanie zwrotu</strong>
              <p class="text-muted small">Kliknij <strong>Zarejestruj zwrot</strong> <i class="bi bi-check2-circle"></i> przy aktywnej pożyczce. Podaj faktyczną datę zwrotu i osobę, która odebrała sprzęt.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-danger text-white rounded p-2 me-3"><i class="bi bi-exclamation-circle-fill"></i></div>
              <div><strong>Pożyczki przeterminowane</strong>
              <p class="text-muted small">Pożyczki po terminie zwrotu automatycznie zmieniają status na <em>Przeterminowane</em> <i class="bi bi-exclamation-triangle-fill text-danger"></i>. Pozostają widoczne i nadal można je zamknąć.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Filtr statusu</strong>
              <p class="text-muted small">Filtruj listę według statusu: <em>Aktywna</em>, <em>Przeterminowana</em>, <em>Zwrócona</em> lub <em>Anulowana</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Rezerwacje <span class="badge bg-warning text-dark ms-2 fs-6">Użytkownik</span></h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-calendar-plus-fill"></i></div>
              <div><strong>Tworzenie rezerwacji</strong>
              <p class="text-muted small">Wybierz przedmiot, datę, godzinę rozpoczęcia, godzinę zakończenia i ilość. System sprawdza dostępność w czasie rzeczywistym dla wybranego przedziału.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>Brak etapu zwrotu</strong>
              <p class="text-muted small">Rezerwacje na miejscu nie wymagają zwrotu. Wystarczy anulować rezerwację, gdy nie jest już potrzebna.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-person-badge-fill"></i></div>
              <div><strong>Widok Managera</strong>
              <p class="text-muted small">Managerowie widzą rezerwacje wszystkich użytkowników i mogą anulować dowolną z nich. Zwykli użytkownicy widzą tylko swoje.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <hr class="my-5">

  <section>
    <h2 class="h4 mb-4">Kalendarz</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-primary fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Niebieski – Aktywna pożyczka:</strong> sprzęt aktualnie wypożyczony.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Zielony – Zwrócona pożyczka:</strong> sprzęt pomyślnie zwrócony.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3">&nbsp;&nbsp;</span>
        <span><strong>Czerwony – Pożyczka przeterminowana:</strong> sprzęt nie zwrócony w terminie.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge fs-6 me-3" style="background:#fd7e14">&nbsp;&nbsp;</span>
        <span><strong>Pomarańczowy – Rezerwacja:</strong> zarezerwowany przedział na miejscu.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> <strong>LoanDesigner</strong> zarządza katalogiem. <strong>LoanManager</strong> zarządza pożyczkami i może przeglądać wszystkie rezerwacje. Każdy zalogowany członek może tworzyć rezerwacje na miejscu i przeglądać kalendarz.</span>
      </div>
    </div>
  </section>
</div>');
SQL);

        return 50;
    }
}
