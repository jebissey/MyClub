<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V112ToV113Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Group list page: messages previously hard-coded in groupsIndex.js
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('person_manager.group.js.no_users',
    'No user in this group.', 'Aucun utilisateur dans ce groupe.', 'Brak użytkowników w tej grupie.'),
('person_manager.group.js.unexpected_response',
    'Unexpected response.', 'Réponse inattendue.', 'Nieoczekiwana odpowiedź.'),
('person_manager.group.js.load_error',
    'Loading error.', 'Erreur de chargement.', 'Błąd ładowania.');

-- Keys no longer referenced: group forms now use the common 'cancel' and 'save' keys,
-- and the member fields page uses custom_fields.delete_confirm
DELETE FROM Languages WHERE Name IN (
    'person_manager.group.create.cancel',
    'person_manager.group.create.save',
    'person_manager.member_fields.delete_confirm'
);

-- Contextual help of the account edition page: for a person manager editing a member, and for a member editing their own account
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('Help_PersonEdit','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Edit a member account</span>
    </div>
    <div class="card-body">
      <p>You are editing the account of a member. Only person managers can see this page.</p>
      <dl class="mb-0">
        <dt>Email</dt>
        <dd>Identifies the member. It cannot be changed for a record coming from an import (the email is the synchronization key).
            If the address is already used by another member, the change is refused and that member is shown.</dd>
        <dt>First name, last name</dt>
        <dd>Read-only for imported records.</dd>
        <dt>Alert</dt>
        <dd>Short text flagging something about this member.</dd>
        <dt>Member information</dt>
        <dd>Free notes, reserved to managers.</dd>
        <dt>Additional fields</dt>
        <dd>Defined in the settings page of the person manager menu.</dd>
      </dl>
    </div>
  </div>
</div>','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Modifier le compte d''un membre</span>
    </div>
    <div class="card-body">
      <p>Vous modifiez le compte d''un membre. Seuls les gestionnaires des personnes voient cette page.</p>
      <dl class="mb-0">
        <dt>Courriel</dt>
        <dd>Identifie le membre. Il ne peut pas être modifié pour une fiche issue d''un import (le courriel est la clé de synchronisation).
            Si l''adresse est déjà utilisée par un autre membre, la modification est refusée et ce membre est affiché.</dd>
        <dt>Prénom, nom</dt>
        <dd>En lecture seule pour les fiches importées.</dd>
        <dt>Alerte</dt>
        <dd>Court texte signalant quelque chose à propos de ce membre.</dd>
        <dt>Informations sur le membre</dt>
        <dd>Notes libres, réservées aux gestionnaires.</dd>
        <dt>Champs supplémentaires</dt>
        <dd>Définis dans la page de paramètres du menu de gestion des personnes.</dd>
      </dl>
    </div>
  </div>
</div>','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Edycja konta członka</span>
    </div>
    <div class="card-body">
      <p>Edytujesz konto członka. Tę stronę widzą tylko osoby zarządzające członkami.</p>
      <dl class="mb-0">
        <dt>E-mail</dt>
        <dd>Identyfikuje członka. Nie można go zmienić dla rekordu pochodzącego z importu (e-mail jest kluczem synchronizacji).
            Jeśli adres jest już używany przez innego członka, zmiana jest odrzucana i wyświetlany jest ten członek.</dd>
        <dt>Imię, nazwisko</dt>
        <dd>Tylko do odczytu dla rekordów zaimportowanych.</dd>
        <dt>Alert</dt>
        <dd>Krótki tekst oznaczający coś w związku z tym członkiem.</dd>
        <dt>Informacje o członku</dt>
        <dd>Dowolne notatki, dostępne tylko dla zarządzających.</dd>
        <dt>Pola dodatkowe</dt>
        <dd>Definiowane na stronie ustawień w menu zarządzania osobami.</dd>
      </dl>
    </div>
  </div>
</div>'),
('Help_UserAccount','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">My account</span>
    </div>
    <div class="card-body">
      <p>Here you can update your personal information.</p>
      <dl class="mb-0">
        <dt>Email</dt>
        <dd>The address you use to sign in. It is locked if your account was imported.</dd>
        <dt>First name, last name</dt>
        <dd>Locked if your account was imported; ask a person manager to change them.</dd>
        <dt>Nickname</dt>
        <dd>Optional.</dd>
        <dt>Avatar</dt>
        <dd>Pick an emoji from the list, or paste your own (for instance from getemoji.com).</dd>
        <dt>Gravatar</dt>
        <dd>When ticked, your picture comes from gravatar.com, based on your email address.</dd>
      </dl>
    </div>
  </div>
</div>','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Mon compte</span>
    </div>
    <div class="card-body">
      <p>Vous pouvez mettre à jour ici vos informations personnelles.</p>
      <dl class="mb-0">
        <dt>Courriel</dt>
        <dd>L''adresse que vous utilisez pour vous connecter. Elle est verrouillée si votre compte a été importé.</dd>
        <dt>Prénom, nom</dt>
        <dd>Verrouillés si votre compte a été importé ; demandez à un gestionnaire des personnes de les modifier.</dd>
        <dt>Pseudo</dt>
        <dd>Facultatif.</dd>
        <dt>Avatar</dt>
        <dd>Choisissez un émoji dans la liste, ou collez le vôtre (par exemple depuis getemoji.com).</dd>
        <dt>Gravatar</dt>
        <dd>Si la case est cochée, votre image vient de gravatar.com, à partir de votre adresse de courriel.</dd>
      </dl>
    </div>
  </div>
</div>','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Moje konto</span>
    </div>
    <div class="card-body">
      <p>Tutaj możesz zaktualizować swoje dane osobowe.</p>
      <dl class="mb-0">
        <dt>E-mail</dt>
        <dd>Adres używany do logowania. Jest zablokowany, jeśli Twoje konto zostało zaimportowane.</dd>
        <dt>Imię, nazwisko</dt>
        <dd>Zablokowane, jeśli Twoje konto zostało zaimportowane; poproś osobę zarządzającą członkami o ich zmianę.</dd>
        <dt>Pseudonim</dt>
        <dd>Opcjonalny.</dd>
        <dt>Awatar</dt>
        <dd>Wybierz emoji z listy albo wklej własne (na przykład z getemoji.com).</dd>
        <dt>Gravatar</dt>
        <dd>Po zaznaczeniu Twój obraz pochodzi z gravatar.com, na podstawie Twojego adresu e-mail.</dd>
      </dl>
    </div>
  </div>
</div>');

-- Route help (shown on the routes page) of the two account edition help routes
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('designer.routes.help.PersonController.editHelp','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager (isPersonManager)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help of the page where a person manager edits a member''s account (/person/edit/@id).
        Reads the help text Help_PersonEdit.</p>
    </div>
  </div>
</div>','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager (isPersonManager)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle de la page où un gestionnaire des personnes modifie le compte d''un membre (/person/edit/@id).
        Lit le texte d''aide Help_PersonEdit.</p>
    </div>
  </div>
</div>','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">PersonManager (isPersonManager)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Opis</h6>
      <p class="mb-0">Pomoc kontekstowa strony, na której osoba zarządzająca członkami edytuje konto członka (/person/edit/@id).
        Odczytuje tekst pomocy Help_PersonEdit.</p>
    </div>
  </div>
</div>'),
('designer.routes.help.UserAccountController.help','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Route help</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">connected (isConnected)</span>
        <span class="badge bg-success">read-only</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Contextual help of the page where a member edits their own account (/user/account).
        Reads the help text Help_UserAccount.</p>
    </div>
  </div>
</div>','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Aide de la route</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">connecté (isConnected)</span>
        <span class="badge bg-success">lecture seule</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">Aide contextuelle de la page où un membre modifie son propre compte (/user/account).
        Lit le texte d''aide Help_UserAccount.</p>
    </div>
  </div>
</div>','<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">Pomoc trasy</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">HTML</span>
        <span class="badge bg-secondary">zalogowany (isConnected)</span>
        <span class="badge bg-success">tylko odczyt</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Opis</h6>
      <p class="mb-0">Pomoc kontekstowa strony, na której członek edytuje własne konto (/user/account).
        Odczytuje tekst pomocy Help_UserAccount.</p>
    </div>
  </div>
</div>');

SQL);

        return 113;
    }
}
