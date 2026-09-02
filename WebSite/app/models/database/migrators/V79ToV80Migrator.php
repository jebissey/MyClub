<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V79ToV80Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('Help_Media_list', 
    '<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Media Manager</h1>
    <p class="lead">Manage all files (images, documents, sounds) used on your site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Finding a File</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
              <div><strong>Year and Month</strong>
              <p class="text-muted small">Filter files by their upload date using the two dropdown lists.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-filetype-raw"></i></div>
              <div><strong>Extension</strong>
              <p class="text-muted small">Restrict the display to a specific file type (jpg, pdf, mp3...).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-search"></i></div>
              <div><strong>Search</strong>
              <p class="text-muted small">Enter a pattern to search for in the file name, then confirm with the magnifying glass.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eraser-fill"></i></div>
              <div><strong>Unused</strong>
                <p class="text-muted small">
                  Check this box to display only files that are not linked to any article, carousel, 
                  or message: they can generally be deleted safely.
                </p>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-list-ol"></i></div>
              <div><strong>Pagination</strong>
              <p class="text-muted small">The counter (e.g. 21/21) shows the number of files matching your current filters.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the Table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-image"></i></div>
              <div>
                <strong>Preview</strong>
                <p class="text-muted small">
                  Thumbnail of the file when it is an image, generic icon for documents, and built-in player for audio files.
                </p>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hdd"></i></div>
                <div>
                  <strong>Size</strong>
                  <p class="text-muted small">Weight of the file on the server, expressed in kilobytes or megabytes.</p>
                </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-diagram-3"></i></div>
                <div>
                  <strong>Article / Carousel / Message</strong>
                  <p class="text-muted small">
                    These three columns show where the media is used on the site. 
                    If a "Yes" link appears, click it to view the use case(s). 
                    If all three columns are empty, the file is not used anywhere and can be safely deleted.
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
              <div><strong>Shared</strong>
                <p class="text-muted small">
                  Indicates whether the file is accessible via a public share link ("Yes") or restricted to logged-in members.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Available Actions</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>View</strong>
              <p class="text-muted small">Opens the file in a new tab or a preview window.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-white rounded p-2 me-3"><i class="bi bi-clipboard-fill"></i></div>
              <div><strong>Copy URL</strong>
                <p class="text-muted small">
                  Copies the file''s address to the clipboard, useful for inserting it elsewhere (article, message...).
                </p>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-info text-white rounded p-2 me-3"><i class="bi bi-share-fill"></i></div>
              <div><strong>Share</strong>
              <p class="text-muted small">Enables or changes the public sharing of the file.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-danger text-white rounded p-2 me-3"><i class="bi bi-trash-fill"></i></div>
              <div><strong>Delete</strong>
                <p class="text-muted small">
                  Permanently deletes the file. Not possible if it is still used in an article, a carousel, or a message.
                </p>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-crop"></i></div>
              <div><strong>Crop</strong>
                <p class="text-muted small">
                  Available only for photos, this tool lets you adjust the image cropping.
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
    <h2 class="h4 mb-4">Key Takeaways</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-cloud-arrow-up-fill fs-3 text-primary me-3"></i>
        <span>
          <strong>Upload a File:</strong> 
          Use the blue button at the top left to add a new image, document, or sound to the media library.
        </span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-primary me-3"></i>
        <span>
          <strong>Tip:</strong> 
          Check "Unused" from time to time to spot and delete files that are no longer needed, freeing up storage space.
        </span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration privileges.</span>
      </div>
    </div>
  </section>
</div>', 
    '<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Gestionnaire de médias</h1>
    <p class="lead">Gérez l''ensemble des fichiers (images, documents, sons) utilisés sur votre site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Trouver un fichier</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
                <div>
                  <strong>Année et mois</strong>
                  <p class="text-muted small">
                    Filtrez les fichiers en fonction de leur date d''ajout grâce aux deux listes déroulantes.
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-filetype-raw"></i></div>
              <div><strong>Extension</strong>
              <p class="text-muted small">Restreignez l''affichage à un type de fichier précis (jpg, pdf, mp3…).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-search"></i></div>
                <div>
                  <strong>Rechercher</strong>
                  <p class="text-muted small">
                    Saisissez un motif à rechercher dans le nom du fichier puis validez avec la loupe.
                  </p>
                </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eraser-fill"></i></div>
                <div>
                  <strong>Non utilisés</strong>
                  <p class="text-muted small">
                    Cochez cette case pour n''afficher que les fichiers qui ne sont liés à aucun article, 
                    carousel ou message : ils peuvent généralement être supprimés sans risque.
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-list-ol"></i></div>
                <div>
                  <strong>Pagination</strong>
                  <p class="text-muted small">
                    Le compteur (ex. 21/21) indique le nombre de fichiers correspondant à vos filtres actuels.
                  </p>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-image"></i></div>
                <div>
                  <strong>Aperçu</strong>
                  <p class="text-muted small">
                    Vignette du fichier lorsque c''est une image, icône générique pour les documents, 
                    et lecteur intégré pour les fichiers audio.
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hdd"></i></div>
              <div><strong>Taille</strong>
              <p class="text-muted small">Poids du fichier sur le serveur, exprimé en kilo-octets ou méga-octets.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-diagram-3"></i></div>
                <div>
                  <strong>Article / Carousel / Message</strong>
                  <p class="text-muted small">
                    Ces trois colonnes indiquent où le média est utilisé sur le site. Si un lien « Oui » apparaît, 
                    cliquez dessus pour consulter le ou les cas d''emploi. Si les trois colonnes sont vides, 
                    le fichier n''est utilisé nulle part et peut être supprimé sans risque.
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
                <div>
                  <strong>Partagé</strong>
                  <p class="text-muted small">
                    Indique si le fichier est accessible via un lien de partage public (« Oui ») ou réservé aux membres connectés.
                  </p>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les actions disponibles</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Visualiser</strong>
              <p class="text-muted small">Ouvre le fichier dans un nouvel onglet ou une fenêtre d''aperçu.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-white rounded p-2 me-3"><i class="bi bi-clipboard-fill"></i></div>
                <div>
                  <strong>Copier l''URL</strong>
                  <p class="text-muted small">
                    Copie l''adresse du fichier dans le presse-papier, pratique pour l''insérer ailleurs (article, message…).
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-info text-white rounded p-2 me-3"><i class="bi bi-share-fill"></i></div>
              <div><strong>Partager</strong>
              <p class="text-muted small">Active ou modifie le partage public du fichier.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-danger text-white rounded p-2 me-3"><i class="bi bi-trash-fill"></i></div>
                <div>
                  <strong>Supprimer</strong>
                  <p class="text-muted small">
                    Efface définitivement le fichier. Impossible si celui-ci est encore utilisé dans un article, un carousel ou un message.
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-crop"></i></div>
                <div>
                  <strong>Retailler</strong>
                  <p class="text-muted small">
                    Disponible uniquement pour les photos, cet outil permet d''ajuster le cadrage de l''image.
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
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-cloud-arrow-up-fill fs-3 text-primary me-3"></i>
        <span>
          <strong>Uploader un fichier :</strong> 
          Utilisez le bouton bleu en haut à gauche pour ajouter une nouvelle image, un document ou un son à la médiathèque.
        </span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-primary me-3"></i>
        <span><strong>Conseil :</strong> 
          Pensez à cocher « Non utilisés » de temps en temps pour repérer et supprimer 
          les fichiers devenus inutiles et libérer de l''espace de stockage.
        </span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>', 
    '<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Menedżer mediów</h1>
    <p class="lead">Zarządzaj wszystkimi plikami (obrazami, dokumentami, dźwiękami) używanymi na Twojej stronie.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Wyszukiwanie pliku</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
              <div><strong>Rok i miesiąc</strong>
              <p class="text-muted small">Filtruj pliki według daty dodania za pomocą dwóch list rozwijanych.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-filetype-raw"></i></div>
              <div><strong>Rozszerzenie</strong>
              <p class="text-muted small">Ogranicz wyświetlanie do określonego typu pliku (jpg, pdf, mp3...).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-search"></i></div>
              <div><strong>Szukaj</strong>
              <p class="text-muted small">Wpisz wzorzec do wyszukania w nazwie pliku, a następnie potwierdź lupą.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eraser-fill"></i></div>
                <div>
                  <strong>Nieużywane</strong>
                  <p class="text-muted small">
                    Zaznacz to pole, aby wyświetlić tylko pliki, które nie są powiązane z żadnym artykułem, 
                    karuzelą ani wiadomością: zazwyczaj można je bezpiecznie usunąć.
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-list-ol"></i></div>
                <div>
                  <strong>Paginacja</strong>
                  <p class="text-muted small">
                    Licznik (np. 21/21) pokazuje liczbę plików odpowiadających bieżącym filtrom.
                  </p>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Odczytywanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-image"></i></div>
                <div>
                  <strong>Podgląd</strong>
                  <p class="text-muted small">
                    Miniatura pliku, jeśli jest to obraz, ogólna ikona dla dokumentów oraz wbudowany odtwarzacz dla plików dźwiękowych.
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hdd"></i></div>
                <div>
                  <strong>Rozmiar</strong>
                  <p class="text-muted small">
                    Waga pliku na serwerze, wyrażona w kilobajtach lub megabajtach.
                  </p>
                </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-diagram-3"></i></div>
                <div>
                  <strong>Artykuł / Karuzela / Wiadomość</strong>
                  <p class="text-muted small">
                    Te trzy kolumny pokazują, gdzie dane medium jest używane na stronie. Jeśli pojawi się link "Tak", 
                    kliknij go, aby zobaczyć przypadki użycia. Jeśli wszystkie trzy kolumny są puste, 
                    plik nie jest nigdzie używany i można go bezpiecznie usunąć.
                  </p>
                </div>
              </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
                <div>
                  <strong>Udostępniony</strong>
                  <p class="text-muted small">
                    Wskazuje, czy plik jest dostępny za pomocą publicznego linku udostępniania ("Tak"), 
                    czy zarezerwowany dla zalogowanych członków.
                  </p>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Dostępne działania</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-success text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Podgląd</strong>
              <p class="text-muted small">Otwiera plik w nowej karcie lub oknie podglądu.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-warning text-white rounded p-2 me-3"><i class="bi bi-clipboard-fill"></i></div>
                <div>
                  <strong>Kopiuj adres URL</strong>
                  <p class="text-muted small">
                    Kopiuje adres pliku do schowka, przydatne do wstawienia go w innym miejscu (artykuł, wiadomość...).
                  </p>
                </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-info text-white rounded p-2 me-3"><i class="bi bi-share-fill"></i></div>
              <div><strong>Udostępnij</strong>
              <p class="text-muted small">Włącza lub zmienia publiczne udostępnianie pliku.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-danger text-white rounded p-2 me-3"><i class="bi bi-trash-fill"></i></div>
              <div>
                <strong>Usuń</strong>
                <p class="text-muted small">
                  Trwale usuwa plik. Niemożliwe, jeśli jest on nadal używany w artykule, karuzeli lub wiadomości.
                </p>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-secondary text-white rounded p-2 me-3"><i class="bi bi-crop"></i></div>
                <div>
                  <strong>Kadruj</strong>
                  <p class="text-muted small">
                    Dostępne tylko dla zdjęć, to narzędzie pozwala dostosować kadrowanie obrazu.
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
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-cloud-arrow-up-fill fs-3 text-primary me-3"></i>
        <span>
          <strong>Prześlij plik:</strong> 
          Użyj niebieskiego przycisku w lewym górnym rogu, aby dodać nowy obraz, dokument lub dźwięk do biblioteki mediów.
        </span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-primary me-3"></i>
        <span>
          <strong>Wskazówka:</strong> 
          Od czasu do czasu zaznacz "Nieużywane", aby wykryć i usunąć zbędne pliki, zwalniając miejsce na dysku.
        </span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span>
          <strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienia administracyjne.
        </span>
      </div>
    </div>
  </section>
</div>');
SQL);

        return 80;
    }
}
