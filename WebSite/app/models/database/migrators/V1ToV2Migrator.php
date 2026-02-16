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
        $pdo->exec("UPDATE Settings SET Name = 'Home_header' WHERE Name = 'Greatings'");
        $pdo->exec("UPDATE Settings SET Name = 'Home_footer' WHERE Name = 'Link'");

        return 2;
    }
}
