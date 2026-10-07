<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V89ToV90Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('navbar.person_manager.export',
'Export members',
'Exporter les membres',
'Eksportuj członków'),
('export.sheet_name',
'Members',
'Membres',
'Członkowie'),
('import.warning.fictive_email',
'Line {line}: email missing or invalid, fictive address created: {email}',
'Ligne {line} : email absent ou invalide, adresse fictive créée : {email}',
'Wiersz {line}: brak adresu e-mail lub jest nieprawidłowy, utworzono adres fikcyjny: {email}'),
('export.title',
'Export members',
'Exporter les membres',
'Eksport członków'),
('export.help',
'Download all members (active and inactive) as an .ods spreadsheet, including custom fields.',
'Téléchargez tous les membres (actifs et inactifs) dans un classeur .ods, champs personnalisés inclus.',
'Pobierz wszystkich członków (aktywnych i nieaktywnych) w arkuszu .ods, wraz z polami niestandardowymi.'),
('export.form.file_name',
'File name',
'Nom du fichier',
'Nazwa pliku'),
('export.form.submit',
'Export',
'Exporter',
'Eksportuj'),
('export.form.submitting',
'Export in progress...',
'Export en cours...',
'Trwa eksportowanie...'),
('export.form.fallback_note',
'Your browser saves the file in its download folder (or asks where to save it, depending on its settings).',
'Votre navigateur enregistrera le fichier dans son dossier de téléchargement (ou vous demandera où l''enregistrer, selon ses réglages).',
'Przeglądarka zapisze plik w folderze pobierania (lub zapyta o miejsce zapisu, zależnie od ustawień).'),
('export.js.done',
'Export completed.',
'Export terminé.',
'Eksport zakończony.'),
('export.js.error',
'The export failed.',
'L''export a échoué.',
'Eksport nie powiódł się.'),
('export.js.file_type',
'OpenDocument spreadsheet',
'Classeur OpenDocument',
'Arkusz OpenDocument'),
('export.default_file_name',
'members',
'membres',
'czlonkowie'),
('export.column.active',
'Active',
'Actif',
'Aktywny'),
('import.ods.error.empty',
'The file contains no member rows.',
'Le fichier ne contient aucune ligne de membre.',
'Plik nie zawiera żadnych wierszy z członkami.'),
('import.ods.error.missing_column',
'Required column not found: {column}',
'Colonne obligatoire introuvable : {column}',
'Nie znaleziono wymaganej kolumny: {column}'),
('import.ods.error.invalid_id',
'Line {line}: invalid Id "{value}", row ignored.',
'Ligne {line} : Id « {value} » invalide, ligne ignorée.',
'Wiersz {line}: nieprawidłowy Id „{value}”, wiersz pominięto.'),
('import.ods.error.unknown_id',
'Line {line}: unknown Id {id}, row ignored.',
'Ligne {line} : Id {id} inconnu, ligne ignorée.',
'Wiersz {line}: nieznany Id {id}, wiersz pominięto.'),
('import.ods.error.invalid_email',
'Line {line}: invalid or missing email address "{email}", row ignored.',
'Ligne {line} : adresse email « {email} » invalide ou absente, ligne ignorée.',
'Wiersz {line}: nieprawidłowy lub brakujący adres e-mail „{email}”, wiersz pominięto.'),
('import.ods.error.email_exists',
'Line {line}: the address {email} is already used by member {id}, row ignored.',
'Ligne {line} : l''adresse {email} est déjà utilisée par le membre {id}, ligne ignorée.',
'Wiersz {line}: adres {email} jest już używany przez członka {id}, wiersz pominięto.'),
('import.ods.warning.duplicate_id',
'Line {line}: Id {id} appears several times, this row was added as a new member.',
'Ligne {line} : l''Id {id} apparaît plusieurs fois, cette ligne a été ajoutée comme nouveau membre.',
'Wiersz {line}: Id {id} występuje wielokrotnie, ten wiersz dodano jako nowego członka.'),
('export.import.title',
'Import a modified export',
'Importer un export modifié',
'Importuj zmodyfikowany eksport'),
('export.import.help',
'Drop an .ods file produced by the export. Rows without an Id are added; members whose Id is missing from the file, 
or whose Active box is not ticked, are deactivated; the others are updated.',
'Déposez un fichier .ods issu de l''export. Les lignes sans Id sont ajoutées ; les membres dont l''Id a disparu du fichier, 
ou dont la case Actif n''est pas cochée, sont désactivés ; les autres sont mis à jour.',
'Upuść plik .ods pochodzący z eksportu. Wiersze bez Id są dodawane; członkowie, 
których Id brakuje w pliku lub u których pole Aktywny nie jest zaznaczone, są dezaktywowani; pozostali są aktualizowani.'),
('export.import.drop',
'Drop an .ods file here, or click to choose one',
'Déposez un fichier .ods ici, ou cliquez pour en choisir un',
'Upuść tutaj plik .ods lub kliknij, aby go wybrać'),
('export.import.confirm',
'{created} created, {updated} updated, {deactivated} deactivated, {reactivated} reactivated, {errors} error(s). Apply these changes?',
'{created} créé(s), {updated} modifié(s), {deactivated} désactivé(s), {reactivated} réactivé(s), {errors} erreur(s). 
Appliquer ces modifications ?',
'{created} utworzonych, {updated} zmienionych, {deactivated} dezaktywowanych, {reactivated} reaktywowanych, błędy: {errors}. 
Zastosować te zmiany?'),
('export.import.no_change',
'No change to apply.',
'Aucune modification à appliquer.',
'Brak zmian do zastosowania.'),
('export.import.result.applied',
'Changes applied.',
'Modifications appliquées.',
'Zmiany zastosowane.'),
('export.import.result.reactivated',
'Reactivated records:',
'Enregistrements réactivés :',
'Reaktywowane rekordy:'),
('export.import.error.no_file',
'No file received.',
'Aucun fichier reçu.',
'Nie otrzymano pliku.'),
('export.import.error.wrong_type',
'Only .ods files are accepted.',
'Seuls les fichiers .ods sont acceptés.',
'Akceptowane są tylko pliki .ods.'),
('export.import.error.invalid_file',
'The file is not a valid .ods file.',
'Le fichier n''est pas un fichier .ods valide.',
'Plik nie jest prawidłowym plikiem .ods.'),
('export.import.error.generic',
'The import failed.',
'L''import a échoué.',
'Import nie powiódł się.'),
('help_missing',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-secondary">🤝 No help here (yet)</h1>

    <p class="mt-3">
      The help for this section doesn’t exist yet...<br>
      but it’s up to <strong>you</strong> to change that!
    </p>

    <p class="fw-bold">
      💡 MyClub is a community project. If you know how this page works, 
      share your knowledge with the webmaster: the next person will thank you.
    </p>

    <hr class="my-4">

    <p>
      ➡️ Not sure where to start? <strong>Tell the webmaster what you would have liked to find here.</strong>
    </p>

    <a href="/" class="btn btn-primary mt-3">🏠 Back to homepage</a>
  </div>
</div>',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-secondary">🤝 Pas encore d’aide ici</h1>

    <p class="mt-3">
      L’aide pour cette rubrique n’existe pas encore...<br>
      mais il ne tient qu’à <strong>vous</strong> d’y remédier !
    </p>

    <p class="fw-bold">
      💡 MyClub est un projet communautaire. Si vous savez comment fonctionne cette page, 
      partagez vos connaissances avec le webmaster : la prochaine personne vous remerciera.
    </p>

    <hr class="my-4">

    <p>
      ➡️ Pas sûr(e) par où commencer ? <strong>Dites au webmaster ce que vous auriez aimé trouver ici.</strong>
    </p>

    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-secondary">🤝 Brak pomocy (jeszcze)</h1>

    <p class="mt-3">
      Pomoc dla tej sekcji jeszcze nie istnieje...<br>
      ale to od <strong>Ciebie</strong> zależy, czy się to zmieni!
    </p>

    <p class="fw-bold">
      💡 MyClub to projekt społecznościowy. Jeśli wiesz, jak działa ta strona, 
      podziel się swoją wiedzą z webmasterem: kolejna osoba Ci za to podziękuje.
    </p>

    <hr class="my-4">

    <p>
      ➡️ Nie wiesz, od czego zacząć? <strong>Napisz do webmastera, czego chciał(a)byś tu znaleźć.</strong>
    </p>

    <a href="/" class="btn btn-primary mt-3">🏠 Powrót do strony głównej</a>
  </div>
</div>'
);
SQL);

        return 90;
    }
}
