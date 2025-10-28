<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V1ToV2Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("UPDATE Person SET Avatar = '' WHERE UseGravatar = 'yes'");
        $avatarMap = [
            ['😍', ['emojiAmoureux.png', 'emojiHeartEyes.png']],
            ['😇', ['emojiAnge.png']],
            ['😎', ['emojiBright.png']],
            ['😘', ['emojiKiss.png']],
            ['😀', ['emojiLaugh.png']],
            ['🤣', ['emojiLaughing.png']],
            ['🤔', ['emojiPensif.png']],
            ['🙂', ['emojiSmile.png', 'emojiHeureux.png']],
            ['🤩', ['emojiStarStruck-.png']],
            ['😋', ['emojiTongue.png']],
            ['😉', ['emojiWink.png']],
            ['🤪', ['emojiZanyFace.png']],
        ];
        $stmt = $pdo->prepare("UPDATE Person SET Avatar = :emoji WHERE Avatar IN (" . implode(',', array_fill(0, count($avatarMap[0][1]), '?')) . ")");
        foreach ($avatarMap as [$emoji, $filenames]) {
            $inClause = implode(',', array_fill(0, count($filenames), '?'));
            $sql = "UPDATE Person SET Avatar = ? WHERE Avatar IN ($inClause)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([$emoji], $filenames));
        }
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN VapidPublicKey TEXT");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN VapidPrivateKey TEXT");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN SendEmailAddress TEXT");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN SendEmailPassword TEXT");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN SendEmailHost TEXT");
        $pdo->exec('CREATE TABLE "PushSubscription" (
            "Id"	INTEGER,
            "IdPerson"	INTEGER NOT NULL,
            "EndPoint"	TEXT NOT NULL UNIQUE,
            "Auth"	TEXT NOT NULL,
            "CreatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
            "P256dh"	TEXT NOT NULL DEFAULT "",
            PRIMARY KEY("Id"),
            FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
        )');
        $pdo->exec("INSERT INTO Languages (Name, en_US, fr_FR) VALUES ('connections', 'Connections', 'Connexions')");
        $pdo->exec("
            INSERT INTO Languages (Name, en_US, fr_FR) VALUES (
                'ErrorLyricsFileNotFound',
                '<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>📁 Lyrics File Not Found</h1>
                        <p class=''mt-3''>
                        The lyrics file could not be found.<br>
                        Please make sure the song exists and that its lyrics file (<code>.lrc</code>) is correctly named.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ The file name might not match the song name.</li>
                        <li>➡️ The file might have been moved or deleted.</li>
                        </ul>
                        <a href='/' class=''btn btn-primary mt-4''>🏠 Back to homepage</a>
                    </div>
                </div>',
                '<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>📁 Fichier de paroles introuvable</h1>
                        <p class=''mt-3''>
                        Le fichier de paroles est introuvable.<br>
                        Vérifie que la chanson existe et que son fichier <code>.lrc</code> porte bien le même nom.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Le nom du fichier ne correspond pas à celui de la chanson.</li>
                        <li>➡️ Le fichier a été déplacé ou supprimé.</li>
                        </ul>
                        <a href='/' class=''btn btn-primary mt-4''>🏠 Retour à l’accueil</a>
                    </div>
                </div>'
                );
            ");
        $pdo->exec("
            INSERT INTO Languages (Name, en_US, fr_FR) VALUES (
                'ErrorLyricsFileNotReadable',
                '<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>🔒 Lyrics File Not Readable</h1>
                        <p class=''mt-3''>
                        The lyrics file exists but cannot be read.<br>
                        Please check file permissions or contact the administrator.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ The file might not have proper read permissions.</li>
                        <li>➡️ The file might be locked or corrupted.</li>
                        </ul>
                        <a href='/' class=''btn btn-primary mt-4''>🏠 Back to homepage</a>
                    </div>
                </div>',
                '<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>🔒 Fichier de paroles non lisible</h1>
                        <p class=''mt-3''>
                        Le fichier de paroles existe mais n’a pas pu être lu.<br>
                        Vérifie les permissions du fichier ou contacte l’administrateur.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Le fichier n’a peut-être pas les droits de lecture suffisants.</li>
                        <li>➡️ Le fichier est peut-être verrouillé ou corrompu.</li>
                        </ul>
                        <a href='/' class=''btn btn-primary mt-4''>🏠 Retour à l’accueil</a>
                    </div>
                </div>'
                );
            ");
        $pdo->exec("
            INSERT INTO Languages (Name, en_US, fr_FR) VALUES (
                'ErrorLyricsFileReadError',
                '<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>⚠️ Lyrics File Reading Error</h1>
                        <p class=''mt-3''>
                        We encountered an unexpected error while reading the lyrics file.<br>
                        Please verify the file content or try again later.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ The file might be corrupted.</li>
                        <li>➡️ The server encountered a temporary I/O error.</li>
                        </ul>
                        <a href='/' class=''btn btn-primary mt-4''>🏠 Back to homepage</a>
                    </div>
                </div>',
                '<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>⚠️ Erreur de lecture du fichier de paroles</h1>
                        <p class=''mt-3''>
                        Une erreur est survenue lors de la lecture du fichier de paroles.<br>
                        Vérifie le contenu du fichier ou réessaie plus tard.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Le fichier est peut-être corrompu.</li>
                        <li>➡️ Le serveur a rencontré une erreur d’accès disque temporaire.</li>
                        </ul>
                        <a href='/' class=''btn btn-primary mt-4''>🏠 Retour à l’accueil</a>
                    </div>
                </div>'
                );
            ");



        return 2;
    }
}
