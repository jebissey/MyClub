<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

/**
 * Adds the Invitation table: a pending event invitation sent to an external
 * email address, before any Individual/Contact exists for that person.
 *
 * Once the invitee clicks the link and confirms (EventController::register()),
 * an Individual (+ Contact subtype, unless the email already matches an
 * existing Individual) is created and a Participant row is added, carrying
 * over InvitedBy/InvitedAt from the Invitation. The Invitation row itself is
 * kept afterwards so its Token stays valid to toggle registration on/off.
 *
 * This replaces the old Guest table, dropped by V80ToV81Migrator when its
 * rows were merged directly into Participant — which lost the distinction
 * between "invited" and "confirmed participant" that Guest used to provide.
 */
final class V84ToV85Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
            CREATE TABLE "Invitation" (
                "Id"        INTEGER PRIMARY KEY,
                "Email"     TEXT    NOT NULL,
                "NickName"  TEXT,
                "IdEvent"   INTEGER NOT NULL,
                "Token"     TEXT    NOT NULL,
                "InvitedBy" INTEGER NOT NULL,
                "InvitedAt" TEXT    NOT NULL,
                FOREIGN KEY("IdEvent")   REFERENCES "Event"("Id"),
                FOREIGN KEY("InvitedBy") REFERENCES "Member"("Id"),
                UNIQUE("Email", "IdEvent")
            )
        SQL);

        return 85;
    }
}
