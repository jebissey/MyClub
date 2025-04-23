BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "Alert" (
	"Id"	INTEGER,
	"CreatedBy"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	"OnlyForMembers"	INTEGER NOT NULL DEFAULT 1,
	"StartDate"	TEXT NOT NULL DEFAULT current_timestamp,
	"EndDate"	TEXT NOT NULL DEFAULT current_timestamp,
	"Message"	TEXT NOT NULL,
	"Type"	TEXT NOT NULL DEFAULT 'alert-warning',
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "Article" (
	"Id"	INTEGER,
	"Title"	TEXT NOT NULL,
	"Content"	TEXT NOT NULL,
	"CreatedBy"	INTEGER NOT NULL,
	"Timestamp"	TEXT NOT NULL DEFAULT current_timestamp,
	"PublishedBy"	INTEGER DEFAULT NULL,
	"IdGroup"	INTEGER DEFAULT NULL,
	"OnlyForMembers"	INTEGER NOT NULL DEFAULT 1,
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("CreatedBy") REFERENCES "Person"("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("PublishedBy") REFERENCES "Person"("Id")
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
	"NickName"	TEXT,
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
CREATE TABLE IF NOT EXISTS "Design" (
	"Id"	INTEGER,
	"IdPerson"	INTEGER NOT NULL,
	"IdGroup"	INTEGER NOT NULL,
	"OnlyForMembers"	INTEGER NOT NULL DEFAULT 1,
	"Name"	TEXT,
	"Detail"	TEXT,
	"NavBar"	TEXT,
	"Status"	TEXT NOT NULL DEFAULT 'UnderReview',
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "DesignVote" (
	"Id"	INTEGER,
	"IdDesign"	INTEGER NOT NULL,
	"IdPerson"	INTEGER NOT NULL,
	"Vote"	INTEGER NOT NULL DEFAULT 0,
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdDesign") REFERENCES "Design"("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "Event" (
	"Id"	INTEGER,
	"Summary"	TEXT NOT NULL,
	"Description"	TEXT NOT NULL,
	"Location"	TEXT NOT NULL,
	"StartTime"	TEXT NOT NULL,
	"Duration"	INTEGER NOT NULL DEFAULT 3600,
	"IdEventType"	INTEGER NOT NULL,
	"CreatedBy"	INTEGER NOT NULL,
	"MaxParticipants"	INTEGER NOT NULL DEFAULT 0,
	"Audience"	TEXT NOT NULL DEFAULT 'ClubMembersOnly',
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
	"IdGroup"	INTEGER DEFAULT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "EventTypeAttribute" (
	"Id"	INTEGER,
	"IdEventType"	INTEGER NOT NULL,
	"IdAttribute"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdAttribute") REFERENCES "Attribute"("Id"),
	FOREIGN KEY("IdEventType") REFERENCES "EventType"("Id")
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
	"en_US"	TEXT NOT NULL,
	"fr_FR"	TEXT NOT NULL,
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
	"Label"	TEXT NOT NULL,
	"Name"	TEXT NOT NULL,
	"ParticipantDependent"	INTEGER NOT NULL DEFAULT 0,
	"IdNeedType"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdNeedType") REFERENCES "NeedType"("Id")
);
CREATE TABLE IF NOT EXISTS "NeedType" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Page" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"Position"	INTEGER NOT NULL,
	"Route"	TEXT NOT NULL,
	"IdGroup"	INTEGER DEFAULT NULL,
	"OnlyForMembers"	INTEGER NOT NULL DEFAULT 1,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "Participant" (
	"Id"	INTEGER,
	"IdEvent"	INTEGER NOT NULL,
	"IdPerson"	INTEGER,
	"IdContact"	INTEGER,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdContact") REFERENCES "Contact"("Id"),
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
	"LastUpdaate"	TEXT NOT NULL DEFAULT current_timestamp,
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
	"ClosingDate"	DATE DEFAULT (date('now', '+10 days')),
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdArticle") REFERENCES "Article"("Id")
);
INSERT INTO "Authorization" VALUES (1,'Webmaster');
INSERT INTO "Authorization" VALUES (2,'PersonManager');
INSERT INTO "Authorization" VALUES (3,'EventManager');
INSERT INTO "Authorization" VALUES (4,'Redactor');
INSERT INTO "Authorization" VALUES (5,'Editor');
INSERT INTO "Group" VALUES (1,'Webmaster',0,0);
INSERT INTO "GroupAuthorization" VALUES (1,1,1);
INSERT INTO "Languages" VALUES (1,'select_language','Select language','Sélectionner la langue');
INSERT INTO "Languages" VALUES (2,'language','Language','Langue');
INSERT INTO "Languages" VALUES (3,'my_data','My ddata','Mes données');
INSERT INTO "Languages" VALUES (4,'admin_zone','Admin zone','Zone d''administration');
INSERT INTO "Languages" VALUES (5,'logout','Logout','Déconnexion');
INSERT INTO "Languages" VALUES (6,'contextual_help','Contextual help','Aide contextuelle');
INSERT INTO "Languages" VALUES (7,'vote','Vote','Voter');
INSERT INTO "Languages" VALUES (8,'connection_required','(You must be connected)','(Vous devez être connecté)');
INSERT INTO "Languages" VALUES (9,'home','Home','Accueil');
INSERT INTO "Languages" VALUES (10,'created_by','Created by','Créé par');
INSERT INTO "Languages" VALUES (11,'modified_on','modified on','modifié le');
INSERT INTO "Languages" VALUES (12,'on','on','le');
INSERT INTO "Languages" VALUES (13,'eventsAvailableForYou','Your events','Vos événements');
INSERT INTO "Languages" VALUES (14,'eventsAvailableForAll','The events','Les événements');
INSERT INTO "Languages" VALUES (15,'type','Type','Type');
INSERT INTO "Languages" VALUES (16,'summary','Summary','Sommaire');
INSERT INTO "Languages" VALUES (17,'location','Location','Lieu');
INSERT INTO "Languages" VALUES (18,'date_time','Date and time','Date et heure');
INSERT INTO "Languages" VALUES (19,'duration','Duration','Durée');
INSERT INTO "Languages" VALUES (20,'attributes','Attributes','Attribut');
INSERT INTO "Languages" VALUES (21,'description','Description','Description');
INSERT INTO "Languages" VALUES (22,'participants','Participants','Participants');
INSERT INTO "Languages" VALUES (23,'audience','Audience','Audience');
INSERT INTO "Languages" VALUES (24,'ClubMembersOnly','Members','Membres');
INSERT INTO "Languages" VALUES (25,'All','Public','Public');
INSERT INTO "Languages" VALUES (26,'register','Register','S''inscrire');
INSERT INTO "Languages" VALUES (27,'unregister','Unregister','Se désinscrire');
INSERT INTO "Languages" VALUES (28,'fullyBooked','Fully booked','Complet');
INSERT INTO "Languages" VALUES (29,'noAttributes','No attributes','Aucun attribut');
INSERT INTO "Languages" VALUES (30,'noParticipant','No participant at this time','Aucun participant pour le moment');
INSERT INTO "Languages" VALUES (31,'login','Login','Connexion');
INSERT INTO "Languages" VALUES (32,'edit','Edit','Modifier');
INSERT INTO "Metadata" VALUES (1,'MyClub',1);
INSERT INTO "Person" VALUES (1,'webmaster@myclub.foo','613cbc51f1650fb264beaad127efc1a5da0f96a96d4da7c440dc01a9e5299910','my first name','my last name','my nick name or nothing',NULL,'0',NULL,NULL,NULL,NULL,0,0,NULL);
INSERT INTO "PersonGroup" VALUES (1,1,1);
INSERT INTO "Settings" VALUES (1,'Title','title');
INSERT INTO "Settings" VALUES (2,'LegalNotices','LegalNotices');
COMMIT;
