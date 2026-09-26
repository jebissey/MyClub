<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

/**
 * V85 → V86
 *
 * - Survey.ClosingDate : DATE → TEXT
 * - DesignVote.Vote    : INTEGER → TEXT (données déjà textuelles en prod)
 * - CHECK sur booléens (0/1) et enums applicatifs :
 *     EventAudience, SurveyVisibility, OrderVisibility, YesNo,
 *     DesignStatus, DesignVote, KaraokeSessionStatus, Message.From
 *
 * Schéma post-V81 : Individual + Member (plus de table Person).
 * Les vues sont recréées avec JOIN Member + Individual.
 */
final class V85ToV86Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec('PRAGMA foreign_keys = OFF');

        $this->dropViews($pdo);

        $this->migrateSurvey($pdo);
        $this->migrateDesignVote($pdo);
        $this->migrateDesign($pdo);
        $this->migrateEvent($pdo);
        $this->migrateEventType($pdo);
        $this->migrateGroup($pdo);
        $this->migrateNeed($pdo);
        $this->migrateMenuItem($pdo);
        $this->migrateMessage($pdo);
        $this->migrateMember($pdo);
        $this->migrateArticle($pdo);
        $this->migrateExercise($pdo);
        $this->migrateSharedFile($pdo);
        $this->migrateKaraokeSession($pdo);
        $this->migrateKaraokeClient($pdo);
        $this->migrateLoanItem($pdo);
        $this->migrateOrder($pdo);
        $this->migrateMetadata($pdo);

        $this->recreateViews($pdo);

        $pdo->exec('PRAGMA foreign_keys = ON');

        return 86;
    }

    private function dropViews(PDO $pdo): void
    {
        foreach (['article_list_view', 'exercise_list_view', 'public_article_list_view'] as $view) {
            $pdo->exec("DROP VIEW IF EXISTS \"$view\"");
        }
    }

    // -------------------------------------------------------------------------
    // Survey : ClosingDate DATE→TEXT + SurveyVisibility
    // -------------------------------------------------------------------------
    private function migrateSurvey(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Survey_new" (
                "Id"          INTEGER PRIMARY KEY,
                "Question"    TEXT    NOT NULL,
                "Options"     TEXT    NOT NULL,
                "IdArticle"   INTEGER NOT NULL,
                "ClosingDate" TEXT    NOT NULL DEFAULT (date('now', '+10 days')),
                "Visibility"  TEXT    NOT NULL DEFAULT 'redactor'
                    CHECK ("Visibility" IN (
                        'all', 'allAfterClosing', 'redactor',
                        'voters', 'votersAfterClosing'
                    )),
                FOREIGN KEY ("IdArticle") REFERENCES "Article" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "Survey_new" SELECT * FROM "Survey"');
        $pdo->exec('DROP TABLE "Survey"');
        $pdo->exec('ALTER TABLE "Survey_new" RENAME TO "Survey"');
    }

    // -------------------------------------------------------------------------
    // DesignVote : Vote INTEGER→TEXT + DesignVote enum
    // -------------------------------------------------------------------------
    private function migrateDesignVote(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "DesignVote_new" (
                "Id"         INTEGER PRIMARY KEY,
                "IdDesign"   INTEGER NOT NULL,
                "IdPerson"   INTEGER NOT NULL,
                "Vote"       TEXT    NOT NULL DEFAULT 'voteNeutral'
                    CHECK ("Vote" IN ('voteUp', 'voteDown', 'voteNeutral')),
                "LastUpdate" TEXT    NOT NULL DEFAULT current_timestamp,
                FOREIGN KEY ("IdDesign") REFERENCES "Design" ("Id"),
                FOREIGN KEY ("IdPerson") REFERENCES "Member" ("Id")
            )
        SQL);
        $pdo->exec(<<<'SQL'
            INSERT INTO "DesignVote_new" ("Id", "IdDesign", "IdPerson", "Vote", "LastUpdate")
            SELECT
                "Id",
                "IdDesign",
                "IdPerson",
                CASE
                    WHEN typeof("Vote") = 'text' THEN "Vote"
                    WHEN "Vote" = 1  THEN 'voteUp'
                    WHEN "Vote" = -1 THEN 'voteDown'
                    ELSE 'voteNeutral'
                END,
                "LastUpdate"
            FROM "DesignVote"
        SQL);
        $pdo->exec('DROP TABLE "DesignVote"');
        $pdo->exec('ALTER TABLE "DesignVote_new" RENAME TO "DesignVote"');
    }

    // -------------------------------------------------------------------------
    // Design : DesignStatus + OnlyForMembers
    // -------------------------------------------------------------------------
    private function migrateDesign(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Design_new" (
                "Id"             INTEGER PRIMARY KEY,
                "IdPerson"       INTEGER NOT NULL,
                "IdGroup"        INTEGER DEFAULT NULL,
                "OnlyForMembers" INTEGER NOT NULL DEFAULT 1
                    CHECK ("OnlyForMembers" IN (0, 1)),
                "Name"           TEXT,
                "Detail"         TEXT,
                "NavBar"         TEXT,
                "Status"         TEXT    NOT NULL DEFAULT 'UnderReview'
                    CHECK ("Status" IN ('UnderReview', 'Approved', 'Rejected')),
                "LastUpdate"     TEXT    NOT NULL DEFAULT current_timestamp,
                FOREIGN KEY ("IdPerson") REFERENCES "Member" ("Id"),
                FOREIGN KEY ("IdGroup")  REFERENCES "Group" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "Design_new" SELECT * FROM "Design"');
        $pdo->exec('DROP TABLE "Design"');
        $pdo->exec('ALTER TABLE "Design_new" RENAME TO "Design"');
    }

    // -------------------------------------------------------------------------
    // Event : EventAudience + Canceled
    // -------------------------------------------------------------------------
    private function migrateEvent(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Event_new" (
                "Id"              INTEGER PRIMARY KEY,
                "Summary"         TEXT    NOT NULL,
                "Description"     TEXT    NOT NULL,
                "Location"        TEXT    NOT NULL,
                "StartTime"       TEXT    NOT NULL,
                "Duration"        INTEGER NOT NULL DEFAULT 3600,
                "IdEventType"     INTEGER NOT NULL,
                "CreatedBy"       INTEGER NOT NULL,
                "MaxParticipants" INTEGER NOT NULL DEFAULT 0,
                "Audience"        TEXT    NOT NULL DEFAULT 'ClubMembersOnly'
                    CHECK ("Audience" IN ('ClubMembersOnly', 'Guest', 'All')),
                "LastUpdate"      TEXT    NOT NULL DEFAULT current_timestamp,
                "Canceled"        INTEGER NOT NULL DEFAULT 0
                    CHECK ("Canceled" IN (0, 1)),
                FOREIGN KEY ("CreatedBy")   REFERENCES "Member" ("Id"),
                FOREIGN KEY ("IdEventType") REFERENCES "EventType" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "Event_new" SELECT * FROM "Event"');
        $pdo->exec('DROP TABLE "Event"');
        $pdo->exec('ALTER TABLE "Event_new" RENAME TO "Event"');
    }

    // -------------------------------------------------------------------------
    // EventType : Inactivated
    // -------------------------------------------------------------------------
    private function migrateEventType(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "EventType_new" (
                "Id"          INTEGER PRIMARY KEY,
                "Name"        TEXT    NOT NULL,
                "Inactivated" INTEGER NOT NULL DEFAULT 0
                    CHECK ("Inactivated" IN (0, 1)),
                "IdGroup"     INTEGER DEFAULT NULL,
                FOREIGN KEY ("IdGroup") REFERENCES "Group" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "EventType_new" SELECT * FROM "EventType"');
        $pdo->exec('DROP TABLE "EventType"');
        $pdo->exec('ALTER TABLE "EventType_new" RENAME TO "EventType"');
    }

    // -------------------------------------------------------------------------
    // Group : Inactivated + SelfRegistration
    // -------------------------------------------------------------------------
    private function migrateGroup(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Group_new" (
                "Id"               INTEGER PRIMARY KEY,
                "Name"             TEXT    NOT NULL,
                "Inactivated"      INTEGER NOT NULL DEFAULT 0
                    CHECK ("Inactivated" IN (0, 1)),
                "SelfRegistration" INTEGER NOT NULL DEFAULT 0
                    CHECK ("SelfRegistration" IN (0, 1))
            )
        SQL);
        $pdo->exec('INSERT INTO "Group_new" SELECT * FROM "Group"');
        $pdo->exec('DROP TABLE "Group"');
        $pdo->exec('ALTER TABLE "Group_new" RENAME TO "Group"');
    }

    // -------------------------------------------------------------------------
    // Need : ParticipantDependent
    // -------------------------------------------------------------------------
    private function migrateNeed(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Need_new" (
                "Id"                   INTEGER PRIMARY KEY,
                "Label"                TEXT    NOT NULL,
                "Name"                 TEXT    NOT NULL,
                "ParticipantDependent" INTEGER NOT NULL DEFAULT 0
                    CHECK ("ParticipantDependent" IN (0, 1)),
                "IdNeedType"           INTEGER NOT NULL,
                FOREIGN KEY ("IdNeedType") REFERENCES "NeedType" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "Need_new" SELECT * FROM "Need"');
        $pdo->exec('DROP TABLE "Need"');
        $pdo->exec('ALTER TABLE "Need_new" RENAME TO "Need"');
    }

    // -------------------------------------------------------------------------
    // MenuItem : ForMembers / ForContacts / ForAnonymous
    // -------------------------------------------------------------------------
    private function migrateMenuItem(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "MenuItem_new" (
                "Id"           INTEGER PRIMARY KEY,
                "What"         TEXT    NOT NULL
                    CHECK ("What" IN ('navbar', 'sidebar')),
                "Type"         TEXT    NOT NULL
                    CHECK ("Type" IN ('heading', 'link', 'divider', 'submenu')),
                "Label"        TEXT,
                "Icon"         TEXT,
                "Url"          TEXT,
                "ParentId"     INTEGER,
                "Position"     INTEGER NOT NULL DEFAULT 1,
                "IdGroup"      INTEGER DEFAULT NULL,
                "ForMembers"   INTEGER NOT NULL DEFAULT 0
                    CHECK ("ForMembers" IN (0, 1)),
                "ForContacts"  INTEGER NOT NULL DEFAULT 0
                    CHECK ("ForContacts" IN (0, 1)),
                "ForAnonymous" INTEGER NOT NULL DEFAULT 0
                    CHECK ("ForAnonymous" IN (0, 1)),
                FOREIGN KEY ("ParentId") REFERENCES "MenuItem" ("Id") ON DELETE CASCADE,
                FOREIGN KEY ("IdGroup")  REFERENCES "Group" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "MenuItem_new" SELECT * FROM "MenuItem"');
        $pdo->exec('DROP TABLE "MenuItem"');
        $pdo->exec('ALTER TABLE "MenuItem_new" RENAME TO "MenuItem"');
    }

    // -------------------------------------------------------------------------
    // Message : From
    // -------------------------------------------------------------------------
    private function migrateMessage(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Message_new" (
                "Id"         INTEGER PRIMARY KEY,
                "EventId"    INTEGER,
                "PersonId"   INTEGER NOT NULL,
                "Text"       TEXT    NOT NULL,
                "LastUpdate" TEXT    NOT NULL DEFAULT current_timestamp,
                "From"       TEXT    NOT NULL DEFAULT 'User'
                    CHECK ("From" IN ('User', 'Webapp')),
                "ArticleId"  INTEGER,
                "GroupId"    INTEGER,
                "ImagePath"  TEXT,
                FOREIGN KEY ("PersonId")  REFERENCES "Member" ("Id"),
                FOREIGN KEY ("EventId")   REFERENCES "Event" ("Id"),
                FOREIGN KEY ("ArticleId") REFERENCES "Article" ("Id"),
                FOREIGN KEY ("GroupId")   REFERENCES "Group" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "Message_new" SELECT * FROM "Message"');
        $pdo->exec('DROP TABLE "Message"');
        $pdo->exec('ALTER TABLE "Message_new" RENAME TO "Message"');
    }

    // -------------------------------------------------------------------------
    // Member : YesNo (UseGravatar) + booléens restants
    //          (Imported, Inactivated, InPresentationDirectory, Show* déjà
    //           partiellement CHECK en V81 — on les réaffirme tous)
    // -------------------------------------------------------------------------
    private function migrateMember(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
        CREATE TABLE "Member_new" (
            "Id"                                  INTEGER PRIMARY KEY,
            "Password"                            TEXT,
            "Token"                               TEXT,
            "TokenCreatedAt"                      TEXT,
            "UseGravatar"                         TEXT    NOT NULL DEFAULT 'no'
                CHECK ("UseGravatar" IN ('yes', 'no')),
            "Availabilities"                      NUMERIC,
            "Preferences"                         TEXT,
            "Notifications"                       TEXT,
            "Imported"                            INTEGER NOT NULL DEFAULT 0
                CHECK ("Imported" IN (0, 1)),
            "Inactivated"                         INTEGER NOT NULL DEFAULT 0
                CHECK ("Inactivated" IN (0, 1)),
            "Presentation"                        TEXT,
            "PresentationLastUpdate"              TEXT    NOT NULL DEFAULT current_timestamp,
            "InPresentationDirectory"             INTEGER NOT NULL DEFAULT 0
                CHECK ("InPresentationDirectory" IN (0, 1)),
            "Location"                            TEXT,
            "LastSignIn"                          TEXT,
            "LastSignOut"                         TEXT,
            "Notepad"                             TEXT,
            "Alert"                               TEXT,
            "ShowPhoneInPresentationDirectory"    INTEGER NOT NULL DEFAULT 0
                CHECK ("ShowPhoneInPresentationDirectory" IN (0, 1)),
            "ShowEmailInPresentationDirectory"    INTEGER NOT NULL DEFAULT 0
                CHECK ("ShowEmailInPresentationDirectory" IN (0, 1)),
            "MemberInfo"                          TEXT    DEFAULT '',
            "MyPublicDataInPresentationDirectory" TEXT,
            "LastPageView"                        TEXT
                CHECK (
                    "LastPageView" IS NULL
                    OR (
                        "LastPageView" LIKE '____-__-__ __:__:__'
                        AND datetime("LastPageView") IS NOT NULL
                    )
                ),
            FOREIGN KEY ("Id") REFERENCES "Individual" ("Id") ON DELETE CASCADE
        )
    SQL);

        // Normalise UseGravatar avant d'appliquer le CHECK
        $pdo->exec(<<<'SQL'
        INSERT INTO "Member_new" (
            "Id", "Password", "Token", "TokenCreatedAt", "UseGravatar",
            "Availabilities", "Preferences", "Notifications",
            "Imported", "Inactivated", "Presentation", "PresentationLastUpdate",
            "InPresentationDirectory", "Location", "LastSignIn", "LastSignOut",
            "Notepad", "Alert",
            "ShowPhoneInPresentationDirectory", "ShowEmailInPresentationDirectory",
            "MemberInfo", "MyPublicDataInPresentationDirectory", "LastPageView"
        )
        SELECT
            "Id", "Password", "Token", "TokenCreatedAt",
            CASE
                WHEN lower(trim(coalesce("UseGravatar", ''))) IN ('yes', '1', 'true', 'on')
                    THEN 'yes'
                ELSE 'no'
            END,
            "Availabilities", "Preferences", "Notifications",
            "Imported", "Inactivated", "Presentation", "PresentationLastUpdate",
            "InPresentationDirectory", "Location", "LastSignIn", "LastSignOut",
            "Notepad", "Alert",
            "ShowPhoneInPresentationDirectory", "ShowEmailInPresentationDirectory",
            "MemberInfo", "MyPublicDataInPresentationDirectory", "LastPageView"
        FROM "Member"
    SQL);

        $pdo->exec('DROP TABLE "Member"');
        $pdo->exec('ALTER TABLE "Member_new" RENAME TO "Member"');
    }

    // -------------------------------------------------------------------------
    // Article : OnlyForMembers (+ Language déjà ajouté en V81)
    // -------------------------------------------------------------------------
    private function migrateArticle(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Article_new" (
                "Id"             INTEGER PRIMARY KEY,
                "Title"          TEXT    NOT NULL,
                "Content"        TEXT    NOT NULL,
                "CreatedBy"      INTEGER NOT NULL,
                "Timestamp"      TEXT    NOT NULL DEFAULT current_timestamp,
                "PublishedBy"    INTEGER DEFAULT NULL,
                "IdGroup"        INTEGER DEFAULT NULL,
                "LastUpdate"     TEXT    NOT NULL DEFAULT current_timestamp,
                "OnlyForMembers" INTEGER NOT NULL DEFAULT 1
                    CHECK ("OnlyForMembers" IN (0, 1)),
                "Language"       TEXT    NOT NULL DEFAULT 'fr_FR',
                FOREIGN KEY ("CreatedBy")   REFERENCES "Member" ("Id"),
                FOREIGN KEY ("PublishedBy") REFERENCES "Member" ("Id"),
                FOREIGN KEY ("IdGroup")     REFERENCES "Group" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "Article_new" SELECT * FROM "Article"');
        $pdo->exec('DROP TABLE "Article"');
        $pdo->exec('ALTER TABLE "Article_new" RENAME TO "Article"');
    }

    // -------------------------------------------------------------------------
    // Exercise / SharedFile : OnlyForMembers
    // -------------------------------------------------------------------------
    private function migrateExercise(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Exercise_new" (
                "Id"             INTEGER PRIMARY KEY,
                "Title"          TEXT    NOT NULL,
                "Detail"         TEXT    NOT NULL,
                "Content"        TEXT    NOT NULL,
                "CreatedBy"      INTEGER NOT NULL,
                "LastUpdate"     TEXT    NOT NULL DEFAULT current_timestamp,
                "IdGroup"        INTEGER,
                "OnlyForMembers" INTEGER NOT NULL DEFAULT 1
                    CHECK ("OnlyForMembers" IN (0, 1)),
                FOREIGN KEY ("CreatedBy") REFERENCES "Member" ("Id"),
                FOREIGN KEY ("IdGroup")   REFERENCES "Group" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "Exercise_new" SELECT * FROM "Exercise"');
        $pdo->exec('DROP TABLE "Exercise"');
        $pdo->exec('ALTER TABLE "Exercise_new" RENAME TO "Exercise"');
    }

    private function migrateSharedFile(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "SharedFile_new" (
                "Id"             INTEGER PRIMARY KEY,
                "Item"           TEXT    NOT NULL,
                "IdGroup"        INTEGER,
                "OnlyForMembers" INTEGER NOT NULL DEFAULT 1
                    CHECK ("OnlyForMembers" IN (0, 1)),
                "Token"          TEXT,
                FOREIGN KEY ("IdGroup") REFERENCES "Group" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "SharedFile_new" SELECT * FROM "SharedFile"');
        $pdo->exec('DROP TABLE "SharedFile"');
        $pdo->exec('ALTER TABLE "SharedFile_new" RENAME TO "SharedFile"');
    }

    // -------------------------------------------------------------------------
    // KaraokeSession : KaraokeSessionStatus
    // KaraokeClient  : IsHost
    // -------------------------------------------------------------------------
    private function migrateKaraokeSession(PDO $pdo): void
    {
        // Colonne SongId (schéma courant) — pas SongName
        $pdo->exec(<<<'SQL'
            CREATE TABLE "KaraokeSession_new" (
                "Id"             INTEGER PRIMARY KEY,
                "SessionId"      TEXT    NOT NULL UNIQUE,
                "SongId"         TEXT    NOT NULL,
                "Status"         TEXT    DEFAULT 'waiting'
                    CHECK ("Status" IN ('waiting', 'countdown', 'idle')),
                "CountdownStart" INTEGER,
                "PlayStartTime"  INTEGER,
                "CurrentTime"    REAL    DEFAULT 0,
                "CreatedAt"      TEXT    NOT NULL DEFAULT current_timestamp,
                "UpdatedAt"      TEXT    NOT NULL DEFAULT current_timestamp
            )
        SQL);
        $pdo->exec('INSERT INTO "KaraokeSession_new" SELECT * FROM "KaraokeSession"');
        $pdo->exec('DROP TABLE "KaraokeSession"');
        $pdo->exec('ALTER TABLE "KaraokeSession_new" RENAME TO "KaraokeSession"');
    }

    private function migrateKaraokeClient(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "KaraokeClient_new" (
                "Id"               INTEGER PRIMARY KEY,
                "ClientId"         TEXT    NOT NULL UNIQUE,
                "IdKaraokeSession" INTEGER NOT NULL,
                "IsHost"           INTEGER DEFAULT 0
                    CHECK ("IsHost" IN (0, 1)),
                "LastHeartbeat"    TEXT    NOT NULL DEFAULT current_timestamp,
                "CreatedAt"        TEXT    NOT NULL DEFAULT current_timestamp,
                FOREIGN KEY ("IdKaraokeSession") REFERENCES "KaraokeSession" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "KaraokeClient_new" SELECT * FROM "KaraokeClient"');
        $pdo->exec('DROP TABLE "KaraokeClient"');
        $pdo->exec('ALTER TABLE "KaraokeClient_new" RENAME TO "KaraokeClient"');
    }

    // -------------------------------------------------------------------------
    // LoanItem : IsActive (Type déjà CHECK)
    // -------------------------------------------------------------------------
    private function migrateLoanItem(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "LoanItem_new" (
                "Id"          INTEGER PRIMARY KEY AUTOINCREMENT,
                "Name"        TEXT    NOT NULL,
                "Description" TEXT    NOT NULL DEFAULT '',
                "Type"        TEXT    NOT NULL DEFAULT 'both'
                    CHECK ("Type" IN ('loan', 'reservation', 'both')),
                "Quantity"    INTEGER NOT NULL DEFAULT 1,
                "IsActive"    INTEGER NOT NULL DEFAULT 1
                    CHECK ("IsActive" IN (0, 1)),
                "CreatedAt"   TEXT    NOT NULL DEFAULT (datetime('now')),
                "UpdatedAt"   TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);
        $pdo->exec('INSERT INTO "LoanItem_new" SELECT * FROM "LoanItem"');
        $pdo->exec('DROP TABLE "LoanItem"');
        $pdo->exec('ALTER TABLE "LoanItem_new" RENAME TO "LoanItem"');
    }

    // -------------------------------------------------------------------------
    // Order : OrderVisibility
    // -------------------------------------------------------------------------
    private function migrateOrder(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE "Order_new" (
                "Id"          INTEGER PRIMARY KEY,
                "Question"    TEXT    NOT NULL,
                "Options"     TEXT    NOT NULL,
                "IdArticle"   INTEGER NOT NULL,
                "ClosingDate" TEXT    NOT NULL,
                "Visibility"  TEXT    NOT NULL
                    CHECK ("Visibility" IN (
                        'all', 'allAfterClosing', 'redactor',
                        'orderers', 'orderersAfterClosing'
                    )),
                FOREIGN KEY ("IdArticle") REFERENCES "Article" ("Id")
            )
        SQL);
        $pdo->exec('INSERT INTO "Order_new" SELECT * FROM "Order"');
        $pdo->exec('DROP TABLE "Order"');
        $pdo->exec('ALTER TABLE "Order_new" RENAME TO "Order"');
    }

    // -------------------------------------------------------------------------
    // Metadata : SiteUnderMaintenance + ThisIsTestSite
    // -------------------------------------------------------------------------
    private function migrateMetadata(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
        CREATE TABLE "Metadata_new" (
            "Id"                              INTEGER PRIMARY KEY,
            "ApplicationName"                 TEXT    NOT NULL,
            "DatabaseVersion"                 INTEGER NOT NULL,
            "SiteUnderMaintenance"            INTEGER NOT NULL DEFAULT 0
                CHECK ("SiteUnderMaintenance" IN (0, 1)),
            "Compact_lastDate"                TEXT,
            "Compact_everyXdays"              INTEGER NOT NULL DEFAULT 10,
            "Compact_removeOlderThanXmonths"  INTEGER NOT NULL DEFAULT 36,
            "Compact_compactOlderThanXmonths" INTEGER NOT NULL DEFAULT 6,
            "Compact_maxRecords"              INTEGER NOT NULL DEFAULT 1000000,
            "ThisIsProdSiteUrl"               TEXT,
            "ThisIsTestSite"                  INTEGER NOT NULL DEFAULT 0
                CHECK ("ThisIsTestSite" IN (0, 1)),
            "ThisIsForcedLanguage"            TEXT
        )
    SQL);

        $pdo->exec(<<<'SQL'
        INSERT INTO "Metadata_new" (
            "Id",
            "ApplicationName",
            "DatabaseVersion",
            "SiteUnderMaintenance",
            "Compact_lastDate",
            "Compact_everyXdays",
            "Compact_removeOlderThanXmonths",
            "Compact_compactOlderThanXmonths",
            "Compact_maxRecords",
            "ThisIsProdSiteUrl",
            "ThisIsTestSite",
            "ThisIsForcedLanguage"
        )
        SELECT
            "Id",
            "ApplicationName",
            "DatabaseVersion",
            COALESCE("SiteUnderMaintenance", 0),
            "Compact_lastDate",
            COALESCE("Compact_everyXdays", 10),
            COALESCE("Compact_removeOlderThanXmonths", 36),
            COALESCE("Compact_compactOlderThanXmonths", 6),
            COALESCE("Compact_maxRecords", 1000000),
            "ThisIsProdSiteUrl",
            COALESCE("ThisIsTestSite", 0),
            "ThisIsForcedLanguage"
        FROM "Metadata"
    SQL);

        $pdo->exec('DROP TABLE "Metadata"');
        $pdo->exec('ALTER TABLE "Metadata_new" RENAME TO "Metadata"');
    }

    // -------------------------------------------------------------------------
    // Vues post-V81 : Member + Individual
    // -------------------------------------------------------------------------
    private function recreateViews(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE VIEW article_list_view AS
            SELECT
                Article.Id,
                Article.CreatedBy,
                Article.Title,
                Article.LastUpdate,
                Article.PublishedBy,
                Article.OnlyForMembers,
                Article.IdGroup,
                Article.Content,
                (
                    SELECT COUNT(*)
                    FROM Message
                    WHERE Message.ArticleId = Article.Id
                ) AS Messages,
                CASE
                    WHEN Article.PublishedBy IS NULL THEN 'non'
                    ELSE 'oui'
                END AS Published,
                CASE
                    WHEN Article.OnlyForMembers = 1 THEN 'oui'
                    ELSE 'non'
                END AS ForMembers,
                CASE
                    WHEN Survey.IdArticle IS NULL THEN 'non'
                    ELSE 'oui'
                END AS Pool,
                CASE
                    WHEN Survey.IdArticle IS NULL THEN ''
                    ELSE (
                        CASE
                            WHEN Survey.ClosingDate < CURRENT_DATE THEN 'clos'
                            ELSE strftime('%d/%m/%Y', Survey.ClosingDate)
                        END
                        || ' (' || COALESCE((SELECT COUNT(*) FROM Reply WHERE Reply.IdSurvey = Survey.Id), 0) || ') '
                        || CASE Survey.Visibility
                            WHEN 'all' THEN '👁️‍🗨️👥'
                            WHEN 'allAfterClosing' THEN '👁️‍🗨️👥📅'
                            WHEN 'voters' THEN '👁️‍🗨️🗳️'
                            WHEN 'votersAfterClosing' THEN '👁️‍🗨️🗳️📅'
                            WHEN 'redactor' THEN '👁️‍🗨️📝'
                            ELSE ''
                        END
                    )
                END AS PoolDetail,
                CASE
                    WHEN Individual.NickName != '' AND Individual.NickName IS NOT NULL
                    THEN Individual.FirstName || ' ' || Individual.LastName || ' (' || Individual.NickName || ')'
                    ELSE Individual.FirstName || ' ' || Individual.LastName
                END AS PersonName,
                "Group".Name AS GroupName,
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM MenuItem
                        WHERE MenuItem.Url = '/menu/show/article/' || Article.Id
                    ) THEN 'oui'
                    ELSE 'non'
                END AS Menu
            FROM Article
            INNER JOIN Member ON Article.CreatedBy = Member.Id
            INNER JOIN Individual ON Member.Id = Individual.Id
            LEFT JOIN Survey ON Article.Id = Survey.IdArticle
            LEFT JOIN "Group" ON "Group".Id = Article.IdGroup
        SQL);

        $pdo->exec(<<<'SQL'
            CREATE VIEW exercise_list_view AS
            SELECT
                Exercise.Id,
                Exercise.CreatedBy,
                Exercise.Title,
                Exercise.Detail,
                Exercise.LastUpdate,
                Exercise.CreatedBy,
                Exercise.OnlyForMembers,
                Exercise.IdGroup,
                CASE
                    WHEN Exercise.OnlyForMembers = 1 THEN 'oui'
                    ELSE 'non'
                END AS ForMembers,
                CASE
                    WHEN Individual.NickName != '' AND Individual.NickName IS NOT NULL
                    THEN Individual.FirstName || ' ' || Individual.LastName || ' (' || Individual.NickName || ')'
                    ELSE Individual.FirstName || ' ' || Individual.LastName
                END AS PersonName,
                "Group".Name AS GroupName
            FROM Exercise
            INNER JOIN Member ON Exercise.CreatedBy = Member.Id
            INNER JOIN Individual ON Member.Id = Individual.Id
            LEFT JOIN "Group" ON "Group".Id = Exercise.IdGroup
        SQL);

        $pdo->exec(<<<'SQL'
            CREATE VIEW public_article_list_view AS
            SELECT
                Id,
                LastUpdate,
                Title,
                CASE
                    WHEN Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name = 'Home_FeaturedArticleId' AND Value != '0'
                    ) THEN 'Home_Featured'
                    WHEN Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name = 'Home_FooterArticleId' AND Value != '0'
                    ) THEN 'Home_Footer'
                    WHEN Id IN (
                        SELECT CAST(REPLACE(Url, '/menu/show/article/', '') AS INTEGER)
                        FROM MenuItem
                        WHERE ForAnonymous = 1
                        AND Url LIKE '/menu/show/article/%'
                    ) THEN 'Menu'
                    ELSE 'Public'
                END AS ReferenceSource
            FROM Article
            WHERE
                (
                    (IdGroup IS NULL AND OnlyForMembers = 0 AND PublishedBy IS NOT NULL)
                    OR Id IN (
                        SELECT CAST(REPLACE(Url, '/menu/show/article/', '') AS INTEGER)
                        FROM MenuItem
                        WHERE ForAnonymous = 1
                        AND Url LIKE '/menu/show/article/%'
                    )
                    OR Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name IN ('Home_FeaturedArticleId', 'Home_FooterArticleId')
                        AND Value != '0'
                    )
                )
            ORDER BY LastUpdate DESC
        SQL);
    }
}
