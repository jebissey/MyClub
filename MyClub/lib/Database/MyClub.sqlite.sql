BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "EventType" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Group" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "PersonGroup" (
	"Id"	INTEGER,
	"IdPerson"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id"),
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Article" (
	"Id"	INTEGER,
	"Title"	TEXT NOT NULL,
	"Content"	TEXT NOT NULL,
	"CreatedBy"	INTEGER NOT NULL,
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id"),
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Participant" (
	"Id"	INTEGER,
	"IdEvent"	INTEGER NOT NULL,
	"IdPerson"	INTEGER NOT NULL,
	FOREIGN KEY("IdEvent") REFERENCES "Event"("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id"),
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "SiteData" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Value"	TEXT NOT NULL,
	PRIMARY KEY("Id")
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
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id"),
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id")
);
CREATE TABLE IF NOT EXISTS "Contact" (
	"Id"	INTEGER,
	"Email"	TEXT NOT NULL,
	"Token"	TEXT,
	"TokenCreatedAt"	TEXT,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Metadata" (
	"Id"	INTEGER,
	"ApplicationName"	TEXT NOT NULL,
	"DatabaseVersion"	INTEGER NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Authorization" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "ArticleGroup" (
	"Id"	INTEGER,
	"IdArticle"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("IdArticle") REFERENCES "Article"("Id")
);
CREATE TABLE IF NOT EXISTS "Page" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Position"	INTEGER NOT NULL,
	"File"	TEXT NOT NULL,
	"Content"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Attribute" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Detail"	TEXT NOT NULL,
	"Color"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Counter" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Value"	INTEGER NOT NULL,
	"IdPerson"	INTEGER NOT NULL,
	"Timestamp"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "EventTypeGroup" (
	"Id"	INTEGER,
	"IdEventType"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id"),
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "EventTypeAttribute" (
	"Id"	INTEGER,
	"IdEventType"	INTEGER NOT NULL,
	"IdAttribute"	INTEGER NOT NULL,
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id"),
	FOREIGN KEY("IdAttribute") REFERENCES "Attribute"("Id"),
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "EventAttribute" (
	"Id"	INTEGER,
	"IdEvent"	INTEGER NOT NULL,
	"IdAttribute"	INTEGER NOT NULL,
	FOREIGN KEY("IdEvent") REFERENCES "Event"("Id"),
	FOREIGN KEY("IdAttribute") REFERENCES "Attribute"("Id"),
	PRIMARY KEY("Id")
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
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "GroupAuthorization" (
	"Id"	INTEGER,
	"IdGroup"	INTEGER NOT NULL,
	"IdAuthorization"	INTEGER NOT NULL,
	FOREIGN KEY("IdAuthorization") REFERENCES "Authorization"("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Settings" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Value"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Languages" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"en_us"	TEXT NOT NULL,
	"fr_fr"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "PageGroup" (
	"Id"	INTEGER,
	"IdPage"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	FOREIGN KEY("IdPage") REFERENCES "Page"("Id"),
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
INSERT INTO "Group" VALUES (1,'Webmaster');
INSERT INTO "PersonGroup" VALUES (1,1,1);
INSERT INTO "SiteData" VALUES (1,'Title','My Club');
INSERT INTO "SiteData" VALUES (2,'LegalNotices','Legal notices');
INSERT INTO "Metadata" VALUES (1,'MyClub',1);
INSERT INTO "Authorization" VALUES (1,'Webmaster');
INSERT INTO "Authorization" VALUES (2,'PersonManager');
INSERT INTO "Authorization" VALUES (3,'EventManager');
INSERT INTO "Authorization" VALUES (4,'Redactor');
INSERT INTO "Person" VALUES (1,'webmaster@myclub.foo','613cbc51f1650fb264beaad127efc1a5da0f96a96d4da7c440dc01a9e5299910','my first name','my last name','my nick name or nothing',NULL,'0',NULL,NULL,NULL,NULL);
INSERT INTO "GroupAuthorization" VALUES (1,1,1);
COMMIT;
