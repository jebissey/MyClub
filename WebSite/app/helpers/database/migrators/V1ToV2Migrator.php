<?php

namespace app\helpers\database\migrators;

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
        return 2;
    }
}
