<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V2ToV3Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("INSERT INTO Languages (Name, en_US, fr_FR) VALUES ('connections', 'Connections', 'Connexions')");
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR)
VALUES (
'message_password_reset_sent',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-success">📧 Email sent!</h1>
    <p class="mt-3">
      An email with a link to <strong>create a new password</strong> has been sent to the address you entered.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Please note:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Check your <strong>spam</strong> or <strong>junk mail</strong> folder if you don’t see the message in your inbox.</li>
      <li>➡️ Simply click the link in the email to set your new password.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Back to Home</a>
  </div>
</div>',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-success">📧 Courriel envoyé !</h1>
    <p class="mt-3">
      Un message contenant un lien pour <strong>créer un nouveau mot de passe</strong> vient d’être envoyé à l’adresse courriel que vous avez indiquée.
    </p>
    <hr class="my-4">
    <h5>ℹ️ À savoir :</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Pensez à vérifier votre dossier <strong>spam</strong> ou <strong>courrier indésirable</strong> si vous ne trouvez pas le message.</li>
      <li>➡️ Cliquez simplement sur le lien présent dans le courriel pour définir votre nouveau mot de passe.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>'
),
(
'message_password_reset_failed',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">⚠️ Email could not be sent</h1>
    <p class="mt-3">
      The password reset email <strong>could not be sent</strong> to the address you entered.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Please check the following:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Make sure the email address you entered is correct and registered in our system.</li>
      <li>➡️ If the issue continues, please contact the <strong>webmaster</strong> or site administrator.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Back to Home</a>
  </div>
</div>',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">⚠️ Échec de l’envoi du courriel</h1>
    <p class="mt-3">
      Le message de réinitialisation du mot de passe <strong>n’a pas pu être envoyé</strong> à l’adresse courriel indiquée.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Vérifiez les points suivants :</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Assurez-vous que l’adresse courriel saisie est correcte et enregistrée dans notre système.</li>
      <li>➡️ Si le problème persiste, contactez le <strong>webmaster</strong> ou l’administrateur du site.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>'
),
(
'message_email_unknown',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">📧 Unknown Email Address</h1>
    <p class="mt-3">
      The email address you entered <strong>does not exist</strong> in our system.
    </p>
    <hr class="my-4">
    <h5>🔍 Please check the following:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Make sure you typed your email address correctly, without any spelling mistakes.</li>
      <li>➡️ If you have never created an account, please contact the site administrator to request one.</li>
      <li>➡️ If you are unsure, contact the <strong>webmaster</strong> or the club administrator.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Back to Home</a>
  </div>
</div>',
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">📧 Adresse courriel inconnue</h1>
    <p class="mt-3">
      L’adresse courriel que vous avez saisie <strong>n’existe pas</strong> dans notre système.
    </p>
    <hr class="my-4">
    <h5>🔍 Vérifiez les points suivants :</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Assurez-vous que vous avez bien saisi votre adresse courriel sans erreur de frappe.</li>
      <li>➡️ Si vous n’avez jamais créé de compte, vous pouvez en demander un auprès de l’administrateur du site.</li>
      <li>➡️ En cas de doute, contactez le <strong>webmaster</strong> ou le responsable du club.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>'
),
(
'ErrorLyricsFileNotFound',
'<div class="container text-center mt-5">
    <div class="card shadow-lg rounded-3 p-4">
        <h1 class="text-danger">📁 Lyrics File Not Found</h1>
        <p class="mt-3">
        The lyrics file could not be found.<br>
        Please make sure the song exists and that its lyrics file (<code>.lrc</code>) is correctly named.
        </p>
        <ul class="text-start mx-auto d-inline-block mt-3">
        <li>➡️ The file name might not match the song name.</li>
        <li>➡️ The file might have been moved or deleted.</li>
        </ul>
        <a href="/" class="btn btn-primary mt-4">🏠 Back to homepage</a>
    </div>
</div>',
'<div class="container text-center mt-5">
    <div class="card shadow-lg rounded-3 p-4">
        <h1 class="text-danger">📁 Fichier de paroles introuvable</h1>
        <p class="mt-3">
        Le fichier de paroles est introuvable.<br>
        Vérifie que la chanson existe et que son fichier <code>.lrc</code> porte bien le même nom.
        </p>
        <ul class="text-start mx-auto d-inline-block mt-3">
        <li>➡️ Le nom du fichier ne correspond pas à celui de la chanson.</li>
        <li>➡️ Le fichier a été déplacé ou supprimé.</li>
        </ul>
        <a href="/" class="btn btn-primary mt-4">🏠 Retour à l’accueil</a>
    </div>
</div>'
),
(
'ErrorLyricsFileNotReadable',
'<div class="container text-center mt-5">
    <div class="card shadow-lg rounded-3 p-4">
        <h1 class="text-danger">🔒 Lyrics File Not Readable</h1>
        <p class="mt-3">
        The lyrics file exists but cannot be read.<br>
        Please check file permissions or contact the administrator.
        </p>
        <ul class="text-start mx-auto d-inline-block mt-3">
            <li>➡️ The file might not have proper read permissions.</li>
            <li>➡️ The file might be locked or corrupted.</li>
        </ul>
        <a href="/" class="btn btn-primary mt-4">🏠 Back to homepage</a>
    </div>
</div>',
'<div class="container text-center mt-5">
    <div class="card shadow-lg rounded-3 p-4">
        <h1 class="text-danger">🔒 Fichier de paroles non lisible</h1>
        <p class="mt-3">
        Le fichier de paroles existe mais n’a pas pu être lu.<br>
        Vérifie les permissions du fichier ou contacte l’administrateur.
        </p>
        <ul class="text-start mx-auto d-inline-block mt-3">
            <li>➡️ Le fichier n’a peut-être pas les droits de lecture suffisants.</li>
            <li>➡️ Le fichier est peut-être verrouillé ou corrompu.</li>
        </ul>
        <a href="/" class="btn btn-primary mt-4">🏠 Retour à l’accueil</a>
    </div>
</div>'
),
(
'ErrorLyricsFileReadError',
'<div class="container text-center mt-5">
    <div class="card shadow-lg rounded-3 p-4">
        <h1 class="text-danger">⚠️ Lyrics File Reading Error</h1>
        <p class="mt-3">
        We encountered an unexpected error while reading the lyrics file.<br>
        Please verify the file content or try again later.
        </p>
        <ul class="text-start mx-auto d-inline-block mt-3">
            <li>➡️ The file might be corrupted.</li>
            <li>➡️ The server encountered a temporary I/O error.</li>
        </ul>
        <a href="/" class="btn btn-primary mt-4">🏠 Back to homepage</a>
    </div>
</div>',
'<div class="container text-center mt-5">
    <div class="card shadow-lg rounded-3 p-4">
        <h1 class="text-danger">⚠️ Erreur de lecture du fichier de paroles</h1>
        <p class="mt-3">
        Une erreur est survenue lors de la lecture du fichier de paroles.<br>
        Vérifie le contenu du fichier ou réessaie plus tard.
        </p>
        <ul class="text-start mx-auto d-inline-block mt-3">
            <li>➡️ Le fichier est peut-être corrompu.</li>
            <li>➡️ Le serveur a rencontré une erreur d’accès disque temporaire.</li>
        </ul>
        <a href="/" class="btn btn-primary mt-4">🏠 Retour à l’accueil</a>
    </div>
</div>'
);
SQL;
        $pdo->exec($sql);
        $pdo->exec('CREATE TABLE "KaraokeClient" (
            "Id"	INTEGER,
            "ClientId"	TEXT NOT NULL UNIQUE,
            "IdKaraokeSession"	INTEGER NOT NULL,
            "IsHost"	INTEGER DEFAULT 0,
            "LastHeartbeat"	TEXT NOT NULL DEFAULT current_timestamp,
            "CreatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
            PRIMARY KEY("Id"),
            FOREIGN KEY("IdKaraokeSession") REFERENCES "KaraokeSession"("Id")
        )');
        $pdo->exec('CREATE TABLE "KaraokeSession" (
            "Id"	INTEGER,
            "SessionId"	TEXT NOT NULL UNIQUE,
            "SongId"	TEXT NOT NULL,
            "Status"	TEXT DEFAULT "waiting",
            "CountdownStart"	INTEGER,
            "PlayStartTime"	INTEGER,
            "CurrentTime"	REAL DEFAULT 0,
            "CreatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
            "UpdatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
            PRIMARY KEY("Id")
        )');

        return 3;
    }
}
