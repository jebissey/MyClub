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
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "EventTypeAttribue" (
	"Id"	INTEGER,
	"IdEventType"	INTEGER NOT NULL,
	"IdAttribute"	INTEGER NOT NULL,
	FOREIGN KEY("IdAttribute") REFERENCES "Attribute"("Id"),
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id")
);
CREATE TABLE IF NOT EXISTS "Article" (
	"Id"	INTEGER,
	"Title"	TEXT NOT NULL,
	"Content"	TEXT NOT NULL,
	"CreatedBy"	INTEGER NOT NULL,
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id"),
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "GroupAuthorisation" (
	"Id"	INTEGER,
	"IdGroup"	INTEGER NOT NULL,
	"IdAuthorisation"	INTEGER NOT NULL,
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("IdAuthorisation") REFERENCES "Authorization"("Id"),
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Participant" (
	"Id"	INTEGER,
	"IdEvent"	INTEGER NOT NULL,
	"IdPerson"	INTEGER NOT NULL,
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id"),
	FOREIGN KEY("IdEvent") REFERENCES "Event"("Id"),
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
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id"),
	PRIMARY KEY("Id"),
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id")
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
CREATE TABLE IF NOT EXISTS "EventGroup" (
	"Id"	INTEGER,
	"IdEvent"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	FOREIGN KEY("IdEvent") REFERENCES "Event"("Id"),
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "ArticleGroup" (
	"Id"	INTEGER,
	"IdArticle"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	FOREIGN KEY("IdArticle") REFERENCES "Article"("Id"),
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
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
CREATE TABLE IF NOT EXISTS "Person" (
	"Id"	INTEGER,
	"Email"	TEXT NOT NULL UNIQUE,
	"Password"	TEXT,
	"FirstName"	TEXT NOT NULL,
	"LastName"	TEXT NOT NULL,
	"NickName"	TEXT,
	"Avatar"	TEXT,
	"Token"	TEXT,
	"TokenCreatedAt"	TEXT,
	"Availability"	TEXT,
	PRIMARY KEY("Id")
);
INSERT INTO "Group" VALUES (1,'Webmaster');
INSERT INTO "PersonGroup" VALUES (1,1,1);
INSERT INTO "GroupAuthorisation" VALUES (1,1,1);
INSERT INTO "SiteData" VALUES (1,'Title','My Club');
INSERT INTO "SiteData" VALUES (2,'LegalNotices','Legal notices');
INSERT INTO "Metadata" VALUES (1,'MyClub',1);
INSERT INTO "Authorization" VALUES (1,'Webmaster');
INSERT INTO "Authorization" VALUES (2,'PersonManager');
INSERT INTO "Authorization" VALUES (3,'EventManager');
INSERT INTO "Authorization" VALUES (4,'Redactor');
INSERT INTO "Person" VALUES (1,'webmaster@myclub.foo','613cbc51f1650fb264beaad127efc1a5da0f96a96d4da7c440dc01a9e5299910','my first name','my last name','my nick name or nothing',NULL,NULL,NULL,NULL);
COMMIT;
