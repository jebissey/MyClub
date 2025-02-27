BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "Article" (
	"Id"	INTEGER,
	"Title"	TEXT NOT NULL,
	"Content"	TEXT NOT NULL,
	"CreatedBy"	INTEGER NOT NULL,
	"Timestamp"	TEXT NOT NULL DEFAULT current_timestamp,
	"Published"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("Id"),
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "ArticleGroup" (
	"Id"	INTEGER,
	"IdArticle"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdArticle") REFERENCES "Article"("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "Attribute" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Detail"	TEXT NOT NULL,
	"Color"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Authorization" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Contact" (
	"Id"	INTEGER,
	"Email"	TEXT NOT NULL,
	"Token"	TEXT,
	"TokenCreatedAt"	TEXT,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Counter" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Detail"	TEXT,
	"Value"	INTEGER NOT NULL,
	"IdPerson"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	"Timestamp"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "Event" (
	"Id"	INTEGER,
	"Summary"	TEXT NOT NULL,
	"Description"	TEXT NOT NULL,
	"Location"	TEXT NOT NULL,
	"StartTime"	TEXT NOT NULL,
	"EndTime"	TEXT NOT NULL,
	"IdEventType"	INTEGER NOT NULL,
	"CreatedBy"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id"),
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id")
);
CREATE TABLE IF NOT EXISTS "EventAttribute" (
	"Id"	INTEGER,
	"IdEvent"	INTEGER NOT NULL,
	"IdAttribute"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdAttribute") REFERENCES "Attribute"("Id"),
	FOREIGN KEY("IdEvent") REFERENCES "Event"("Id")
);
CREATE TABLE IF NOT EXISTS "EventNeed" (
	"Id"	INTEGER,
	"IdEvent"	INTEGER NOT NULL,
	"IdNeed"	INTEGER NOT NULL,
	"Counter"	INTEGER,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdEvent") REFERENCES "Event"("Id"),
	FOREIGN KEY("IdNeed") REFERENCES "Need"("Id")
);
CREATE TABLE IF NOT EXISTS "EventType" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Inactivated"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "EventTypeAttribute" (
	"Id"	INTEGER,
	"IdEventType"	INTEGER NOT NULL,
	"IdAttribute"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdAttribute") REFERENCES "Attribute"("Id"),
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id")
);
CREATE TABLE IF NOT EXISTS "EventTypeGroup" (
	"Id"	INTEGER,
	"IdEventType"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "Group" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Inactivated"	INTEGER NOT NULL DEFAULT 0,
	"SelfRegistration"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "GroupAuthorization" (
	"Id"	INTEGER,
	"IdGroup"	INTEGER NOT NULL,
	"IdAuthorization"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdAuthorization") REFERENCES "Authorization"("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "Languages" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"en_us"	TEXT NOT NULL,
	"fr_fr"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Metadata" (
	"Id"	INTEGER,
	"ApplicationName"	TEXT NOT NULL,
	"DatabaseVersion"	INTEGER NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Need" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"PaticipantDependent"	INTEGER NOT NULL DEFAULT 0,
	"Detail"	TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS "Page" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Position"	INTEGER NOT NULL,
	"File"	TEXT NOT NULL,
	"Content"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "PageGroup" (
	"Id"	INTEGER,
	"IdPage"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("IdPage") REFERENCES "Page"("Id")
);
CREATE TABLE IF NOT EXISTS "Participant" (
	"Id"	INTEGER,
	"IdEvent"	INTEGER NOT NULL,
	"IdPerson"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdEvent") REFERENCES "Event"("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "ParticipantSupply" (
	"Id"	INTEGER,
	"IdParticipant"	INTEGER NOT NULL,
	"IdNeed"	INTEGER NOT NULL,
	"Supply"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdNeed") REFERENCES "Need"("Id"),
	FOREIGN KEY("IdParticipant") REFERENCES "Participant"("Id")
);
CREATE TABLE IF NOT EXISTS "Person" (
	"Id"	INTEGER,
	"Email"	TEXT NOT NULL UNIQUE,
	"Password"	TEXT,
	"FirstName"	TEXT NOT NULL,
	"LastName"	TEXT NOT NULL,
	"NickName"	TEXT,
	"Avatar"	TEXT,
	"UseGravatar"	TEXT NOT NULL DEFAULT 'no',
	"Token"	TEXT,
	"TokenCreatedAt"	TEXT,
	"Availabilities"	TEXT,
	"Preferences"	TEXT,
	"Imported"	INTEGER NOT NULL DEFAULT 0,
	"Inactivated"	INTEGER NOT NULL DEFAULT 0,
	"Phone"	TEXT,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "PersonGroup" (
	"Id"	INTEGER,
	"IdPerson"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "Reply" (
	"Id"	INTEGER,
	"IdPerson"	INTEGER NOT NULL,
	"IdSurvey"	INTEGER NOT NULL,
	"Answers"	TEXT NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id"),
	FOREIGN KEY("IdSurvey") REFERENCES "Survey"("Id")
);
CREATE TABLE IF NOT EXISTS "Settings" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Value"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Survey" (
	"Id"	INTEGER,
	"Question"	TEXT NOT NULL,
	"Options"	TEXT NOT NULL,
	"IdArticle"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdArticle") REFERENCES "Article"("Id")
);
INSERT INTO "Authorization" VALUES (1,'Webmaster');
INSERT INTO "Authorization" VALUES (2,'PersonManager');
INSERT INTO "Authorization" VALUES (3,'EventManager');
INSERT INTO "Authorization" VALUES (4,'Redactor');
INSERT INTO "Group" VALUES (1,'Webmaster',0,0);
INSERT INTO "GroupAuthorization" VALUES (1,1,1);
INSERT INTO "Metadata" VALUES (1,'MyClub',1);
INSERT INTO "Person" VALUES (1,'webmaster@myclub.foo','613cbc51f1650fb264beaad127efc1a5da0f96a96d4da7c440dc01a9e5299910','my first name','my last name','my nick name or nothing',NULL,'0',NULL,NULL,NULL,NULL,0,0,NULL);
INSERT INTO "PersonGroup" VALUES (1,1,1);
INSERT INTO "Settings" VALUES (1,'Title','title');
INSERT INTO "Settings" VALUES (2,'LegalNotices','LegalNotices');
COMMIT;
