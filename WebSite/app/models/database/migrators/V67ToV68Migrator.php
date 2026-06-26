<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V67ToV68Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('Help_Communication',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">
      <i class="bi bi-envelope-paper me-2"></i>Contextual Help: Communication Manager
    </h1>
    <p class="lead">Send personalised e-mails to a targeted list of members in just a few clicks.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">What you can do</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-funnel-fill"></i>
              </div>
              <div>
                <strong>Filter &amp; select recipients</strong>
                <p class="text-muted small">Combine group, password, profile and map filters to target exactly the right members.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-pencil-square"></i>
              </div>
              <div>
                <strong>Rich-text editor</strong>
                <p class="text-muted small">Format your message with bold, colours, lists, alignment and more.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-send-check"></i>
              </div>
              <div>
                <strong>Test before sending</strong>
                <p class="text-muted small">Send a preview copy to yourself to verify layout and content.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-reply-all-fill"></i>
              </div>
              <div>
                <strong>Reply-to control</strong>
                <p class="text-muted small">Choose where member replies go: no-reply, your address, or the organisation contact.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3">
      <i class="bi bi-layout-sidebar me-2 text-success"></i>Left panel – Selecting recipients
    </h2>
    <p>Use the filters to narrow the list, then click <strong>Refresh</strong>. The counter in the top bar updates instantly.</p>

    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
      <div><strong>Group</strong><p class="text-muted small mb-0">Restrict the list to a specific group, or choose <em>All members</em>.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-key-fill"></i></div>
      <div><strong>Password</strong><p class="text-muted small mb-0">Filter by whether the member has already activated their account (created a password).</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-vcard-fill"></i></div>
      <div><strong>Profile</strong><p class="text-muted small mb-0">Include only members with a complete or empty public profile.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-geo-alt-fill"></i></div>
      <div><strong>On public map</strong><p class="text-muted small mb-0">Filter members who opted in or out of the public map.</p></div>
    </div>
    <div class="d-flex align-items-start mb-4">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-dash-fill"></i></div>
      <div><strong>Disabled accounts</strong><p class="text-muted small mb-0">Include or exclude members whose accounts have been deactivated.</p></div>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2">
      <i class="bi bi-lightbulb-fill fs-5 mt-1"></i>
      <div>Check individual names to pick specific recipients, or click <strong>Select all</strong> to target everyone returned by the current filters.</div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3">
      <i class="bi bi-ui-radios-grid me-2 text-danger"></i>Top bar – Key controls
    </h2>
    <p>The top bar controls apply to the whole communication before you send it.</p>

    <div class="d-flex align-items-start mb-2">
      <span class="badge bg-secondary me-3 mt-1 fs-6">N dest.</span>
      <div><strong>Recipient counter</strong><p class="text-muted small mb-0">Shows how many members are currently selected. Updates in real time.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-return-left"></i></div>
      <div><strong>Reply to</strong>
        <p class="text-muted small mb-0">
          Choose where member replies are sent:<br>
          <em>No reply</em> – replies are discarded &bull;
          <em>Sender</em> – replies go to your address &bull;
          <em>Contact address</em> – replies go to the organisation contact.
        </p>
      </div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-info text-dark rounded p-2 me-3"><i class="bi bi-send"></i></div>
      <div><strong>Blue test button</strong>
        <p class="text-muted small mb-0">Sends a copy <strong>only to yourself</strong> so you can check the e-mail before the real send. The button displays your e-mail address.</p>
      </div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-send-fill"></i></div>
      <div><strong>Send</strong><p class="text-muted small mb-0">Sends the e-mail to all selected recipients. A confirmation is required. This action cannot be undone.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-x-circle"></i></div>
      <div><strong>Cancel</strong><p class="text-muted small mb-0">Discards the draft and returns to the previous screen.</p></div>
    </div>
    <div class="d-flex align-items-start mb-4">
      <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-question-circle"></i></div>
      <div><strong>Help</strong><p class="text-muted small mb-0">Opens this contextual help page.</p></div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3"><i class="bi bi-star-fill me-2 text-warning"></i>Tips &amp; best practices</h2>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Always use the <strong>test button</strong> before the final send to verify your formatting and links.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Combine filters to reach a precise audience and avoid unnecessary mass mailings.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Choose <em>No reply</em> only for purely informational messages; otherwise prefer <em>Sender</em> or <em>Contact address</em>.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Keep the subject line short and clear to maximise open rates.</span>
    </div>
  </section>

  <footer class="border-top pt-3 text-muted small">
    <i class="bi bi-question-circle me-1"></i>Need more help? Contact your administrator.
  </footer>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">
      <i class="bi bi-envelope-paper me-2"></i>Aide Contextuelle : Gestionnaire de communication
    </h1>
    <p class="lead">Envoyez des courriels personnalisés à une liste ciblée de membres en quelques clics.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Ce que vous pouvez faire</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-funnel-fill"></i>
              </div>
              <div>
                <strong>Filtrer &amp; sélectionner les destinataires</strong>
                <p class="text-muted small">Combinez les filtres groupe, mot de passe, présentation et carte pour cibler précisément les bons membres.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-pencil-square"></i>
              </div>
              <div>
                <strong>Éditeur de texte enrichi</strong>
                <p class="text-muted small">Mettez en forme votre message avec du gras, des couleurs, des listes, des alignements et bien plus.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-send-check"></i>
              </div>
              <div>
                <strong>Tester avant d''envoyer</strong>
                <p class="text-muted small">Envoyez-vous une copie de prévisualisation pour vérifier la mise en forme et le contenu.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-reply-all-fill"></i>
              </div>
              <div>
                <strong>Contrôle de l''adresse de réponse</strong>
                <p class="text-muted small">Choisissez où arrivent les réponses des membres : pas de réponse, votre adresse ou le contact de l''organisation.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3">
      <i class="bi bi-layout-sidebar me-2 text-success"></i>Panneau gauche – Sélectionner les destinataires
    </h2>
    <p>Utilisez les filtres pour affiner la liste, puis cliquez sur <strong>Actualiser</strong>. Le compteur de la barre supérieure se met à jour instantanément.</p>

    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
      <div><strong>Groupe</strong><p class="text-muted small mb-0">Restreignez la liste à un groupe spécifique ou choisissez <em>Tous les membres</em>.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-key-fill"></i></div>
      <div><strong>Mot de passe</strong><p class="text-muted small mb-0">Filtrez selon que le membre a déjà activé son compte (mot de passe créé) ou non.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-vcard-fill"></i></div>
      <div><strong>Présentation</strong><p class="text-muted small mb-0">N''incluez que les membres dont la présentation publique est complète ou vide.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-geo-alt-fill"></i></div>
      <div><strong>Dans la carte publique</strong><p class="text-muted small mb-0">Filtrez les membres ayant choisi d''apparaître ou non sur la carte publique.</p></div>
    </div>
    <div class="d-flex align-items-start mb-4">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-dash-fill"></i></div>
      <div><strong>Comptes désactivés</strong><p class="text-muted small mb-0">Incluez ou excluez les membres dont le compte a été désactivé.</p></div>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2">
      <i class="bi bi-lightbulb-fill fs-5 mt-1"></i>
      <div>Cochez les noms individuellement pour choisir des destinataires précis, ou cliquez sur <strong>Tout sél.</strong> pour cibler tous les membres retournés par les filtres en cours.</div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3">
      <i class="bi bi-ui-radios-grid me-2 text-danger"></i>Barre supérieure – Contrôles principaux
    </h2>
    <p>Les contrôles de la barre supérieure s''appliquent à l''ensemble de la communication avant envoi.</p>

    <div class="d-flex align-items-start mb-2">
      <span class="badge bg-secondary me-3 mt-1 fs-6">N dest.</span>
      <div><strong>Compteur de destinataires</strong><p class="text-muted small mb-0">Affiche le nombre de membres actuellement sélectionnés. Se met à jour en temps réel.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-return-left"></i></div>
      <div><strong>Répondre à</strong>
        <p class="text-muted small mb-0">
          Définit où arrivent les réponses des membres :<br>
          <em>Pas de réponse</em> – les réponses sont ignorées &bull;
          <em>L''émetteur</em> – les réponses arrivent sur votre adresse &bull;
          <em>L''adresse contact</em> – les réponses arrivent sur le contact de l''organisation.
        </p>
      </div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-info text-dark rounded p-2 me-3"><i class="bi bi-send"></i></div>
      <div><strong>Bouton bleu de test</strong>
        <p class="text-muted small mb-0">Envoie une copie <strong>uniquement à vous-même</strong> pour vérifier le courriel avant l''envoi réel. Le bouton affiche votre adresse e-mail.</p>
      </div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-send-fill"></i></div>
      <div><strong>Envoyer</strong><p class="text-muted small mb-0">Envoie le courriel à tous les destinataires sélectionnés. Une confirmation est demandée. Cette action est irréversible.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-x-circle"></i></div>
      <div><strong>Annuler</strong><p class="text-muted small mb-0">Abandonne le brouillon et revient à l''écran précédent sans envoyer.</p></div>
    </div>
    <div class="d-flex align-items-start mb-4">
      <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-question-circle"></i></div>
      <div><strong>Aide</strong><p class="text-muted small mb-0">Ouvre cette page d''aide contextuelle.</p></div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3"><i class="bi bi-star-fill me-2 text-warning"></i>Conseils et bonnes pratiques</h2>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Utilisez toujours le <strong>bouton test</strong> avant l''envoi définitif pour vérifier la mise en forme et les liens.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Combinez les filtres pour cibler précisément votre audience et éviter les envois en masse inutiles.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Choisissez <em>Pas de réponse</em> uniquement pour des messages purement informatifs ; préférez sinon <em>L''émetteur</em> ou <em>L''adresse contact</em>.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Rédigez un objet court et clair pour maximiser le taux d''ouverture.</span>
    </div>
  </section>

  <footer class="border-top pt-3 text-muted small">
    <i class="bi bi-question-circle me-1"></i>Besoin d''aide supplémentaire ? Contactez votre administrateur.
  </footer>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">
      <i class="bi bi-envelope-paper me-2"></i>Pomoc kontekstowa: Menedżer komunikacji
    </h1>
    <p class="lead">Wysyłaj spersonalizowane e-maile do wybranej listy członków w kilku kliknięciach.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Co możesz zrobić</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-funnel-fill"></i>
              </div>
              <div>
                <strong>Filtruj i wybieraj odbiorców</strong>
                <p class="text-muted small">Łącz filtry grupy, hasła, profilu i mapy, aby precyzyjnie dotrzeć do właściwych członków.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-pencil-square"></i>
              </div>
              <div>
                <strong>Edytor tekstu sformatowanego</strong>
                <p class="text-muted small">Formatuj wiadomość pogrubieniem, kolorami, listami, wyrównaniem i wieloma innymi opcjami.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-send-check"></i>
              </div>
              <div>
                <strong>Testuj przed wysłaniem</strong>
                <p class="text-muted small">Wyślij sobie kopię podglądu, aby sprawdzić układ i treść wiadomości.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3">
                <i class="bi bi-reply-all-fill"></i>
              </div>
              <div>
                <strong>Kontrola adresu odpowiedzi</strong>
                <p class="text-muted small">Wybierz, gdzie trafiają odpowiedzi członków: brak odpowiedzi, Twój adres lub kontakt organizacji.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3">
      <i class="bi bi-layout-sidebar me-2 text-success"></i>Panel lewy – Wybór odbiorców
    </h2>
    <p>Użyj filtrów, aby zawęzić listę, a następnie kliknij <strong>Odśwież</strong>. Licznik na pasku górnym aktualizuje się natychmiast.</p>

    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
      <div><strong>Grupa</strong><p class="text-muted small mb-0">Ogranicz listę do określonej grupy lub wybierz <em>Wszyscy członkowie</em>.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-key-fill"></i></div>
      <div><strong>Hasło</strong><p class="text-muted small mb-0">Filtruj według tego, czy członek aktywował już konto (utworzone hasło).</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-vcard-fill"></i></div>
      <div><strong>Prezentacja</strong><p class="text-muted small mb-0">Uwzględnij tylko członków z kompletnym lub pustym profilem publicznym.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-geo-alt-fill"></i></div>
      <div><strong>Na mapie publicznej</strong><p class="text-muted small mb-0">Filtruj członków, którzy zdecydowali się pojawiać lub nie na mapie publicznej.</p></div>
    </div>
    <div class="d-flex align-items-start mb-4">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-dash-fill"></i></div>
      <div><strong>Dezaktywowane konta</strong><p class="text-muted small mb-0">Uwzględnij lub wyklucz członków z dezaktywowanymi kontami.</p></div>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2">
      <i class="bi bi-lightbulb-fill fs-5 mt-1"></i>
      <div>Zaznacz poszczególne nazwiska, aby wybrać konkretnych odbiorców, lub kliknij <strong>Zaznacz wszystkich</strong>, aby objąć wszystkich spełniających aktualne kryteria filtrów.</div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3">
      <i class="bi bi-ui-radios-grid me-2 text-danger"></i>Pasek górny – Główne kontrolki
    </h2>
    <p>Kontrolki paska górnego dotyczą całej wiadomości przed jej wysłaniem.</p>

    <div class="d-flex align-items-start mb-2">
      <span class="badge bg-secondary me-3 mt-1 fs-6">N odb.</span>
      <div><strong>Licznik odbiorców</strong><p class="text-muted small mb-0">Pokazuje liczbę aktualnie zaznaczonych członków. Aktualizuje się na bieżąco.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-return-left"></i></div>
      <div><strong>Odpowiedz do</strong>
        <p class="text-muted small mb-0">
          Określa, gdzie trafiają odpowiedzi członków:<br>
          <em>Brak odpowiedzi</em> – odpowiedzi są odrzucane &bull;
          <em>Nadawca</em> – odpowiedzi trafiają na Twój adres &bull;
          <em>Adres kontaktowy</em> – odpowiedzi trafiają do kontaktu organizacji.
        </p>
      </div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-info text-dark rounded p-2 me-3"><i class="bi bi-send"></i></div>
      <div><strong>Niebieski przycisk testowy</strong>
        <p class="text-muted small mb-0">Wysyła kopię <strong>tylko do Ciebie</strong>, abyś mógł sprawdzić wiadomość przed właściwym wysłaniem. Etykieta przycisku wyświetla Twój adres e-mail.</p>
      </div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-send-fill"></i></div>
      <div><strong>Wyślij</strong><p class="text-muted small mb-0">Wysyła wiadomość do wszystkich zaznaczonych odbiorców. Wymagane jest potwierdzenie. Tej akcji nie można cofnąć.</p></div>
    </div>
    <div class="d-flex align-items-start mb-2">
      <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-x-circle"></i></div>
      <div><strong>Anuluj</strong><p class="text-muted small mb-0">Odrzuca wersję roboczą i wraca do poprzedniego ekranu bez wysyłania.</p></div>
    </div>
    <div class="d-flex align-items-start mb-4">
      <div class="bg-warning text-dark rounded p-2 me-3"><i class="bi bi-question-circle"></i></div>
      <div><strong>Pomoc</strong><p class="text-muted small mb-0">Otwiera tę stronę pomocy kontekstowej.</p></div>
    </div>
  </section>
  <section class="mb-5">
    <h2 class="h4 fw-semibold mb-3"><i class="bi bi-star-fill me-2 text-warning"></i>Wskazówki i dobre praktyki</h2>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Zawsze używaj <strong>przycisku testowego</strong> przed ostatecznym wysłaniem, aby sprawdzić formatowanie i linki.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Łącz filtry, aby precyzyjnie określić grupę docelową i unikać niepotrzebnych masowych wysyłek.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Wybieraj <em>Brak odpowiedzi</em> tylko dla wiadomości czysto informacyjnych; w pozostałych przypadkach preferuj <em>Nadawca</em> lub <em>Adres kontaktowy</em>.</span>
    </div>
    <div class="d-flex align-items-start mb-2">
      <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
      <span>Pisz krótkie i klarowne tematy wiadomości, aby zmaksymalizować wskaźnik otwarć.</span>
    </div>
  </section>

  <footer class="border-top pt-3 text-muted small">
    <i class="bi bi-question-circle me-1"></i>Potrzebujesz więcej pomocy? Skontaktuj się z administratorem.
  </footer>
</div>'

),
('communication.index.test', 'Send a test to myself', "M'envoyer un test", 'Wyślij test do mnie');
SQL);

        $pdo->exec("DELETE FROM Languages WHERE Name IN (
            'communication.index.cancel'
        )");

        return 68;
    }
}
