<?php

declare(strict_types=1);

/**
 * install.php — Installation / mise à jour automatisée de MyClub.
 *
 * Usage :
 *   1) Copier ce fichier ET l'archive "FullInstall.zip" (téléchargée depuis
 *      https://github.com/jebissey/MyClub/releases) à la racine du site, sur l'hébergement.
 *   2) Ouvrir /install.php dans un navigateur.
 *   3) Vérifier le mode détecté (installation / mise à jour) puis confirmer.
 *
 * Ordre des opérations en mode "mise à jour" (important sur hébergement à quota serré) :
 *   1. suppression de tout le contenu existant, SAUF "data", CE fichier et l'archive
 *      -> libère la place AVANT d'extraire, pour éviter le cumul ancien+zip+extraction
 *   2. extraction de l'archive dans un dossier temporaire, puis suppression immédiate
 *      de l'archive (on n'en a plus besoin, ça libère encore de la place)
 *   3. déplacement (rename, pas copie) des fichiers extraits vers la racine,
 *      SAUF le dossier "data" -> pas de duplication d'octets sur disque
 *
 * La suite est prise en charge par l'application elle-même, à la première ouverture
 * du site dans un navigateur : copie du template de base dans data/ (installation)
 * ou exécution des migrateurs (mise à jour), puis suppression du dossier
 * app/models/database/migrators.
 *
 * ATTENTION : en mode mise à jour, les anciens fichiers sont supprimés AVANT
 * que la nouvelle version soit en place. Si l'extraction échoue après coup
 * (quota encore dépassé, etc.), le site reste indisponible jusqu'à relancer
 * l'opération avec une archive plus légère ou plus d'espace disque. Le
 * dossier "data" n'est en revanche jamais touché ni supprimé.
 *
 * SÉCURITÉ : ce script est destructeur. Définissez INSTALL_SECRET ci-dessous
 * avant de le mettre en ligne, et supprimez-le du serveur une fois l'opération
 * terminée (le script propose de le faire lui-même en fin de traitement).
 */

// ------------------------------------------------------------------
// Configuration
// ------------------------------------------------------------------

// Changez cette valeur avant de mettre le fichier en ligne. Laissez vide
// pour désactiver la protection (déconseillé si le site est public).
const INSTALL_SECRET = 'install1234';

const ARCHIVE_NAME  = 'FullInstall.zip';
const ROOT_DIR       = __DIR__;
const TEMP_DIR        = ROOT_DIR . '/_install_tmp';
const DATA_DIR        = ROOT_DIR . '/data';

// Convertit les warnings/notices PHP (ex: ZipArchive::extractTo en cas de
// quota dépassé) en exceptions, pour un affichage propre au lieu d'un texte
// PHP brut exposant les chemins serveur.
set_error_handler(function (int $errno, string $errstr): bool {
    if (!(error_reporting() & $errno)) {
        return false; // erreur volontairement supprimée par @, on laisse faire
    }
    throw new ErrorException($errstr, 0, $errno);
});
ini_set('display_errors', '0');

// ------------------------------------------------------------------
// Utilitaires
// ------------------------------------------------------------------

/** Lit le secret via une fonction (type de retour natif string) : évite que
 *  PHPStan traite la constante comme un littéral figé dans les comparaisons. */
function installSecret(): string
{
    return INSTALL_SECRET;
}

function rrmdirAll(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) {
            rrmdirAll($path);
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}

/** @param list<string> $exclude */
function cleanRootExcept(string $dir, array $exclude): void
{
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..' || in_array($item, $exclude, true)) {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            rrmdirAll($path);
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}

function rcopyAll(string $src, string $dst): void
{
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    foreach (scandir($src) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $s = $src . DIRECTORY_SEPARATOR . $item;
        $d = $dst . DIRECTORY_SEPARATOR . $item;
        if (is_dir($s)) {
            rcopyAll($s, $d);
        } else {
            copy($s, $d);
        }
    }
}

/** Déplace (rename) le contenu de $src vers $dst, sans dupliquer d'octets sur
 *  disque (contrairement à une copie). Ne descend en récursif que si la cible
 *  existe déjà (cas de fusion, normalement rare puisque l'ancien contenu a
 *  été supprimé au préalable).
 *
 *  @param list<string> $excludeTopLevel */
function rmoveAll(string $src, string $dst, array $excludeTopLevel = []): void
{
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    foreach (scandir($src) as $item) {
        if ($item === '.' || $item === '..' || in_array($item, $excludeTopLevel, true)) {
            continue;
        }
        $s = $src . DIRECTORY_SEPARATOR . $item;
        $d = $dst . DIRECTORY_SEPARATOR . $item;
        if (file_exists($d)) {
            // La cible existe déjà : fusion récursive plutôt qu'un rename direct.
            if (is_dir($s) && is_dir($d)) {
                rmoveAll($s, $d);
                @rmdir($s);
            } else {
                @unlink($d);
                if (!@rename($s, $d)) {
                    copy($s, $d);
                    unlink($s);
                }
            }
            continue;
        }
        if (!@rename($s, $d)) {
            // Repli si rename échoue (ex: changement de système de fichiers) :
            // copie puis suppression de la source.
            if (is_dir($s)) {
                rcopyAll($s, $d);
                rrmdirAll($s);
                @rmdir($s);
            } else {
                copy($s, $d);
                unlink($s);
            }
        }
    }
}

/** Si l'archive contient un unique dossier racine (courant avec les zip GitHub),
 *  on redescend dedans pour retrouver les vrais fichiers du site. */
function resolveArchiveRoot(string $extractedDir): string
{
    $entries = array_values(array_diff(scandir($extractedDir), ['.', '..']));
    if (count($entries) === 1 && is_dir($extractedDir . '/' . $entries[0])) {
        return $extractedDir . '/' . $entries[0];
    }
    return $extractedDir;
}

function detectMode(): string
{
    return (is_dir(DATA_DIR) && count(array_diff(scandir(DATA_DIR), ['.', '..'])) > 0)
        ? 'mise à jour'
        : 'installation';
}

function selfUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $hostRaw   = $_SERVER['HTTP_HOST'] ?? null;
    $scriptRaw = $_SERVER['SCRIPT_NAME'] ?? null;
    $host   = is_string($hostRaw) ? $hostRaw : 'localhost';
    $script = is_string($scriptRaw) ? $scriptRaw : '/';
    $dir    = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $scheme . '://' . $host . $dir . '/';
}

function checkSecret(): bool
{
    $secret = installSecret();
    if ($secret === '') {
        return true;
    }
    $key = $_REQUEST['key'] ?? null;
    return is_string($key) && hash_equals($secret, $key);
}

function humanSize(float $bytes): string
{
    $units = ['o', 'Ko', 'Mo', 'Go'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}

/** Prérequis vérifiés AVANT toute action (affichage + exécution).
 *  @return list<array{label: string, ok: bool, detail: string}> */
function checkRequirements(): array
{
    return [
        [
            'label'  => 'PHP 8.4 ou supérieur',
            'ok'     => PHP_VERSION_ID >= 80400,
            'detail' => 'version détectée : ' . PHP_VERSION,
        ],
        [
            'label'  => 'Extension pdo_sqlite',
            'ok'     => extension_loaded('pdo_sqlite'),
            'detail' => extension_loaded('pdo_sqlite') ? 'présente' : 'absente',
        ],
        [
            'label'  => 'Extension intl',
            'ok'     => extension_loaded('intl'),
            'detail' => extension_loaded('intl') ? 'présente' : 'absente',
        ],
        [
            'label'  => 'Extension zip (requise par ce script)',
            'ok'     => class_exists('ZipArchive'),
            'detail' => class_exists('ZipArchive') ? 'présente' : 'absente',
        ],
    ];
}

/** @param list<array{label: string, ok: bool, detail: string}> $requirements */
function requirementsMet(array $requirements): bool
{
    foreach ($requirements as $r) {
        if (!$r['ok']) {
            return false;
        }
    }
    return true;
}

/** @param list<array{label: string, ok: bool, detail: string}> $requirements */
function requirementsHtml(array $requirements): string
{
    $items = '';
    foreach ($requirements as $r) {
        $cls = $r['ok'] ? 'ok' : 'err';
        $mark = $r['ok'] ? '✔' : '✘';
        $items .= "<li class='$cls'>$mark " . htmlspecialchars($r['label'] . ' — ' . $r['detail']) . '</li>';
    }
    return "<p>Prérequis du serveur :</p><ul>$items</ul>";
}

function render(string $title, string $body): void
{
    echo "<!DOCTYPE html><html lang='fr'><head><meta charset='utf-8'>"
        . "<title>$title</title><style>"
        . "body{font-family:sans-serif;max-width:640px;margin:40px auto;line-height:1.5}"
        . "code{background:#f0f0f0;padding:2px 5px;border-radius:3px}"
        . "button{padding:8px 16px;font-size:1rem;cursor:pointer}"
        . "button:disabled{cursor:wait;opacity:.6}"
        . "ul{background:#f7f7f7;padding:12px 24px;border-radius:6px}"
        . ".err{color:#b00020} .ok{color:#0a7d1e} .warn{color:#a15c00}"
        . "</style></head><body><h1>$title</h1>$body</body></html>";
}

// ------------------------------------------------------------------
// Point d'entrée
// ------------------------------------------------------------------

if (!checkSecret()) {
    http_response_code(403);
    render('Accès refusé', '<p class="err">Clé manquante ou invalide (paramètre <code>?key=...</code>).</p>');
    exit;
}

$keyParam = installSecret() !== '' ? '?key=' . urlencode(installSecret()) : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || ($_POST['confirm'] ?? '') !== '1') {
    // --- Écran de confirmation ---
    $mode = detectMode();
    $requirements = checkRequirements();
    $reqOk = requirementsMet($requirements);
    $archivePath = ROOT_DIR . '/' . ARCHIVE_NAME;
    $archiveOk = is_file($archivePath);

    $warn = $mode === 'mise à jour'
        ? "<p class='warn'>Tous les fichiers du site seront supprimés <strong>sauf le dossier <code>data</code></strong> "
          . "<em>avant</em> l'extraction de l'archive (nécessaire sur un hébergement à quota limité). "
          . "En cas d'échec pendant l'extraction, le site restera indisponible jusqu'à relancer l'opération — "
          . "le dossier <code>data</code> ne sera dans tous les cas jamais touché.</p>"
        : '<p>Aucun dossier <code>data</code> existant n\'a été détecté : installation initiale.</p>';

    $archiveMsg = '';
    if ($archiveOk) {
        $archiveSize = filesize($archivePath);
        $archiveMsg = "<p class='ok'>Archive trouvée : " . ARCHIVE_NAME . ' (' . humanSize($archiveSize !== false ? $archiveSize : 0) . ')</p>'
            . "<p class='warn'>Sur un hébergement mutualisé à quota limité, l'espace réellement disponible pour "
            . "votre compte n'est pas mesurable depuis PHP (<code>disk_free_space()</code> ne reflète que la "
            . "partition physique du serveur). Si une erreur \"quota dépassé\" survient malgré tout, videz "
            . "d'autres fichiers (anciens logs, sauvegardes) avant de relancer.</p>";
    } else {
        $archiveMsg = "<p class='err'>Archive " . ARCHIVE_NAME . " introuvable à la racine du site.</p>";
    }

    $body = requirementsHtml($requirements) . "<p>Mode détecté : <strong>$mode</strong></p>$warn$archiveMsg"
        . (!$reqOk
            ? "<p class='err'>Prérequis non satisfaits : aucune opération ne sera lancée. Corrigez la configuration PHP de l'hébergement puis rechargez cette page.</p>"
            : '')
        . ($archiveOk && $reqOk
            ? "<form method='post' action='$keyParam' onsubmit=\"this.querySelector('button').disabled=true;this.querySelector('button').textContent='Traitement en cours, veuillez patienter…';document.body.style.cursor='wait';\">"
              . "<input type='hidden' name='confirm' value='1'>"
              . "<p><label><input type='checkbox' name='auto_delete' value='1' checked> "
              . "Supprimer install.php automatiquement à la fin (recommandé)</label></p>"
              . "<button type='submit'>Lancer l'opération</button></form>"
            : '');

    render('MyClub — Installation / mise à jour', $body);
    exit;
}

// --- Exécution ---
$log = [];
// Ne pas être interrompu si le navigateur se ferme ou si l'opération est longue.
ignore_user_abort(true);
@set_time_limit(0);
try {
    // Prérequis vérifiés avant TOUTE action (aucune suppression tant que non satisfaits).
    $requirements = checkRequirements();
    if (!requirementsMet($requirements)) {
        $missing = [];
        foreach ($requirements as $r) {
            if (!$r['ok']) {
                $missing[] = $r['label'] . ' (' . $r['detail'] . ')';
            }
        }
        throw new RuntimeException('Prérequis non satisfaits, aucune action effectuée : ' . implode(', ', $missing));
    }

    $archivePath = ROOT_DIR . '/' . ARCHIVE_NAME;
    if (!is_file($archivePath)) {
        throw new RuntimeException('Archive ' . ARCHIVE_NAME . ' introuvable.');
    }

    $mode = detectMode();
    $log[] = "Mode : $mode";

    // 1) Libérer la place AVANT toute extraction : on supprime l'ancien
    //    contenu (sauf data, install.php, l'archive) en tout premier.
    if ($mode === 'mise à jour') {
        cleanRootExcept(ROOT_DIR, ['data', basename(__FILE__), ARCHIVE_NAME]);
        $log[] = 'Anciens fichiers supprimés (data conservé).';
    }

    // 2) Extraction dans un dossier temporaire, puis suppression immédiate
    //    de l'archive pour regagner de la place.
    rrmdirAll(TEMP_DIR);
    mkdir(TEMP_DIR, 0755, true);

    $zip = new ZipArchive();
    if ($zip->open($archivePath) !== true) {
        throw new RuntimeException("Impossible d'ouvrir l'archive.");
    }
    $extracted = $zip->extractTo(TEMP_DIR);
    $zip->close();
    if (!$extracted) {
        throw new RuntimeException("Échec de l'extraction (espace disque insuffisant ?).");
    }
    $log[] = 'Archive extraite.';

    @unlink($archivePath);
    $log[] = 'Archive supprimée (place regagnée).';

    // 3) Déplacement (pas copie) vers la racine, sans jamais toucher data.
    $source = resolveArchiveRoot(TEMP_DIR);
    rmoveAll($source, ROOT_DIR, ['data']);
    $log[] = 'Nouveaux fichiers déplacés en place (data non touché).';

    rrmdirAll(TEMP_DIR);
    @rmdir(TEMP_DIR);
    $log[] = 'Dossier temporaire nettoyé.';

    // La migration de la base et la suppression de app/models/database/migrators sont
    // faites par l'application à la première ouverture du site (voir Database.php).
    $url = selfUrl();

    // Suppression d'install.php dans CETTE MÊME requête si demandé : une fois
    // le .htaccess d'origine restauré, une requête ultérieure vers /install.php
    // serait réécrite vers index.php par Apache et n'atteindrait plus ce script.
    $deleteMsg = '';
    if (($_POST['auto_delete'] ?? '') === '1') {
        $deleteMsg = @unlink(__FILE__)
            ? "<p class='ok'>install.php a été supprimé automatiquement.</p>"
            : "<p class='warn'>La suppression automatique a échoué — supprimez install.php manuellement via FTP/gestionnaire de fichiers.</p>";
    } else {
        $deleteMsg = "<p class='warn'>Pensez à supprimer install.php manuellement via FTP/gestionnaire de fichiers "
            . "(le supprimer depuis le navigateur après cette étape échouera probablement : le .htaccess du site, "
            . "désormais restauré, renvoie /install.php vers index.php).</p>";
    }

    $items = '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $log)) . '</li></ul>';
    $status = "<p class='ok'>Fichiers installés.</p>"
        . "<p>Ouvrez maintenant le site : la première visite finalise l'opération (migration de la base si nécessaire).</p>";
    $body = "$status$items$deleteMsg"
        . "<p><a href='$url'>Ouvrir le site et finaliser</a></p>";

    render('MyClub — Résultat', $body);
} catch (Throwable $e) {
    http_response_code(500);
    $items = $log !== [] ? '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $log)) . '</li></ul>' : '';
    render('MyClub — Erreur', "<p class='err'>" . htmlspecialchars($e->getMessage()) . "</p>$items"
        . "<p class='warn'>Si l'erreur mentionne un quota disque dépassé, libérez de l'espace (le dossier <code>data</code> "
        . "n'a pas été touché) puis relancez l'opération avec une nouvelle archive.</p>");
}