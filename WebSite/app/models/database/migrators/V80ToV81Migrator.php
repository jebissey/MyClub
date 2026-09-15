<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use RuntimeException;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

/**
 * Migrates from schema (Person + Contact) to (Individual + Member + Contact subtypes).
 * Also adds Article.Language.
 *
 * Corrected for current schema (extra Person fields, Loan*, Membership, Exercise, Message.ImagePath,
 * column orders, views, FK handling).
 */
final class V80ToV81Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        // Disable FK checks for the duration of structural changes
        $pdo->exec('PRAGMA foreign_keys = OFF');

        // 1. Add Language column to Article (idempotent-ish; will be present in rebuild)
        try {
            $pdo->exec("ALTER TABLE Article ADD COLUMN Language TEXT NOT NULL DEFAULT 'fr_FR'");
        } catch (\Throwable $e) {
            // Column may already exist in some test runs
        }

        $this->dropViews($pdo);

        $this->createIndividualTable($pdo);
        $this->createMemberTable($pdo);
        $this->migratePersonData($pdo);

        $contactIdMap = $this->migrateContactToIndividual($pdo);
        $this->createContactSubtypeTable($pdo);
        $this->populateContactSubtype($pdo, $contactIdMap);

        $this->createNewParticipantTable($pdo);
        $this->migrateParticipants($pdo, $contactIdMap);
        $this->migrateGuests($pdo, $contactIdMap);

        $this->rebuildMemberGroupTable($pdo);
        $this->rebuildForeignKeysToMember($pdo);
        $this->dropLegacyTables($pdo);
        $this->finalizeTableNames($pdo);

        $this->recreateViews($pdo);

        $pdo->exec('PRAGMA foreign_keys = ON');

        return 81;
    }

    private function dropViews(PDO $pdo): void
    {
        foreach (['article_list_view', 'exercise_list_view', 'public_article_list_view'] as $view) {
            $pdo->exec("DROP VIEW IF EXISTS \"$view\"");
        }
    }

    private function createIndividualTable(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE "Individual" (
                "Id"          INTEGER PRIMARY KEY,
                "Type"        TEXT    NOT NULL CHECK(Type IN ('Member', 'Contact')),
                "Email"       TEXT    NOT NULL UNIQUE,
                "FirstName"   TEXT    NOT NULL,
                "LastName"    TEXT    NOT NULL DEFAULT '',
                "NickName"    TEXT,
                "Avatar"      TEXT,
                "Phone"       TEXT,
                "CreatedAt"   TEXT    NOT NULL DEFAULT current_timestamp
            )
        SQL);
    }

    private function createMemberTable(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE "Member" (
                "Id"                               INTEGER PRIMARY KEY,
                "Password"                         TEXT,
                "Token"                            TEXT,
                "TokenCreatedAt"                   TEXT,
                "UseGravatar"                      TEXT    NOT NULL DEFAULT 'no',
                "Availabilities"                   NUMERIC,
                "Preferences"                      TEXT,
                "Notifications"                    TEXT,
                "Imported"                         INTEGER NOT NULL DEFAULT 0 CHECK(Imported IN (0, 1)),
                "Inactivated"                      INTEGER NOT NULL DEFAULT 0 CHECK(Inactivated IN (0, 1)),
                "Presentation"                     TEXT,
                "PresentationLastUpdate"           TEXT    NOT NULL DEFAULT current_timestamp,
                "InPresentationDirectory"          INTEGER NOT NULL DEFAULT 0 CHECK(InPresentationDirectory IN (0, 1)),
                "Location"                         TEXT,
                "LastSignIn"                       TEXT,
                "LastSignOut"                      TEXT,
                "Notepad"                          TEXT,
                "Alert"                            TEXT,
                "ShowPhoneInPresentationDirectory" INTEGER NOT NULL DEFAULT 0 CHECK(ShowPhoneInPresentationDirectory IN (0, 1)),
                "ShowEmailInPresentationDirectory" INTEGER NOT NULL DEFAULT 0 CHECK(ShowEmailInPresentationDirectory IN (0, 1)),
                "MemberInfo"                       TEXT    DEFAULT '',
                "MyPublicDataInPresentationDirectory" TEXT,
                "LastPageView"                     TEXT,
                FOREIGN KEY("Id") REFERENCES "Individual"("Id") ON DELETE CASCADE
            )
        SQL);
    }

    private function createContactSubtypeTable(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE "Contact_new" (
                "Id"             INTEGER PRIMARY KEY,
                "Token"          TEXT,
                "TokenCreatedAt" TEXT,
                FOREIGN KEY("Id") REFERENCES "Individual"("Id") ON DELETE CASCADE
            )
        SQL);
    }

    private function createNewParticipantTable(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE "Participant_new" (
                "Id"           INTEGER PRIMARY KEY,
                "IdEvent"      INTEGER NOT NULL,
                "IdIndividual" INTEGER NOT NULL,
                "InvitedBy"    INTEGER DEFAULT NULL,
                "InvitedAt"    TEXT    DEFAULT NULL,
                FOREIGN KEY("IdEvent")      REFERENCES "Event"("Id"),
                FOREIGN KEY("IdIndividual") REFERENCES "Individual"("Id"),
                FOREIGN KEY("InvitedBy")    REFERENCES "Member"("Id"),
                UNIQUE("IdEvent", "IdIndividual")
            )
        SQL);
    }

    private function migratePersonData(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            INSERT INTO Individual (Id, Type, Email, FirstName, LastName, NickName, Avatar, Phone)
            SELECT Id, 'Member', Email, FirstName, LastName, NickName, Avatar, Phone
            FROM Person
        SQL);

        $pdo->exec(<<<SQL
            INSERT INTO Member (
                Id, Password, Token, TokenCreatedAt, UseGravatar,
                Availabilities, Preferences, Notifications, Imported, Inactivated,
                Presentation, PresentationLastUpdate, InPresentationDirectory,
                Location, LastSignIn, LastSignOut, Notepad, Alert,
                ShowPhoneInPresentationDirectory, ShowEmailInPresentationDirectory,
                MemberInfo, MyPublicDataInPresentationDirectory, LastPageView
            )
            SELECT
                Id, Password, Token, TokenCreatedAt, UseGravatar,
                Availabilities, Preferences, Notifications, Imported, Inactivated,
                Presentation, PresentationLastUpdate, InPresentationDirectory,
                Location, LastSignIn, LastSignOut, Notepad, Alert,
                ShowPhoneInPresentationDirectory, ShowEmailInPresentationDirectory,
                MemberInfo, MyPublicDataInPresentationDirectory, LastPageView
            FROM Person
        SQL);
    }

    /**
     * @return array<int, int>  Map [old_contact_id => new_individual_id]
     */
    private function migrateContactToIndividual(PDO $pdo): array
    {
        /** @var list<object{Id: int, Email: string, NickName: string|null}> $contacts */
        $contacts = $this->queryAll($pdo, "SELECT Id, Email, NickName FROM Contact");

        $insert = $pdo->prepare(<<<SQL
            INSERT INTO Individual (Type, Email, FirstName, LastName, NickName)
            VALUES ('Contact', :email, :firstName, '', :nickName)
            ON CONFLICT(Email) DO NOTHING
        SQL);
        $resolve = $pdo->prepare("SELECT Id FROM Individual WHERE Email = :email");
        $map = [];
        foreach ($contacts as $contact) {
            $firstName = $contact->NickName !== null && $contact->NickName !== ''
                ? $contact->NickName
                : $contact->Email;
            $insert->execute([
                ':email'     => $contact->Email,
                ':firstName' => $firstName,
                ':nickName'  => $contact->NickName,
            ]);

            $resolve->execute([':email' => $contact->Email]);
            $map[$contact->Id] = (int) $resolve->fetchColumn();
        }

        return $map;
    }

    /**
     * @param array<int, int> $contactIdMap
     */
    private function populateContactSubtype(PDO $pdo, array $contactIdMap): void
    {
        /** @var list<object{Id: int, Token: string|null, TokenCreatedAt: string|null}> $contacts */
        $contacts = $this->queryAll($pdo, "SELECT Id, Token, TokenCreatedAt FROM Contact");

        $stmt = $pdo->prepare(<<<SQL
            INSERT OR IGNORE INTO Contact_new (Id, Token, TokenCreatedAt)
            VALUES (:id, :token, :tokenCreatedAt)
        SQL);

        foreach ($contacts as $contact) {
            $newId = $contactIdMap[$contact->Id] ?? null;
            if ($newId === null) {
                continue;
            }
            $stmt->execute([
                ':id'             => $newId,
                ':token'          => $contact->Token,
                ':tokenCreatedAt' => $contact->TokenCreatedAt,
            ]);
        }
    }

    /**
     * @param array<int, int> $contactIdMap
     */
    private function migrateParticipants(PDO $pdo, array $contactIdMap): void
    {
        /** @var list<object{Id: int, IdEvent: int, IdPerson: int|null, IdContact: int|null}> $rows */
        $rows = $this->queryAll($pdo, "SELECT Id, IdEvent, IdPerson, IdContact FROM Participant");

        $stmt = $pdo->prepare(<<<SQL
            INSERT OR IGNORE INTO Participant_new (Id, IdEvent, IdIndividual)
            VALUES (:id, :idEvent, :idIndividual)
        SQL);

        foreach ($rows as $row) {
            if ($row->IdPerson !== null) {
                $stmt->execute([
                    ':id'           => $row->Id,
                    ':idEvent'      => $row->IdEvent,
                    ':idIndividual' => $row->IdPerson,
                ]);
            } elseif ($row->IdContact !== null) {
                $newId = $contactIdMap[$row->IdContact] ?? null;
                if ($newId === null) {
                    continue;
                }
                $stmt->execute([
                    ':id'           => $row->Id,
                    ':idEvent'      => $row->IdEvent,
                    ':idIndividual' => $newId,
                ]);
            }
        }
    }

    /**
     * @param array<int, int> $contactIdMap
     */
    private function migrateGuests(PDO $pdo, array $contactIdMap): void
    {
        /** @var list<object{IdContact: int, IdEvent: int, InvitedBy: int|null}> $guests */
        $guests = $this->queryAll($pdo, "SELECT IdContact, IdEvent, InvitedBy FROM Guest");

        $insert = $pdo->prepare(<<<SQL
            INSERT INTO Participant_new (IdEvent, IdIndividual, InvitedBy)
            VALUES (:idEvent, :idIndividual, :invitedBy)
            ON CONFLICT(IdEvent, IdIndividual)
            DO UPDATE SET InvitedBy = excluded.InvitedBy
            WHERE Participant_new.InvitedBy IS NULL
        SQL);

        foreach ($guests as $guest) {
            $newId = $contactIdMap[$guest->IdContact] ?? null;
            if ($newId === null) {
                continue;
            }
            $insert->execute([
                ':idEvent'      => $guest->IdEvent,
                ':idIndividual' => $newId,
                ':invitedBy'    => $guest->InvitedBy,
            ]);
        }
    }

    private function rebuildMemberGroupTable(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE "MemberGroup" (
                "Id"       INTEGER PRIMARY KEY,
                "IdMember" INTEGER NOT NULL,
                "IdGroup"  INTEGER NOT NULL,
                FOREIGN KEY("IdMember") REFERENCES "Member"("Id"),
                FOREIGN KEY("IdGroup")  REFERENCES "Group"("Id"),
                UNIQUE("IdMember", "IdGroup")
            )
        SQL);

        $pdo->exec(<<<SQL
            INSERT OR IGNORE INTO MemberGroup (Id, IdMember, IdGroup)
            SELECT Id, IdPerson, IdGroup FROM PersonGroup
        SQL);
    }

    private function rebuildForeignKeysToMember(PDO $pdo): void
    {
        // Each entry: table => [create_sql, list of columns for explicit INSERT SELECT]
        // Column lists match the *current* table after any prior ALTERs (e.g. Article.Language)
        $tables = [
            'Article' => [
                <<<SQL
                CREATE TABLE "Article_new" (
                    "Id"             INTEGER PRIMARY KEY,
                    "Title"          TEXT    NOT NULL,
                    "Content"        TEXT    NOT NULL,
                    "CreatedBy"      INTEGER NOT NULL,
                    "Timestamp"      TEXT    NOT NULL DEFAULT current_timestamp,
                    "PublishedBy"    INTEGER DEFAULT NULL,
                    "IdGroup"        INTEGER DEFAULT NULL,
                    "LastUpdate"     TEXT    NOT NULL DEFAULT current_timestamp,
                    "OnlyForMembers" INTEGER NOT NULL DEFAULT 1,
                    "Language"       TEXT    NOT NULL DEFAULT 'fr_FR',
                    FOREIGN KEY("CreatedBy")   REFERENCES "Member"("Id"),
                    FOREIGN KEY("PublishedBy") REFERENCES "Member"("Id"),
                    FOREIGN KEY("IdGroup")     REFERENCES "Group"("Id")
                )
                SQL,
                ['Id', 'Title', 'Content', 'CreatedBy', 'Timestamp', 'PublishedBy', 'IdGroup', 'LastUpdate', 'OnlyForMembers', 'Language']
            ],
            'Counter' => [
                <<<SQL
                CREATE TABLE "Counter_new" (
                    "Id"        INTEGER PRIMARY KEY,
                    "Name"      TEXT    NOT NULL,
                    "Detail"    TEXT,
                    "Value"     INTEGER NOT NULL,
                    "IdPerson"  INTEGER NOT NULL,
                    "IdGroup"   INTEGER NOT NULL,
                    "Timestamp" TEXT    NOT NULL DEFAULT current_timestamp,
                    FOREIGN KEY("IdPerson") REFERENCES "Member"("Id"),
                    FOREIGN KEY("IdGroup")  REFERENCES "Group"("Id")
                )
                SQL,
                ['Id', 'Name', 'Detail', 'Value', 'IdPerson', 'IdGroup', 'Timestamp']
            ],
            'Design' => [
                <<<SQL
                CREATE TABLE "Design_new" (
                    "Id"             INTEGER PRIMARY KEY,
                    "IdPerson"       INTEGER NOT NULL,
                    "IdGroup"        INTEGER DEFAULT NULL,
                    "OnlyForMembers" INTEGER NOT NULL DEFAULT 1,
                    "Name"           TEXT,
                    "Detail"         TEXT,
                    "NavBar"         TEXT,
                    "Status"         TEXT NOT NULL DEFAULT 'UnderReview',
                    "LastUpdate"     TEXT NOT NULL DEFAULT current_timestamp,
                    FOREIGN KEY("IdPerson") REFERENCES "Member"("Id"),
                    FOREIGN KEY("IdGroup")  REFERENCES "Group"("Id")
                )
                SQL,
                ['Id', 'IdPerson', 'IdGroup', 'OnlyForMembers', 'Name', 'Detail', 'NavBar', 'Status', 'LastUpdate']
            ],
            'DesignVote' => [
                <<<SQL
                CREATE TABLE "DesignVote_new" (
                    "Id"         INTEGER PRIMARY KEY,
                    "IdDesign"   INTEGER NOT NULL,
                    "IdPerson"   INTEGER NOT NULL,
                    "Vote"       INTEGER NOT NULL DEFAULT 0,
                    "LastUpdate" TEXT    NOT NULL DEFAULT current_timestamp,
                    FOREIGN KEY("IdDesign") REFERENCES "Design"("Id"),
                    FOREIGN KEY("IdPerson") REFERENCES "Member"("Id")
                )
                SQL,
                ['Id', 'IdDesign', 'IdPerson', 'Vote', 'LastUpdate']
            ],
            'Event' => [
                <<<SQL
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
                    "Audience"        TEXT    NOT NULL DEFAULT 'ClubMembersOnly',
                    "LastUpdate"      TEXT    NOT NULL DEFAULT current_timestamp,
                    "Canceled"        INTEGER NOT NULL DEFAULT 0,
                    FOREIGN KEY("CreatedBy")   REFERENCES "Member"("Id"),
                    FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id")
                )
                SQL,
                [
                    'Id',
                    'Summary',
                    'Description',
                    'Location',
                    'StartTime',
                    'Duration',
                    'IdEventType',
                    'CreatedBy',
                    'MaxParticipants',
                    'Audience',
                    'LastUpdate',
                    'Canceled'
                ]
            ],
            'Exercise' => [
                <<<SQL
                CREATE TABLE "Exercise_new" (
                    "Id"             INTEGER PRIMARY KEY,
                    "Title"          TEXT    NOT NULL,
                    "Detail"         TEXT    NOT NULL,
                    "Content"        TEXT    NOT NULL,
                    "CreatedBy"      INTEGER NOT NULL,
                    "LastUpdate"     TEXT    NOT NULL DEFAULT current_timestamp,
                    "IdGroup"        INTEGER,
                    "OnlyForMembers" INTEGER NOT NULL DEFAULT 1,
                    FOREIGN KEY("CreatedBy") REFERENCES "Member"("Id"),
                    FOREIGN KEY("IdGroup")   REFERENCES "Group"("Id")
                )
                SQL,
                ['Id', 'Title', 'Detail', 'Content', 'CreatedBy', 'LastUpdate', 'IdGroup', 'OnlyForMembers']
            ],
            'KanbanProject' => [
                <<<SQL
                CREATE TABLE "KanbanProject_new" (
                    "Id"       INTEGER PRIMARY KEY,
                    "Title"    TEXT    NOT NULL,
                    "Detail"   TEXT    NOT NULL,
                    "IdPerson" INTEGER NOT NULL,
                    FOREIGN KEY("IdPerson") REFERENCES "Member"("Id")
                )
                SQL,
                ['Id', 'Title', 'Detail', 'IdPerson']
            ],
            'Message' => [
                <<<SQL
                CREATE TABLE "Message_new" (
                    "Id"         INTEGER PRIMARY KEY,
                    "EventId"    INTEGER,
                    "PersonId"   INTEGER NOT NULL,
                    "Text"       TEXT    NOT NULL,
                    "LastUpdate" TEXT    NOT NULL DEFAULT current_timestamp,
                    "From"       TEXT    NOT NULL DEFAULT 'User',
                    "ArticleId"  INTEGER,
                    "GroupId"    INTEGER,
                    "ImagePath"  TEXT,
                    FOREIGN KEY("PersonId")  REFERENCES "Member"("Id"),
                    FOREIGN KEY("EventId")   REFERENCES "Event"("Id"),
                    FOREIGN KEY("ArticleId") REFERENCES "Article"("Id"),
                    FOREIGN KEY("GroupId")   REFERENCES "Group"("Id")
                )
                SQL,
                ['Id', 'EventId', 'PersonId', 'Text', 'LastUpdate', 'From', 'ArticleId', 'GroupId', 'ImagePath']
            ],
            'OrderReply' => [
                <<<SQL
                CREATE TABLE "OrderReply_new" (
                    "Id"         INTEGER PRIMARY KEY,
                    "IdPerson"   INTEGER NOT NULL,
                    "IdOrder"    INTEGER NOT NULL,
                    "Answers"    TEXT    NOT NULL,
                    "LastUpdate" TEXT    NOT NULL,
                    FOREIGN KEY("IdPerson") REFERENCES "Member"("Id"),
                    FOREIGN KEY("IdOrder")  REFERENCES "Order"("Id")
                )
                SQL,
                ['Id', 'IdPerson', 'IdOrder', 'Answers', 'LastUpdate']
            ],
            'PushSubscription' => [
                <<<SQL
                CREATE TABLE "PushSubscription_new" (
                    "Id"        INTEGER PRIMARY KEY,
                    "IdPerson"  INTEGER NOT NULL,
                    "EndPoint"  TEXT    NOT NULL UNIQUE,
                    "Auth"      TEXT    NOT NULL,
                    "CreatedAt" TEXT    NOT NULL DEFAULT current_timestamp,
                    "P256dh"    TEXT    NOT NULL DEFAULT '',
                    FOREIGN KEY("IdPerson") REFERENCES "Member"("Id")
                )
                SQL,
                ['Id', 'IdPerson', 'EndPoint', 'Auth', 'CreatedAt', 'P256dh']
            ],
            'Reply' => [
                <<<SQL
                CREATE TABLE "Reply_new" (
                    "Id"         INTEGER PRIMARY KEY,
                    "IdPerson"   INTEGER NOT NULL,
                    "IdSurvey"   INTEGER NOT NULL,
                    "Answers"    TEXT    NOT NULL,
                    "LastUpdate" TEXT    NOT NULL DEFAULT current_timestamp,
                    FOREIGN KEY("IdPerson") REFERENCES "Member"("Id"),
                    FOREIGN KEY("IdSurvey") REFERENCES "Survey"("Id")
                )
                SQL,
                ['Id', 'IdPerson', 'IdSurvey', 'Answers', 'LastUpdate']
            ],
            'LoanRecord' => [
                <<<SQL
                CREATE TABLE "LoanRecord_new" (
                    "Id"            INTEGER PRIMARY KEY,
                    "ItemId"        INTEGER NOT NULL,
                    "BorrowerId"    INTEGER NOT NULL,
                    "LenderId"      INTEGER NOT NULL,
                    "LoanDate"      TEXT    NOT NULL,
                    "DueDate"       TEXT    NOT NULL,
                    "ReturnDate"    TEXT,
                    "ReturnedToId"  INTEGER,
                    "QuantityLent"  INTEGER NOT NULL DEFAULT 1,
                    "Notes"         TEXT    NOT NULL DEFAULT '',
                    "Status"        TEXT    NOT NULL DEFAULT 'active' CHECK("Status" IN ('active', 'returned', 'overdue', 'cancelled')),
                    "CreatedAt"     TEXT    NOT NULL DEFAULT (datetime('now')),
                    FOREIGN KEY("BorrowerId")   REFERENCES "Member"("Id"),
                    FOREIGN KEY("ItemId")       REFERENCES "LoanItem"("Id"),
                    FOREIGN KEY("LenderId")     REFERENCES "Member"("Id"),
                    FOREIGN KEY("ReturnedToId") REFERENCES "Member"("Id")
                )
                SQL,
                [
                    'Id',
                    'ItemId',
                    'BorrowerId',
                    'LenderId',
                    'LoanDate',
                    'DueDate',
                    'ReturnDate',
                    'ReturnedToId',
                    'QuantityLent',
                    'Notes',
                    'Status',
                    'CreatedAt'
                ]
            ],
            'LoanReservation' => [
                <<<SQL
                CREATE TABLE "LoanReservation_new" (
                    "Id"               INTEGER PRIMARY KEY,
                    "ItemId"           INTEGER NOT NULL,
                    "UserId"           INTEGER NOT NULL,
                    "ReservationDate"  TEXT    NOT NULL,
                    "StartTime"        TEXT    NOT NULL,
                    "EndTime"          TEXT    NOT NULL,
                    "QuantityReserved" INTEGER NOT NULL DEFAULT 1,
                    "Notes"            TEXT    NOT NULL DEFAULT '',
                    "Status"           TEXT    NOT NULL DEFAULT 'active' CHECK("Status" IN ('active', 'cancelled')),
                    "CreatedAt"        TEXT    NOT NULL DEFAULT (datetime('now')),
                    FOREIGN KEY("ItemId") REFERENCES "LoanItem"("Id"),
                    FOREIGN KEY("UserId") REFERENCES "Member"("Id")
                )
                SQL,
                ['Id', 'ItemId', 'UserId', 'ReservationDate', 'StartTime', 'EndTime', 'QuantityReserved', 'Notes', 'Status', 'CreatedAt']
            ],
            'Membership' => [
                <<<SQL
                CREATE TABLE "Membership_new" (
                    "Id"                         INTEGER PRIMARY KEY,
                    "PersonId"                   INTEGER NOT NULL,
                    "Season"                     TEXT    NOT NULL,
                    "Amount"                     INTEGER NOT NULL DEFAULT 0,
                    "Status"                     TEXT    NOT NULL DEFAULT 'pending' CHECK("Status" IN ('pending', 'paid', 'cancelled')),
                    "HelloAssoOrderId"           TEXT    NOT NULL DEFAULT '',
                    "HelloAssoCheckoutIntentId"  TEXT    NOT NULL DEFAULT '',
                    "PaidAt"                     TEXT,
                    "CreatedAt"                  TEXT    NOT NULL DEFAULT (datetime('now')),
                    "UpdatedAt"                  TEXT    NOT NULL DEFAULT (datetime('now')),
                    FOREIGN KEY("PersonId") REFERENCES "Member"("Id")
                )
                SQL,
                [
                    'Id',
                    'PersonId',
                    'Season',
                    'Amount',
                    'Status',
                    'HelloAssoOrderId',
                    'HelloAssoCheckoutIntentId',
                    'PaidAt',
                    'CreatedAt',
                    'UpdatedAt'
                ]
            ],
        ];

        foreach ($tables as $table => [$ddl, $columns]) {
            $pdo->exec($ddl);
            $colList = '"' . implode('", "', $columns) . '"';
            $pdo->exec("INSERT INTO \"{$table}_new\" ($colList) SELECT $colList FROM \"$table\"");
            $pdo->exec("DROP TABLE \"$table\"");
            $pdo->exec("ALTER TABLE \"{$table}_new\" RENAME TO \"$table\"");
        }
    }

    private function dropLegacyTables(PDO $pdo): void
    {
        foreach (['Guest', 'Participant', 'Contact', 'Person', 'PersonGroup'] as $table) {
            $pdo->exec("DROP TABLE IF EXISTS \"$table\"");
        }
    }

    private function finalizeTableNames(PDO $pdo): void
    {
        $pdo->exec('ALTER TABLE Contact_new    RENAME TO Contact');
        $pdo->exec('ALTER TABLE Participant_new RENAME TO Participant');
    }

    private function recreateViews(PDO $pdo): void
    {
        // Updated to join Member + Individual for person names
        $pdo->exec(<<<SQL
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
                    ELSE 
                        (
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

        $pdo->exec(<<<SQL
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

        $pdo->exec(<<<SQL
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

    /**
     * @return list<object>
     */
    private function queryAll(PDO $pdo, string $sql): array
    {
        $stmt = $pdo->query($sql);
        if ($stmt === false) {
            throw new RuntimeException("Query failed: $sql");
        }

        return array_values($stmt->fetchAll(PDO::FETCH_OBJ));
    }
}
