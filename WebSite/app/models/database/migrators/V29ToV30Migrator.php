<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V29ToV30Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('Help_Observers',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Observers</h1>
    <p class="lead">Track visitor activity, analyse traffic sources and trends.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Available tools</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bar-chart-line-fill"></i></div>
              <div><strong>Visitor statistics</strong>
              <p class="text-muted small">Combined chart of <em>Unique visitors</em> and <em>Page views</em> for the selected period (day / week / month / year), with a detailed table below.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-pie-chart-fill"></i></div>
              <div><strong>Visitor summary</strong>
              <p class="text-muted small">Pie charts showing distribution by <em>Operating system</em>, <em>Browser</em>, <em>Screen resolution</em> and <em>Hardware</em>.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-cloud-arrow-up-fill"></i></div>
              <div><strong>Referrer sites</strong>
              <p class="text-muted small">Visit origins: <em>direct</em>, <em>internal</em>, <em>external</em>, then detail of source URLs. Navigate by Day / Week / Month / Year.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-trophy-fill"></i></div>
              <div><strong>Top visited pages</strong>
              <p class="text-muted small">Ranking of the most visited URIs with visit count and percentage. Period selectable via drop-down list.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Dynamic cross-table</strong>
              <p class="text-muted small">Crosses <em>URIs</em> visited by <em>User</em>. Filterable by Period, URI, Email and Group. Shows total per row. 👉 <em>Use the <strong>Hide</strong> button to collapse the table.</em></p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Last visits</strong>
              <p class="text-muted small">Lists recently connected members: last page visited, timestamp, OS and browser. Also shows the number of active visitors in real time.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-text-fill"></i></div>
              <div><strong>Logs</strong>
              <p class="text-muted small">Raw log of all requests: Date, Type, Browser, OS, Page visited, Visitor, HTTP code, Message. Combinable multi-filters, paginated results.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-fill"></i></div>
              <div><strong>Member alerts</strong>
              <p class="text-muted small">Summary of each member''s notification preferences: alert on new <em>Event</em> and/or new <em>Article</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <p class="text-muted">Features common to most pages:</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Time navigation:</strong> The <kbd>Day</kbd> <kbd>Week</kbd> <kbd>Month</kbd> <kbd>Year</kbd> buttons and the <kbd>&lt;</kbd> <kbd>&gt;</kbd> arrows let you navigate through time on all statistics pages.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-funnel-fill fs-3 text-primary me-3"></i>
        <span><strong>Filters:</strong> The <em>Cross-table</em> and <em>Logs</em> pages offer combinable filters. Click <strong>Filter</strong> to apply and <strong>Reset</strong> to clear.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access to the <em>Observers</em> area is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Observateurs</h1>
    <p class="lead">Suivez l''activité des visiteurs, analysez les sources de trafic et les tendances.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les outils disponibles</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bar-chart-line-fill"></i></div>
              <div><strong>Statistiques des visiteurs</strong>
              <p class="text-muted small">Graphique combiné <em>Visiteurs uniques</em> et <em>Pages vues</em> sur la période choisie (jour / semaine / mois / année). Tableau détaillé en dessous du graphique.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-pie-chart-fill"></i></div>
              <div><strong>Synthèse des visiteurs</strong>
              <p class="text-muted small">Camemberts de répartition par <em>Système d''exploitation</em>, <em>Navigateur</em>, <em>Résolution d''écran</em> et <em>Matériel</em>.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-cloud-arrow-up-fill"></i></div>
              <div><strong>Sites référents</strong>
              <p class="text-muted small">Origine des visites : <em>direct</em>, <em>interne</em>, <em>externe</em>, puis détail des URLs sources. Navigation par Jour / Semaine / Mois / Année.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-trophy-fill"></i></div>
              <div><strong>Top des pages visitées</strong>
              <p class="text-muted small">Classement des URI les plus consultées avec le nombre de visites et le pourcentage. Période sélectionnable via la liste déroulante.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Tableau croisé dynamique</strong>
              <p class="text-muted small">Croise les <em>URI</em> visitées par <em>Utilisateur</em>. Filtrable par Période, URI, Email et Groupe. Affiche le total par ligne. 👉 <em>Bouton <strong>Masquer</strong> pour replier le tableau.</em></p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Dernières visites</strong>
              <p class="text-muted small">Liste les membres connectés récemment : dernière page consultée, horodatage, OS et navigateur. Affiche aussi le nombre de visiteurs actifs en temps réel.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-text-fill"></i></div>
              <div><strong>Logs</strong>
              <p class="text-muted small">Journal brut de toutes les requêtes : Date, Type, Navigateur, OS, Page visitée, Visiteur, Code HTTP, Message. Multi-filtres combinables, résultats paginés.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-fill"></i></div>
              <div><strong>Alertes demandées par les membres</strong>
              <p class="text-muted small">Récapitulatif des préférences de notification de chaque membre : alerte sur nouvel <em>Événement</em> et/ou nouvel <em>Article</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <p class="text-muted">Fonctionnalités communes à la plupart des pages :</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Navigation temporelle :</strong> Les boutons <kbd>Jour</kbd> <kbd>Semaine</kbd> <kbd>Mois</kbd> <kbd>Année</kbd> et les flèches <kbd>&lt;</kbd> <kbd>&gt;</kbd> permettent de naviguer dans le temps sur toutes les pages de statistiques.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-funnel-fill fs-3 text-primary me-3"></i>
        <span><strong>Filtres :</strong> Les pages <em>Tableau croisé</em> et <em>Logs</em> proposent des filtres combinables. Cliquez sur <strong>Filtrer</strong> pour appliquer et <strong>Réinitialiser</strong> pour effacer.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès à la zone <em>Observateurs</em> est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Obserwatorzy</h1>
    <p class="lead">Śledź aktywność odwiedzających, analizuj źródła ruchu i trendy.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Dostępne narzędzia</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bar-chart-line-fill"></i></div>
              <div><strong>Statystyki odwiedzających</strong>
              <p class="text-muted small">Łączny wykres <em>Unikalnych odwiedzających</em> i <em>Wyświetleń stron</em> za wybrany okres (dzień / tydzień / miesiąc / rok). Szczegółowa tabela pod wykresem.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-pie-chart-fill"></i></div>
              <div><strong>Podsumowanie odwiedzających</strong>
              <p class="text-muted small">Wykresy kołowe rozkładu według <em>Systemu operacyjnego</em>, <em>Przeglądarki</em>, <em>Rozdzielczości ekranu</em> i <em>Sprzętu</em>.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-cloud-arrow-up-fill"></i></div>
              <div><strong>Witryny odsyłające</strong>
              <p class="text-muted small">Źródła wizyt: <em>bezpośrednie</em>, <em>wewnętrzne</em>, <em>zewnętrzne</em>, następnie szczegóły źródłowych URL. Nawigacja Dzień / Tydzień / Miesiąc / Rok.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-trophy-fill"></i></div>
              <div><strong>Najpopularniejsze strony</strong>
              <p class="text-muted small">Ranking najczęściej odwiedzanych URI z liczbą wizyt i procentem. Okres wybierany z listy rozwijanej.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Dynamiczna tabela krzyżowa</strong>
              <p class="text-muted small">Krzyżuje <em>URI</em> odwiedzone przez <em>Użytkownika</em>. Filtrowanie według Okresu, URI, Email i Grupy. Wyświetla sumę w wierszu. 👉 <em>Przycisk <strong>Ukryj</strong> zwija tabelę.</em></p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Ostatnie wizyty</strong>
              <p class="text-muted small">Lista ostatnio zalogowanych członków: ostatnia odwiedzona strona, znacznik czasu, OS i przeglądarka. Pokazuje też liczbę aktywnych odwiedzających w czasie rzeczywistym.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-text-fill"></i></div>
              <div><strong>Logi</strong>
              <p class="text-muted small">Surowy dziennik wszystkich żądań: Data, Typ, Przeglądarka, OS, Odwiedzona strona, Odwiedzający, Kod HTTP, Wiadomość. Łączne filtry, wyniki paginowane.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-fill"></i></div>
              <div><strong>Alerty zamówione przez członków</strong>
              <p class="text-muted small">Podsumowanie preferencji powiadomień każdego członka: alert o nowym <em>Wydarzeniu</em> i/lub nowym <em>Artykule</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <p class="text-muted">Funkcje wspólne dla większości stron:</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Nawigacja czasowa:</strong> Przyciski <kbd>Dzień</kbd> <kbd>Tydzień</kbd> <kbd>Miesiąc</kbd> <kbd>Rok</kbd> oraz strzałki <kbd>&lt;</kbd> <kbd>&gt;</kbd> umożliwiają nawigację w czasie na wszystkich stronach statystyk.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-funnel-fill fs-3 text-primary me-3"></i>
        <span><strong>Filtry:</strong> Strony <em>Tabela krzyżowa</em> i <em>Logi</em> oferują łączne filtry. Kliknij <strong>Filtruj</strong> aby zastosować i <strong>Resetuj</strong> aby wyczyścić.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp do strefy <em>Obserwatorzy</em> jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>'),

('Help_Referents',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Referrer Sites</h1>
    <p class="lead">Find out where your visitors come from before arriving on the site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>direct</strong>
              <p class="text-muted small">The visitor typed the URL directly or used a bookmark. No referring site.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-house-fill"></i></div>
              <div><strong>internal</strong>
              <p class="text-muted small">Navigation between pages within the site itself.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-arrow-in-right"></i></div>
              <div><strong>external</strong>
              <p class="text-muted small">The visitor arrived via a link on another website (social network, search engine, partner site…).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Source URLs</strong>
              <p class="text-muted small">Below the three summary lines, the exact URLs of external referrers are listed with their visit count.
              <span class="d-block mt-1 text-dark">👉 <em>A high count on a specific URL indicates an active inbound link worth monitoring.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Time navigation:</strong> Switch between <kbd>Day</kbd> <kbd>Week</kbd> <kbd>Month</kbd> <kbd>Year</kbd> and use the <kbd>&lt;</kbd> <kbd>&gt;</kbd> arrows to move through time.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Sites référents</h1>
    <p class="lead">Identifiez d''où viennent vos visiteurs avant d''arriver sur le site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>direct</strong>
              <p class="text-muted small">Le visiteur a tapé l''URL directement ou utilisé un favori. Aucun site référent.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-house-fill"></i></div>
              <div><strong>interne</strong>
              <p class="text-muted small">Navigation entre les pages du site lui-même.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-arrow-in-right"></i></div>
              <div><strong>externe</strong>
              <p class="text-muted small">Le visiteur est arrivé via un lien sur un autre site (réseau social, moteur de recherche, site partenaire…).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>URLs sources</strong>
              <p class="text-muted small">Sous les trois lignes de synthèse, les URLs exactes des référents externes sont listées avec leur nombre de visites.
              <span class="d-block mt-1 text-dark">👉 <em>Un compteur élevé sur une URL précise signale un lien entrant actif à surveiller.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Navigation temporelle :</strong> Basculez entre <kbd>Jour</kbd> <kbd>Semaine</kbd> <kbd>Mois</kbd> <kbd>Année</kbd> et utilisez les flèches <kbd>&lt;</kbd> <kbd>&gt;</kbd> pour naviguer dans le temps.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Witryny odsyłające</h1>
    <p class="lead">Dowiedz się, skąd przychodzą Twoi odwiedzający przed dotarciem na stronę.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>bezpośrednie</strong>
              <p class="text-muted small">Odwiedzający wpisał URL bezpośrednio lub użył zakładki. Brak witryny odsyłającej.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-house-fill"></i></div>
              <div><strong>wewnętrzne</strong>
              <p class="text-muted small">Nawigacja między stronami w obrębie samej witryny.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-arrow-in-right"></i></div>
              <div><strong>zewnętrzne</strong>
              <p class="text-muted small">Odwiedzający przyszedł przez link na innej stronie (sieć społecznościowa, wyszukiwarka, strona partnerska…).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Źródłowe URL</strong>
              <p class="text-muted small">Pod trzema wierszami podsumowania wyświetlane są dokładne URL zewnętrznych odsyłaczy wraz z liczbą wizyt.
              <span class="d-block mt-1 text-dark">👉 <em>Wysoki licznik przy konkretnym URL wskazuje aktywny link przychodzący warty monitorowania.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Nawigacja czasowa:</strong> Przełączaj między <kbd>Dzień</kbd> <kbd>Tydzień</kbd> <kbd>Miesiąc</kbd> <kbd>Rok</kbd> i używaj strzałek <kbd>&lt;</kbd> <kbd>&gt;</kbd> do poruszania się w czasie.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>'),

('Help_TopPages',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Top Visited Pages</h1>
    <p class="lead">Identify which pages attract the most traffic on your site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hash"></i></div>
              <div><strong>Rank</strong>
              <p class="text-muted small">Pages are sorted from most to least visited for the selected period.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>URI</strong>
              <p class="text-muted small">The path of the visited page (e.g. <code>/nextEvents</code>, <code>/user/sign_in</code>).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Visits &amp; Percentage</strong>
              <p class="text-muted small">Number of hits for the period and its share of total traffic, illustrated by a progress bar.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
              <div><strong>Period</strong>
              <p class="text-muted small">Select the analysis window via the drop-down list (e.g. last 7 days, last 30 days…).</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-bar-chart-fill fs-3 text-primary me-3"></i>
        <span><strong>Usage tip:</strong> A URI with a very high percentage indicates a strategic entry point — check that its content is up to date.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Top des pages visitées</h1>
    <p class="lead">Identifiez les pages qui attirent le plus de trafic sur votre site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hash"></i></div>
              <div><strong>Rang</strong>
              <p class="text-muted small">Les pages sont triées de la plus visitée à la moins visitée pour la période sélectionnée.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>URI</strong>
              <p class="text-muted small">Le chemin de la page visitée (ex. <code>/nextEvents</code>, <code>/user/sign_in</code>).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Visites &amp; Pourcentage</strong>
              <p class="text-muted small">Nombre de passages sur la période et sa part du trafic total, illustrée par une barre de progression.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
              <div><strong>Période</strong>
              <p class="text-muted small">Sélectionnez la fenêtre d''analyse via la liste déroulante (ex. 7 derniers jours, 30 derniers jours…).</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-bar-chart-fill fs-3 text-primary me-3"></i>
        <span><strong>Conseil :</strong> Une URI avec un très fort pourcentage représente un point d''entrée stratégique — vérifiez que son contenu est à jour.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Najpopularniejsze strony</h1>
    <p class="lead">Zidentyfikuj strony przyciągające największy ruch.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hash"></i></div>
              <div><strong>Ranga</strong>
              <p class="text-muted small">Strony posortowane od najczęściej do najrzadziej odwiedzanych w wybranym okresie.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>URI</strong>
              <p class="text-muted small">Ścieżka odwiedzonej strony (np. <code>/nextEvents</code>, <code>/user/sign_in</code>).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Wizyty &amp; Procent</strong>
              <p class="text-muted small">Liczba wejść w danym okresie i jej udział w całkowitym ruchu, zilustrowany paskiem postępu.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
              <div><strong>Okres</strong>
              <p class="text-muted small">Wybierz okno analizy z listy rozwijanej (np. ostatnie 7 dni, ostatnie 30 dni…).</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-bar-chart-fill fs-3 text-primary me-3"></i>
        <span><strong>Wskazówka:</strong> URI z bardzo wysokim procentem to strategiczny punkt wejścia — sprawdź, czy jej treść jest aktualna.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>'),

('Help_Crosstab',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Dynamic Cross-Table</h1>
    <p class="lead">Cross-reference which pages each user has visited over a given period.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">How it works</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>URI × User matrix</strong>
              <p class="text-muted small">Rows = visited URIs. Columns = users (email). Each cell shows the number of visits by that user on that page. The <strong>Total</strong> column sums all users per URI.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-slash-fill"></i></div>
              <div><strong>Hide / Show</strong>
              <p class="text-muted small">The <strong>Hide</strong> button in the table header collapses the matrix to save screen space. Click again to expand.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Filters</strong>
              <p class="text-muted small">Combine four criteria to narrow results:
                <span class="d-block mt-1">📅 <strong>Period</strong> — analysis window</span>
                <span class="d-block">🔗 <strong>URI</strong> — filter on a specific page</span>
                <span class="d-block">📧 <strong>Email</strong> — filter on a specific user</span>
                <span class="d-block">👥 <strong>Group</strong> — restrict to a member group</span>
                <span class="d-block mt-1 text-dark">👉 <em>Click <strong>Filter</strong> to apply, <strong>Reset</strong> to clear.</em></span>
              </p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-person-lines-fill fs-3 text-primary me-3"></i>
        <span><strong>Usage tip:</strong> Filter by Email to see the complete browsing path of a specific member, or by Group to compare the habits of a team.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Tableau croisé dynamique</h1>
    <p class="lead">Croisez les pages visitées avec les utilisateurs sur une période donnée.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Fonctionnement</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Matrice URI × Utilisateur</strong>
              <p class="text-muted small">Lignes = URI visitées. Colonnes = utilisateurs (email). Chaque cellule indique le nombre de passages de cet utilisateur sur cette page. La colonne <strong>Total</strong> agrège tous les utilisateurs par URI.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-slash-fill"></i></div>
              <div><strong>Masquer / Afficher</strong>
              <p class="text-muted small">Le bouton <strong>Masquer</strong> en en-tête du tableau replie la matrice pour gagner de l''espace. Cliquez à nouveau pour la déplier.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Filtres</strong>
              <p class="text-muted small">Combinez quatre critères pour affiner les résultats :
                <span class="d-block mt-1">📅 <strong>Période</strong> — fenêtre d''analyse</span>
                <span class="d-block">🔗 <strong>URI</strong> — filtrer sur une page précise</span>
                <span class="d-block">📧 <strong>Email</strong> — filtrer sur un utilisateur précis</span>
                <span class="d-block">👥 <strong>Groupe</strong> — restreindre à un groupe de membres</span>
                <span class="d-block mt-1 text-dark">👉 <em>Cliquez sur <strong>Filtrer</strong> pour appliquer, <strong>Réinitialiser</strong> pour effacer.</em></span>
              </p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-person-lines-fill fs-3 text-primary me-3"></i>
        <span><strong>Conseil :</strong> Filtrez par Email pour voir le parcours complet d''un membre, ou par Groupe pour comparer les habitudes d''une équipe.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Dynamiczna tabela krzyżowa</h1>
    <p class="lead">Skrzyżuj odwiedzone strony z użytkownikami w danym okresie.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Jak to działa</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Macierz URI × Użytkownik</strong>
              <p class="text-muted small">Wiersze = odwiedzone URI. Kolumny = użytkownicy (email). Każda komórka pokazuje liczbę wejść danego użytkownika na daną stronę. Kolumna <strong>Łącznie</strong> sumuje wszystkich użytkowników dla URI.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-slash-fill"></i></div>
              <div><strong>Ukryj / Pokaż</strong>
              <p class="text-muted small">Przycisk <strong>Ukryj</strong> w nagłówku tabeli zwija macierz, aby zaoszczędzić miejsce. Kliknij ponownie, aby rozwinąć.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Filtry</strong>
              <p class="text-muted small">Łącz cztery kryteria, aby zawęzić wyniki:
                <span class="d-block mt-1">📅 <strong>Okres</strong> — okno analizy</span>
                <span class="d-block">🔗 <strong>URI</strong> — filtruj na konkretnej stronie</span>
                <span class="d-block">📧 <strong>Email</strong> — filtruj na konkretnym użytkowniku</span>
                <span class="d-block">👥 <strong>Grupa</strong> — ogranicz do grupy członków</span>
                <span class="d-block mt-1 text-dark">👉 <em>Kliknij <strong>Filtruj</strong> aby zastosować, <strong>Resetuj</strong> aby wyczyścić.</em></span>
              </p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-person-lines-fill fs-3 text-primary me-3"></i>
        <span><strong>Wskazówka:</strong> Filtruj po Email, aby zobaczyć pełną ścieżkę przeglądania członka, lub po Grupie, aby porównać nawyki zespołu.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>'),

('Help_VisitorGraf',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Visitor Statistics</h1>
    <p class="lead">Visualise visitor trends and page views over time.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the chart</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
              <div><strong>Unique visitors</strong>
              <p class="text-muted small">Line chart (left axis) — number of distinct visitors per period unit. A visitor counted once per session regardless of pages viewed.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
              <div><strong>Page views</strong>
              <p class="text-muted small">Bar chart (right axis) — total number of pages loaded. A high Pages/Visitor ratio indicates strong engagement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-range"></i></div>
              <div><strong>Time navigation</strong>
              <p class="text-muted small">Switch between <kbd>By day</kbd> <kbd>By week</kbd> <kbd>By month</kbd> <kbd>By year</kbd>. Use <kbd>&lt;&lt;</kbd> <kbd>&lt;</kbd> <kbd>Today</kbd> <kbd>&gt;</kbd> <kbd>&gt;&gt;</kbd> to move through time.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Detail table</strong>
              <p class="text-muted small">Below the chart, a table lists each period unit with Unique visitors, Page views and the Pages/Visitor ratio.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-graph-up-arrow fs-3 text-primary me-3"></i>
        <span><strong>Usage tip:</strong> A spike in page views without a corresponding rise in unique visitors suggests an existing member is browsing deeply — check the cross-table for details.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Statistiques des visiteurs</h1>
    <p class="lead">Visualisez les tendances de fréquentation et les pages vues dans le temps.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le graphique</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
              <div><strong>Visiteurs uniques</strong>
              <p class="text-muted small">Courbe (axe gauche) — nombre de visiteurs distincts par unité de période. Un visiteur est compté une seule fois par session quelle que soit la navigation.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
              <div><strong>Pages vues</strong>
              <p class="text-muted small">Barres (axe droit) — nombre total de pages chargées. Un ratio Pages/Visiteur élevé indique un fort engagement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-range"></i></div>
              <div><strong>Navigation temporelle</strong>
              <p class="text-muted small">Basculez entre <kbd>Par jour</kbd> <kbd>Par semaine</kbd> <kbd>Par mois</kbd> <kbd>Par année</kbd>. Utilisez <kbd>&lt;&lt;</kbd> <kbd>&lt;</kbd> <kbd>Aujourd''hui</kbd> <kbd>&gt;</kbd> <kbd>&gt;&gt;</kbd> pour naviguer.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Tableau détaillé</strong>
              <p class="text-muted small">Sous le graphique, un tableau liste chaque unité de période avec Visiteurs uniques, Pages vues et le ratio Pages/Visiteur.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-graph-up-arrow fs-3 text-primary me-3"></i>
        <span><strong>Conseil :</strong> Un pic de pages vues sans hausse des visiteurs uniques suggère qu''un membre existant navigue en profondeur — consultez le tableau croisé pour plus de détails.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Statystyki odwiedzających</h1>
    <p class="lead">Wizualizuj trendy odwiedzin i wyświetlenia stron w czasie.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie wykresu</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
              <div><strong>Unikalni odwiedzający</strong>
              <p class="text-muted small">Linia (lewa oś) — liczba odrębnych odwiedzających na jednostkę okresu. Odwiedzający liczony raz na sesję niezależnie od przeglądanych stron.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
              <div><strong>Wyświetlenia stron</strong>
              <p class="text-muted small">Słupki (prawa oś) — całkowita liczba załadowanych stron. Wysoki wskaźnik Strony/Odwiedzający wskazuje na duże zaangażowanie.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-range"></i></div>
              <div><strong>Nawigacja czasowa</strong>
              <p class="text-muted small">Przełączaj między <kbd>Dzień</kbd> <kbd>Tydzień</kbd> <kbd>Miesiąc</kbd> <kbd>Rok</kbd>. Używaj <kbd>&lt;&lt;</kbd> <kbd>&lt;</kbd> <kbd>Dzisiaj</kbd> <kbd>&gt;</kbd> <kbd>&gt;&gt;</kbd> do nawigacji.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Szczegółowa tabela</strong>
              <p class="text-muted small">Pod wykresem tabela zawiera każdą jednostkę okresu z Unikalnymi odwiedzającymi, Wyświetleniami stron i wskaźnikiem Strony/Odwiedzający.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-graph-up-arrow fs-3 text-primary me-3"></i>
        <span><strong>Wskazówka:</strong> Skok wyświetleń stron bez wzrostu unikalnych odwiedzających sugeruje, że istniejący członek intensywnie przegląda — sprawdź tabelę krzyżową po szczegóły.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>'),

('Help_Analytics',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Visitor Summary</h1>
    <p class="lead">Understand the technical profile of your visitors at a glance.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">The four charts</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-laptop-fill"></i></div>
              <div><strong>Operating systems</strong>
              <p class="text-muted small">Pie chart of OS used: Linux, Windows, iOS, Android… Helps prioritise compatibility testing.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-browser-chrome"></i></div>
              <div><strong>Browsers</strong>
              <p class="text-muted small">Distribution of browsers: Firefox, Chrome, Safari, Mobile Safari… Useful for ensuring cross-browser compatibility.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-aspect-ratio-fill"></i></div>
              <div><strong>Screen resolutions</strong>
              <p class="text-muted small">Breakdown of screen sizes used to access the site. Indicates whether mobile or desktop visitors predominate.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-phone-fill"></i></div>
              <div><strong>Hardware</strong>
              <p class="text-muted small">Type of device: desktop, mobile, tablet. Guides responsive design priorities.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Time navigation:</strong> Switch between <kbd>Day</kbd> <kbd>Week</kbd> <kbd>Month</kbd> <kbd>Year</kbd> and use the arrows to select the desired period. All four charts update simultaneously.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Synthèse des visiteurs</h1>
    <p class="lead">Comprenez le profil technique de vos visiteurs en un coup d''œil.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les quatre graphiques</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-laptop-fill"></i></div>
              <div><strong>Systèmes d''exploitation</strong>
              <p class="text-muted small">Camembert des OS utilisés : Linux, Windows, iOS, Android… Aide à prioriser les tests de compatibilité.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-browser-chrome"></i></div>
              <div><strong>Navigateurs</strong>
              <p class="text-muted small">Répartition des navigateurs : Firefox, Chrome, Safari, Mobile Safari… Utile pour vérifier la compatibilité cross-browser.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-aspect-ratio-fill"></i></div>
              <div><strong>Résolutions d''écran</strong>
              <p class="text-muted small">Répartition des tailles d''écran utilisées pour accéder au site. Indique si les visiteurs mobile ou bureau prédominent.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-phone-fill"></i></div>
              <div><strong>Matériel</strong>
              <p class="text-muted small">Type d''appareil : ordinateur, mobile, tablette. Guide les priorités de design responsive.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Navigation temporelle :</strong> Basculez entre <kbd>Jour</kbd> <kbd>Semaine</kbd> <kbd>Mois</kbd> <kbd>Année</kbd> et utilisez les flèches pour sélectionner la période souhaitée. Les quatre graphiques se mettent à jour simultanément.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Podsumowanie odwiedzających</h1>
    <p class="lead">Zrozum profil techniczny swoich odwiedzających jednym spojrzeniem.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Cztery wykresy</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-laptop-fill"></i></div>
              <div><strong>Systemy operacyjne</strong>
              <p class="text-muted small">Wykres kołowy używanych OS: Linux, Windows, iOS, Android… Pomaga ustalić priorytety testów kompatybilności.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-browser-chrome"></i></div>
              <div><strong>Przeglądarki</strong>
              <p class="text-muted small">Rozkład przeglądarek: Firefox, Chrome, Safari, Mobile Safari… Przydatne do sprawdzania kompatybilności cross-browser.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-aspect-ratio-fill"></i></div>
              <div><strong>Rozdzielczości ekranu</strong>
              <p class="text-muted small">Rozkład rozmiarów ekranów używanych do odwiedzania strony. Wskazuje, czy przeważają odwiedzający mobilni czy stacjonarni.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-phone-fill"></i></div>
              <div><strong>Sprzęt</strong>
              <p class="text-muted small">Typ urządzenia: komputer, telefon, tablet. Kieruje priorytetami projektowania responsywnego.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Nawigacja czasowa:</strong> Przełączaj między <kbd>Dzień</kbd> <kbd>Tydzień</kbd> <kbd>Miesiąc</kbd> <kbd>Rok</kbd> i używaj strzałek do wyboru okresu. Wszystkie cztery wykresy aktualizują się jednocześnie.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>'),

('Help_LastVisits',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Last Visits</h1>
    <p class="lead">See who is on the site right now and who visited recently.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Member &amp; Email</strong>
              <p class="text-muted small">Full name and email address of the member. Sorted by most recent activity first.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Last page</strong>
              <p class="text-muted small">The last URI visited by the member, highlighted in pink. Click to navigate to that page.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>Last activity</strong>
              <p class="text-muted small">Relative time (e.g. <em>Just now</em>, <em>23 hours</em>, <em>7 days</em>) and exact timestamp of the last recorded action.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-display-fill"></i></div>
              <div><strong>OS &amp; Browser</strong>
              <p class="text-muted small">Operating system and browser used during the last session (e.g. Linux / Firefox 140, Android / Chrome Mobile).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-activity"></i></div>
              <div><strong>Active visitor count</strong>
              <p class="text-muted small">The badge at the top shows the number of visitors <strong>currently connected</strong>, and the number of <strong>active users</strong> over the recent period.
              <span class="d-block mt-1 text-dark">👉 <em>The page does not auto-refresh — reload to get the latest data.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Dernières visites</h1>
    <p class="lead">Voyez qui est sur le site en ce moment et qui a visité récemment.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Membre &amp; Email</strong>
              <p class="text-muted small">Nom complet et adresse email du membre. Trié par activité la plus récente en premier.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Dernière page</strong>
              <p class="text-muted small">La dernière URI visitée par le membre, surlignée en rose. Cliquez pour naviguer vers cette page.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>Dernière activité</strong>
              <p class="text-muted small">Temps relatif (ex. <em>À l''instant</em>, <em>23 heures</em>, <em>7 jours</em>) et horodatage exact de la dernière action enregistrée.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-display-fill"></i></div>
              <div><strong>OS &amp; Navigateur</strong>
              <p class="text-muted small">Système d''exploitation et navigateur utilisés lors de la dernière session (ex. Linux / Firefox 140, Android / Chrome Mobile).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-activity"></i></div>
              <div><strong>Compteurs d''activité</strong>
              <p class="text-muted small">Le badge en haut indique le nombre de visiteurs <strong>actuellement connectés</strong> et le nombre d''<strong>utilisateurs actifs</strong> sur la période récente.
              <span class="d-block mt-1 text-dark">👉 <em>La page ne se rafraîchit pas automatiquement — rechargez pour obtenir les données les plus récentes.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Ostatnie wizyty</h1>
    <p class="lead">Zobacz, kto jest teraz na stronie i kto odwiedził ją ostatnio.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Członek &amp; Email</strong>
              <p class="text-muted small">Pełne imię i nazwisko oraz adres email członka. Posortowane według najnowszej aktywności.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Ostatnia strona</strong>
              <p class="text-muted small">Ostatnie URI odwiedzone przez członka, wyróżnione różowym kolorem. Kliknij, aby przejść do tej strony.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>Ostatnia aktywność</strong>
              <p class="text-muted small">Czas względny (np. <em>Przed chwilą</em>, <em>23 godziny</em>, <em>7 dni</em>) i dokładny znacznik czasu ostatniej zarejestrowanej akcji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-display-fill"></i></div>
              <div><strong>OS &amp; Przeglądarka</strong>
              <p class="text-muted small">System operacyjny i przeglądarka użyte podczas ostatniej sesji (np. Linux / Firefox 140, Android / Chrome Mobile).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-activity"></i></div>
              <div><strong>Liczniki aktywności</strong>
              <p class="text-muted small">Etykieta u góry pokazuje liczbę odwiedzających <strong>aktualnie połączonych</strong> i liczbę <strong>aktywnych użytkowników</strong> w ostatnim okresie.
              <span class="d-block mt-1 text-dark">👉 <em>Strona nie odświeża się automatycznie — przeładuj, aby uzyskać najnowsze dane.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>'),

('Help_AlertAsked',
'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Member Alerts</h1>
    <p class="lead">See which members have requested to be notified of new content.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Member</strong>
              <p class="text-muted small">Full name of the member (with nickname in brackets if set). Members are listed alphabetically.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-slash-fill"></i></div>
              <div><strong>No alert</strong>
              <p class="text-muted small">An <strong>X</strong> in this column means the member has chosen not to receive any notification.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-event-fill"></i></div>
              <div><strong>Event</strong>
              <p class="text-muted small">An <strong>X</strong> means the member is notified by email when a new event is published.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-newspaper"></i></div>
              <div><strong>Article</strong>
              <p class="text-muted small">An <strong>X</strong> means the member is notified by email when a new article is published.
              <span class="d-block mt-1 text-dark">👉 <em>A member can subscribe to both types simultaneously.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-gear-fill fs-3 text-primary me-3"></i>
        <span><strong>Member preferences:</strong> Each member manages their own notification settings from their personal profile. This page is a read-only overview for administrators.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Alertes demandées par les membres</h1>
    <p class="lead">Consultez quels membres souhaitent être notifiés lors de la publication de nouveaux contenus.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Membre</strong>
              <p class="text-muted small">Nom complet du membre (avec surnom entre parenthèses si renseigné). Les membres sont listés par ordre alphabétique.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-slash-fill"></i></div>
              <div><strong>Aucune alerte</strong>
              <p class="text-muted small">Un <strong>X</strong> dans cette colonne indique que le membre a choisi de ne recevoir aucune notification.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-event-fill"></i></div>
              <div><strong>Evénement</strong>
              <p class="text-muted small">Un <strong>X</strong> indique que le membre est notifié par email lors de la publication d''un nouvel événement.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-newspaper"></i></div>
              <div><strong>Article</strong>
              <p class="text-muted small">Un <strong>X</strong> indique que le membre est notifié par email lors de la publication d''un nouvel article.
              <span class="d-block mt-1 text-dark">👉 <em>Un membre peut souscrire aux deux types simultanément.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-gear-fill fs-3 text-primary me-3"></i>
        <span><strong>Préférences membres :</strong> Chaque membre gère ses propres paramètres de notification depuis son profil personnel. Cette page est une vue d''ensemble en lecture seule pour les administrateurs.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>',

'<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Alerty zamówione przez członków</h1>
    <p class="lead">Sprawdź, którzy członkowie chcą być powiadamiani o nowych treściach.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Członek</strong>
              <p class="text-muted small">Pełne imię i nazwisko członka (z pseudonimem w nawiasach, jeśli podano). Członkowie są wymienieni alfabetycznie.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-slash-fill"></i></div>
              <div><strong>Brak alertu</strong>
              <p class="text-muted small"><strong>X</strong> w tej kolumnie oznacza, że członek wybrał brak powiadomień.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-event-fill"></i></div>
              <div><strong>Wydarzenie</strong>
              <p class="text-muted small"><strong>X</strong> oznacza, że członek otrzymuje powiadomienie e-mail przy publikacji nowego wydarzenia.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-newspaper"></i></div>
              <div><strong>Artykuł</strong>
              <p class="text-muted small"><strong>X</strong> oznacza, że członek otrzymuje powiadomienie e-mail przy publikacji nowego artykułu.
              <span class="d-block mt-1 text-dark">👉 <em>Członek może subskrybować oba typy jednocześnie.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-gear-fill fs-3 text-primary me-3"></i>
        <span><strong>Preferencje członków:</strong> Każdy członek zarządza własnymi ustawieniami powiadomień z poziomu swojego profilu. Ta strona jest widokiem tylko do odczytu dla administratorów.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>');
SQL;
        $pdo->exec($sql);

        return 30;
    }
}