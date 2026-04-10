BEGIN TRANSACTION;
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
	"Language"	TEXT NOT NULL DEFAULT 'fr_FR',
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
CREATE TABLE IF NOT EXISTS "Carousel" (
	"Id"	INTEGER,
	"IdArticle"	INTEGER NOT NULL,
	"Item"	TEXT NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdArticle") REFERENCES "Article"("Id")
);
CREATE TABLE IF NOT EXISTS "Contact" (
	"Id"	INTEGER,
	"Email"	TEXT NOT NULL,
	"NickName"	TEXT,
	"Token"	TEXT,
	"TokenCreatedAt"	TEXT,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "ContactRateLimit" (
	"ip_hash"	TEXT NOT NULL,
	"attempts"	INTEGER NOT NULL DEFAULT 1,
	"since"	INTEGER NOT NULL,
	PRIMARY KEY("ip_hash")
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
	"IdGroup"	INTEGER,
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
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	"Canceled"	INTEGER NOT NULL DEFAULT 0,
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
CREATE TABLE IF NOT EXISTS "Guest" (
	"Id"	INTEGER,
	"IdContact"	INTEGER NOT NULL,
	"IdEvent"	INTEGER NOT NULL,
	"InvitedBy"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdContact") REFERENCES "Contact"("Id"),
	FOREIGN KEY("IdEvent") REFERENCES "Event"("Id"),
	FOREIGN KEY("InvitedBy") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "KanbanCard" (
	"Id"	INTEGER,
	"Title"	TEXT NOT NULL,
	"Detail"	TEXT NOT NULL,
	"IdKanbanCardType"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdKanbanCardType") REFERENCES "KanbanCardType"("Id")
);
CREATE TABLE IF NOT EXISTS "KanbanCardStatus" (
	"Id"	INTEGER,
	"IdKanbanCard"	INTEGER NOT NULL,
	"What"	TEXT NOT NULL,
	"Remark"	TEXT NOT NULL,
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdKanbanCard") REFERENCES "KanbanCard"("Id")
);
CREATE TABLE IF NOT EXISTS "KanbanCardType" (
	"Id"	INTEGER,
	"Label"	TEXT NOT NULL,
	"Detail"	TEXT NOT NULL,
	"IdKanbanProject"	INTEGER NOT NULL,
	"Color"	TEXT NOT NULL DEFAULT 'bg-warning-subtle',
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdKanbanProject") REFERENCES "KanbanProject"("Id")
);
CREATE TABLE IF NOT EXISTS "KanbanProject" (
	"Id"	INTEGER,
	"Title"	TEXT NOT NULL,
	"Detail"	TEXT NOT NULL,
	"IdPerson"	INTEGER NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "KaraokeClient" (
	"Id"	INTEGER,
	"ClientId"	TEXT NOT NULL UNIQUE,
	"IdKaraokeSession"	INTEGER NOT NULL,
	"IsHost"	INTEGER DEFAULT 0,
	"LastHeartbeat"	TEXT NOT NULL DEFAULT current_timestamp,
	"CreatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdKaraokeSession") REFERENCES "KaraokeSession"("Id")
);
CREATE TABLE IF NOT EXISTS "KaraokeSession" (
	"Id"	INTEGER,
	"SessionId"	TEXT NOT NULL UNIQUE,
	"SongName"	TEXT NOT NULL,
	"Status"	TEXT DEFAULT 'waiting',
	"CountdownStart"	INTEGER,
	"PlayStartTime"	INTEGER,
	"CurrentTime"	REAL DEFAULT 0,
	"CreatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
	"UpdatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "Languages" (
	"Id"	INTEGER,
	"Name"	TEXT NOT NULL,
	"en_US"	TEXT NOT NULL,
	"fr_FR"	TEXT NOT NULL,
	"pl_PL"	TEXT NOT NULL,
	PRIMARY KEY("Id")
);
CREATE TABLE IF NOT EXISTS "MenuItem" (
	"Id"	INTEGER,
	"Type"	TEXT NOT NULL CHECK("Type" IN ('heading', 'link', 'divider', 'submenu')),
	"Label"	TEXT,
	"Icon"	TEXT,
	"Url"	TEXT,
	"ParentId"	INTEGER,
	"Position"	INTEGER NOT NULL DEFAULT 1,
	"IdGroup"	INTEGER DEFAULT NULL,
	"ForMembers"	INTEGER NOT NULL DEFAULT 0,
	"ForContacts"	INTEGER NOT NULL DEFAULT 0,
	"ForAnonymous"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id"),
	FOREIGN KEY("ParentId") REFERENCES "MenuItem"("Id") ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS "Message" (
	"Id"	INTEGER,
	"EventId"	INTEGER,
	"PersonId"	INTEGER NOT NULL,
	"Text"	TEXT NOT NULL,
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	"From"	TEXT NOT NULL DEFAULT 'User',
	"ArticleId"	INTEGER,
	"GroupId"	INTEGER,
	PRIMARY KEY("Id"),
	FOREIGN KEY("ArticleId") REFERENCES "Article"("Id"),
	FOREIGN KEY("EventId") REFERENCES "Event"("Id"),
	FOREIGN KEY("GroupId") REFERENCES "Group"("Id"),
	FOREIGN KEY("PersonId") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "Metadata" (
	"Id"	INTEGER,
	"ApplicationName"	TEXT NOT NULL,
	"DatabaseVersion"	INTEGER NOT NULL,
	"SiteUnderMaintenance"	INTEGER NOT NULL DEFAULT 0,
	"Compact_maxRecords"	INTEGER NOT NULL DEFAULT 1000000,
	"Compact_lastDate"	TEXT,
	"Compact_everyXdays"	INTEGER NOT NULL DEFAULT 10,
	"Compact_removeOlderThanXmonths"	INTEGER NOT NULL DEFAULT 36,
	"Compact_compactOlderThanXmonths"	INTEGER NOT NULL DEFAULT 6,
	"ThisIsProdSiteUrl"	TEXT,
	"ThisIsTestSite"	INTEGER NOT NULL DEFAULT 0,
	"ThisIsForcedLanguage"	TEXT,
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
CREATE TABLE IF NOT EXISTS "Order" (
	"Id"	INTEGER,
	"Question"	TEXT NOT NULL,
	"Options"	TEXT NOT NULL,
	"IdArticle"	INTEGER NOT NULL,
	"ClosingDate"	TEXT NOT NULL,
	"Visibility"	TEXT NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdArticle") REFERENCES "Article"("Id")
);
CREATE TABLE IF NOT EXISTS "OrderReply" (
	"Id"	INTEGER,
	"IdPerson"	INTEGER NOT NULL,
	"IdOrder"	INTEGER NOT NULL,
	"Answers"	TEXT NOT NULL,
	"LastUpdate"	TEXT NOT NULL,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdOrder") REFERENCES "Order"("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
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
	"Notifications"	TEXT,
	"Imported"	INTEGER NOT NULL DEFAULT 0,
	"Inactivated"	INTEGER NOT NULL DEFAULT 0,
	"Phone"	TEXT,
	"Presentation"	TEXT,
	"PresentationLastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	"InPresentationDirectory"	INTEGER NOT NULL DEFAULT 0,
	"ShowPhoneInPresentationDirectory"	INTEGER NOT NULL DEFAULT 0 CHECK("ShowPhoneInPresentationDirectory" IN (0, 1)),
	"ShowEmailInPresentationDirectory"	INTEGER NOT NULL DEFAULT 0 CHECK("ShowEmailInPresentationDirectory" IN (0, 1)),
	"MyPublicDataInPresentationDirectory"	TEXT,
	"Location"	TEXT,
	"LastSignIn"	TEXT,
	"LastSignOut"	TEXT,
	"Notepad"	TEXT,
	"Alert"	TEXT,
	"MemberInfo"	TEXT DEFAULT '',
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
CREATE TABLE IF NOT EXISTS "PushSubscription" (
	"Id"	INTEGER,
	"IdPerson"	INTEGER NOT NULL,
	"EndPoint"	TEXT NOT NULL UNIQUE,
	"Auth"	TEXT NOT NULL,
	"P256dh"	TEXT NOT NULL,
	"CreatedAt"	TEXT NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdPerson") REFERENCES "Person"("Id")
);
CREATE TABLE IF NOT EXISTS "Reply" (
	"Id"	INTEGER,
	"IdPerson"	INTEGER NOT NULL,
	"IdSurvey"	INTEGER NOT NULL,
	"Answers"	TEXT NOT NULL,
	"LastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
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
CREATE TABLE IF NOT EXISTS "SharedFile" (
	"Id"	INTEGER,
	"Item"	TEXT NOT NULL,
	"IdGroup"	INTEGER,
	"OnlyForMembers"	INTEGER NOT NULL DEFAULT 1,
	"Token"	TEXT,
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdGroup") REFERENCES "Group"("Id")
);
CREATE TABLE IF NOT EXISTS "Survey" (
	"Id"	INTEGER,
	"Question"	TEXT NOT NULL,
	"Options"	TEXT NOT NULL,
	"IdArticle"	INTEGER NOT NULL,
	"ClosingDate"	DATE NOT NULL DEFAULT (date('now', '+10 days')),
	"Visibility"	TEXT NOT NULL DEFAULT 'redactor',
	PRIMARY KEY("Id"),
	FOREIGN KEY("IdArticle") REFERENCES "Article"("Id")
);
INSERT INTO "Authorization" VALUES (1,'Webmaster');
INSERT INTO "Authorization" VALUES (2,'PersonManager');
INSERT INTO "Authorization" VALUES (3,'EventManager');
INSERT INTO "Authorization" VALUES (4,'Redactor');
INSERT INTO "Authorization" VALUES (5,'Editor');
INSERT INTO "Authorization" VALUES (6,'HomeDesigner');
INSERT INTO "Authorization" VALUES (7,'EventDesigner');
INSERT INTO "Authorization" VALUES (8,'VisitorInsights');
INSERT INTO "Authorization" VALUES (9,'MenuDesigner');
INSERT INTO "Authorization" VALUES (10,'KanbanDesigner');
INSERT INTO "Authorization" VALUES (11,'Translator');
INSERT INTO "Authorization" VALUES (12,'CommunicationManager');
INSERT INTO "Group" VALUES (1,'Webmaster',0,0);
INSERT INTO "GroupAuthorization" VALUES (1,1,1);
INSERT INTO "Languages" VALUES (1,'select_language','Select language','Sélectionner la langue','Wybierz język');
INSERT INTO "Languages" VALUES (2,'language','Language','Langue','Język');
INSERT INTO "Languages" VALUES (3,'my_data','My ddata','Mes données','Moje dane');
INSERT INTO "Languages" VALUES (4,'admin_zone','Admin zone','Zone d''administration','Strefa administratora');
INSERT INTO "Languages" VALUES (5,'logout','Logout','Déconnexion','Wyloguj');
INSERT INTO "Languages" VALUES (6,'contextual_help','Contextual help','Aide contextuelle','Pomoc kontekstualna');
INSERT INTO "Languages" VALUES (7,'vote','Vote','Voter','Głosuj');
INSERT INTO "Languages" VALUES (8,'connection_required','(You must be connected)','(Vous devez être connecté)','(Musisz być zalogowany)');
INSERT INTO "Languages" VALUES (9,'home','Home','Accueil','Strona główna');
INSERT INTO "Languages" VALUES (10,'created_by','Created by','Créé par','Utworzony przez');
INSERT INTO "Languages" VALUES (11,'modified_on','modified on','modifié le','zmodyfikowano dnia');
INSERT INTO "Languages" VALUES (12,'on','on','le','dnia');
INSERT INTO "Languages" VALUES (13,'eventsAvailableForYou','Your events','Vos événements','Twoje wydarzenia');
INSERT INTO "Languages" VALUES (14,'eventsAvailableForAll','The events','Les événements','Wszystkie wydarzenia');
INSERT INTO "Languages" VALUES (15,'type','Type','Type','Typ');
INSERT INTO "Languages" VALUES (16,'summary','Summary','Sommaire','Podsumowanie');
INSERT INTO "Languages" VALUES (17,'location','Location','Lieu','Miejsce');
INSERT INTO "Languages" VALUES (18,'date_time','Date and time','Date et heure','Data i godzina');
INSERT INTO "Languages" VALUES (19,'duration','Duration','Durée','Czas trwania');
INSERT INTO "Languages" VALUES (20,'attributes','Attributes','Attribut','Atrybuty');
INSERT INTO "Languages" VALUES (21,'description','Description','Description','Opis');
INSERT INTO "Languages" VALUES (22,'participants','Participants','Participants','Uczestnicy');
INSERT INTO "Languages" VALUES (23,'audience','Audience','Audience','Odbiorcy');
INSERT INTO "Languages" VALUES (24,'ClubMembersOnly','Members','Membres','Członkowie');
INSERT INTO "Languages" VALUES (25,'All','Public','Public','Publiczne');
INSERT INTO "Languages" VALUES (26,'register','Register','S''inscrire','Zapisz się');
INSERT INTO "Languages" VALUES (27,'unregister','Unregister','Se désinscrire','Wypisz się');
INSERT INTO "Languages" VALUES (28,'fullyBooked','Fully booked','Complet','Komplet');
INSERT INTO "Languages" VALUES (29,'noAttributes','No attributes','Aucun attribut','Brak atrybutów');
INSERT INTO "Languages" VALUES (30,'noParticipant','No participant at this time','Aucun participant pour le moment','Brak uczestników');
INSERT INTO "Languages" VALUES (31,'login','Login','Connexion','Logowanie');
INSERT INTO "Languages" VALUES (32,'edit','Edit','Modifier','Edytuj');
INSERT INTO "Languages" VALUES (33,'messages','Messages','Messages','Wiadomości');
INSERT INTO "Languages" VALUES (34,'delete','Delete','Supprimer','Usuń');
INSERT INTO "Languages" VALUES (35,'duplicate','Duplicate','Dupliquer','Duplikuj');
INSERT INTO "Languages" VALUES (36,'sendEmail','Send email','Envoyer courriel','Wyślij e-mail');
INSERT INTO "Languages" VALUES (37,'news','News','News','Aktualności');
INSERT INTO "Languages" VALUES (38,'directory','Directory','Trombinoscope','Katalog');
INSERT INTO "Languages" VALUES (39,'statistics','Statistics','Statistiques','Statystyki');
INSERT INTO "Languages" VALUES (40,'preferences','Preferences','Préférences','Preferencje');
INSERT INTO "Languages" VALUES (41,'groups','Groups','Groupes','Grupy');
INSERT INTO "Languages" VALUES (42,'availabilities','Availabilities','Disponibilités','Dostępności');
INSERT INTO "Languages" VALUES (43,'account','Account','Compte','Konto');
INSERT INTO "Languages" VALUES (44,'Guest','Guest','Invité','Gość');
INSERT INTO "Languages" VALUES (45,'morning','Morning','Matin','Rano');
INSERT INTO "Languages" VALUES (46,'afternoon','Afternoon','Après-midi','Południe');
INSERT INTO "Languages" VALUES (47,'evening','Evening','Soir','Wieczór');
INSERT INTO "Languages" VALUES (48,'Message_UnknownUser','Unknown user (email)','Utilisateur inconnu (courriel)','Nieznany użytkownik (e-mail)');
INSERT INTO "Languages" VALUES (49,'comboSeparatorHome','--- Home ---','--- Accueil ---','--- Strona główna ---');
INSERT INTO "Languages" VALUES (50,'comboSeparatorMessages','--- Messages ---','--- Messages ---','--- Wiadomości ---');
INSERT INTO "Languages" VALUES (51,'comboSeparatorErrorPages','--- Error pages ---','--- Pages d''erreur ---','--- Strony błędów ---');
INSERT INTO "Languages" VALUES (52,'comboSeparatorHelp','--- Help ---','--- Aides ---','--- Pomoce ---');
INSERT INTO "Languages" VALUES (53,'Help_Admin','<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Administration Zone Tools</h2>
                <p class="lead text-muted mb-4">
                    This page provides access to administration tools based on your permissions.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Access &amp; Permissions</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Visibility:</strong> The yellow key only appears in the top bar for members with authorisations.
                                    </li>
                                    <li>
                                        <strong>Smart navigation:</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Multiple zones → selection menu</li>
                                            <li>→ Single zone → automatic redirect (saves time 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Mobile Optimisation</h3>
                                <p class="text-muted small">
                                    Shortcuts appear directly here as well — no need to open the ☰ menu on narrow screens.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">MyClub Permissions (quick reference)</h2>
                <p class="lead text-muted mb-4">
                    When <strong>MyClub</strong> is installed, a <strong>Webmaster</strong> account and group are created automatically.
                    This group is reserved for initial administration: it cannot be modified and no member can be added to it.
                </p>
                <p class="text-muted mb-4">
                    The system is based on <strong>authorisations</strong> assigned to <strong>groups</strong>.
                    Members inherit the rights of the groups they belong to.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-person-gear fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Initial Setup</h3>
                                <p class="text-muted small mb-2">The <strong>Webmaster</strong> must first:</p>
                                <ul class="list-unstyled text-muted small ms-1">
                                    <li class="mb-1">① Create <strong>groups</strong> (<em>PersonManager</em>)</li>
                                    <li class="mb-1">② Create <strong>members</strong> (<em>PersonManager</em>)</li>
                                    <li>③ Assign members to groups</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-palette fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Site Design</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>HomeDesigner</strong>: create/edit the home page</li>
                                    <li class="mb-1"><strong>MenuDesigner</strong>: create and organise menus</li>
                                    <li><strong>Translator</strong>: translate site texts and pages</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Articles</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>Redactor</strong>: write and edit articles</li>
                                    <li class="mb-1"><strong>Editor</strong>: publish an article visible to everyone</li>
                                    <li>A redactor can publish if the article is restricted to members or a group</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Events</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>EventDesigner</strong>: create event types</li>
                                    <li class="mb-1"><strong>EventManager</strong>: create events</li>
                                    <li>Only the <strong>creator</strong> can edit or cancel their event</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-envelope fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Communication</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>CommunicationManager</strong>: send emails to members</li>
                                    <li>Some messages can be sent automatically (articles, events, password, contact)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Kanban Projects</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>KanbanDesigner</strong>: create and edit your own projects</li>
                                    <li>Other members with this right can only view them</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-bar-chart-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Site Analytics</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>VisitorInsights</strong>: access visitor analytics tools</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <h2 class="h4 mb-3 fw-bold">Key takeaways</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>The <strong>yellow key</strong> is only visible to members with authorisations.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>Single administration zone → automatic redirect.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>On mobile: direct shortcuts on this page.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span><strong>Contextual help:</strong> available in each module via the help icon.</span>
            </div>
        </div>
    </section>
</div>','<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Les outils des zones d''administration</h2>
                <p class="lead text-muted mb-4">
                    Cette page permet d''accéder aux outils d''administration selon vos droits.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Accès et Permissions</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Visibilité :</strong> La clé jaune n''apparaît dans la barre supérieure que pour les membres ayant des autorisations.
                                    </li>
                                    <li>
                                        <strong>Navigation intelligente :</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Plusieurs zones → menu de sélection</li>
                                            <li>→ Une seule zone → redirection automatique (gain de temps 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Optimisation Mobile</h3>
                                <p class="text-muted small">
                                    Les raccourcis apparaissent aussi directement ici — plus besoin d''ouvrir le menu ☰ sur les écrans étroits.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Les droits dans MyClub (aide rapide)</h2>
                <p class="lead text-muted mb-4">
                    Lors de l''installation de <strong>MyClub</strong>, un compte et un groupe <strong>Webmaster</strong> sont créés automatiquement.
                    Ce groupe est réservé à l''administration initiale : il ne peut pas être modifié et aucun membre ne peut y être ajouté.
                </p>
                <p class="text-muted mb-4">
                    Le système repose sur des <strong>autorisations</strong> attribuées à des <strong>groupes</strong>.
                    Les membres héritent des droits des groupes auxquels ils appartiennent.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-person-gear fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Première configuration</h3>
                                <p class="text-muted small mb-2">Le <strong>Webmaster</strong> doit d''abord :</p>
                                <ul class="list-unstyled text-muted small ms-1">
                                    <li class="mb-1">① Créer les <strong>groupes</strong> (<em>PersonManager</em>)</li>
                                    <li class="mb-1">② Créer les <strong>membres</strong> (<em>PersonManager</em>)</li>
                                    <li>③ Inscrire les membres dans les groupes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-palette fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Conception du site</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>HomeDesigner</strong> : créer/modifier la page d''accueil</li>
                                    <li class="mb-1"><strong>MenuDesigner</strong> : créer et organiser les menus</li>
                                    <li><strong>Translator</strong> : traduire les textes et pages du site</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Articles</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>Redactor</strong> : écrire et modifier des articles</li>
                                    <li class="mb-1"><strong>Editor</strong> : publier un article visible par tous</li>
                                    <li>Le rédacteur peut publier si l''article est limité aux membres ou à un groupe</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Événements</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>EventDesigner</strong> : créer les types d''événements</li>
                                    <li class="mb-1"><strong>EventManager</strong> : créer des événements</li>
                                    <li>Seul le <strong>créateur</strong> peut modifier ou annuler son événement</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-envelope fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Communication</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>CommunicationManager</strong> : envoyer des courriels aux membres</li>
                                    <li>Certains messages peuvent être envoyés automatiquement (articles, événements, mot de passe, contact)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Projets Kanban</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>KanbanDesigner</strong> : créer et modifier ses propres projets</li>
                                    <li>Les autres membres ayant ce droit peuvent seulement les consulter</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-bar-chart-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Analyse du site</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>VisitorInsights</strong> : accès aux outils d''analyse des visiteurs</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <h2 class="h4 mb-3 fw-bold">Ce qu''il faut retenir</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>La <strong>clé jaune</strong> n''est visible que pour les membres ayant des autorisations.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>Une seule zone d''administration → redirection automatique.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>Sur mobile : raccourcis directs sur cette page.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span><strong>Aide contextuelle :</strong> disponible dans chaque module via l''icône d''aide.</span>
            </div>
        </div>
    </section>
</div>','<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Narzędzia stref administracyjnych</h2>
                <p class="lead text-muted mb-4">
                    Ta strona umożliwia dostęp do narzędzi administracyjnych zgodnie z posiadanymi uprawnieniami.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Dostęp i uprawnienia</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Widoczność:</strong> Żółty klucz pojawia się na górnym pasku tylko dla członków posiadających uprawnienia.
                                    </li>
                                    <li>
                                        <strong>Inteligentna nawigacja:</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Kilka stref → menu wyboru</li>
                                            <li>→ Jedna strefa → automatyczne przekierowanie (oszczędność czasu 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Optymalizacja mobilna</h3>
                                <p class="text-muted small">
                                    Skróty pojawiają się też bezpośrednio tutaj — nie trzeba otwierać menu ☰ na wąskich ekranach.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Uprawnienia w MyClub (krótki przewodnik)</h2>
                <p class="lead text-muted mb-4">
                    Podczas instalacji <strong>MyClub</strong> automatycznie tworzony jest konto i grupa <strong>Webmaster</strong>.
                    Grupa ta jest zarezerwowana do wstępnej administracji: nie można jej modyfikować ani dodawać do niej członków.
                </p>
                <p class="text-muted mb-4">
                    System opiera się na <strong>uprawnieniach</strong> przypisanych do <strong>grup</strong>.
                    Członkowie dziedziczą prawa grup, do których należą.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-person-gear fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Pierwsza konfiguracja</h3>
                                <p class="text-muted small mb-2"><strong>Webmaster</strong> musi najpierw:</p>
                                <ul class="list-unstyled text-muted small ms-1">
                                    <li class="mb-1">① Utworzyć <strong>grupy</strong> (<em>PersonManager</em>)</li>
                                    <li class="mb-1">② Utworzyć <strong>członków</strong> (<em>PersonManager</em>)</li>
                                    <li>③ Przypisać członków do grup</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-palette fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Projekt strony</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>HomeDesigner</strong>: tworzenie/edycja strony głównej</li>
                                    <li class="mb-1"><strong>MenuDesigner</strong>: tworzenie i organizacja menu</li>
                                    <li><strong>Translator</strong>: tłumaczenie tekstów i stron serwisu</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Artykuły</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>Redactor</strong>: pisanie i edytowanie artykułów</li>
                                    <li class="mb-1"><strong>Editor</strong>: publikowanie artykułu widocznego dla wszystkich</li>
                                    <li>Redaktor może publikować, jeśli artykuł jest ograniczony do członków lub grupy</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Wydarzenia</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>EventDesigner</strong>: tworzenie typów wydarzeń</li>
                                    <li class="mb-1"><strong>EventManager</strong>: tworzenie wydarzeń</li>
                                    <li>Tylko <strong>twórca</strong> może modyfikować lub anulować swoje wydarzenie</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-envelope fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Komunikacja</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>CommunicationManager</strong>: wysyłanie e-maili do członków</li>
                                    <li>Niektóre wiadomości mogą być wysyłane automatycznie (artykuły, wydarzenia, hasło, kontakt)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Projekty Kanban</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-1"><strong>KanbanDesigner</strong>: tworzenie i edytowanie własnych projektów</li>
                                    <li>Inni członkowie z tym prawem mogą je tylko przeglądać</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-bar-chart-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Analityka strony</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>VisitorInsights</strong>: dostęp do narzędzi analizy odwiedzających</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <h2 class="h4 mb-3 fw-bold">Najważniejsze informacje</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span><strong>Żółty klucz</strong> jest widoczny tylko dla członków posiadających uprawnienia.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>Jedna strefa administracyjna → automatyczne przekierowanie.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>Na urządzeniach mobilnych: bezpośrednie skróty na tej stronie.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span><strong>Pomoc kontekstowa:</strong> dostępna w każdym module przez ikonę pomocy.</span>
            </div>
        </div>
    </section>
</div>');
INSERT INTO "Languages" VALUES (54,'Help_Designer','Designer help','Aide designer','Pomoc projektanta');
INSERT INTO "Languages" VALUES (55,'Help_EventManager','Event manager help','Aide gestionnaire d''événements','Pomoc animatora');
INSERT INTO "Languages" VALUES (56,'Help_Home','
<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Contextual Help: MyClub</h1>
        <p class="lead">Simplify the management of your association in just a few clicks.</p>
    </header>
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Application overview</h2>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <strong>Secure authentication</strong>
                                <p class="text-muted small">
                                    Email-based login.
                                    <span class="d-block mt-1 text-dark">
                                        👉 <em>First time logging in? Use "Create / reset my password" to initialize your account.</em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-newspaper"></i>
                            </div>
                            <div>
                                <strong>Article browsing</strong>
                                <p class="text-muted small">
                                    Read and share news written by the community.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <strong>Activity management</strong>
                                <p class="text-muted small">
                                    Register for activities and sync them with your personal calendar.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <div>
                                <strong>Preferences &amp; filters</strong>
                                <p class="text-muted small">
                                    Configure your favorite event types and availability for a tailored view.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <strong>Groups &amp; resources</strong>
                                <p class="text-muted small">
                                    Join specific groups to access their dedicated resources.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <strong>Member directory</strong>
                                <p class="text-muted small">
                                    Introduce yourself to other members by completing your profile.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr class="my-5">
    <section>
        <h2 class="h4 mb-4">Key points to remember</h2>
        <p class="text-muted">
            Navigation mainly happens through the top navigation bar.
        </p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-secondary me-3">[Logo]</span>
                <span>
                    Located in the top-left corner, it instantly brings you back to the <strong>home page</strong>.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-list fs-3 me-3"></i>
                <span>
                    <strong>Burger menu:</strong> On mobile, top-right, it reveals hidden navigation options.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">👻</span>
                <span><strong>Public mode:</strong> You are not logged in. Access is limited to public resources only.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">😊</span>
                <span><strong>Member mode:</strong> You are logged in. Full access to your groups resources.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-box-arrow-right fs-3 text-warning me-3"></i>
                <span><strong>Logout:</strong> Click here to securely end your session.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Help:</strong> This is where you will find all the information you need to use MyClub.
                </span>
            </div>
        </div>
    </section>
</div>
<div class="mt-4 text-center text-muted">
In the footer, 
  <strong><a href="https://myclub.ovh/menu/show/article/28">Tutorials</a></strong> will take you to the 
  <strong><i><u>MyClub</u></i></strong> website. 
  There you will find <strong>videos</strong>, <strong>articles</strong>, 
  a <strong>dictionary</strong>, and other resources to support you.
</div>
','
<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : MyClub</h1>
        <p class="lead">Simplifiez la gestion de votre vie associative en quelques clics.</p>
    </header>
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Présentation de l''application</h2>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <strong>Identification sécurisée</strong>
                                <p class="text-muted small">
                                    Identification par e-mail.
                                    <span class="d-block mt-1 text-dark">
                                        👉 <em>Première connexion ? Utilisez "Créer/modifier mon mot de passe" pour initialiser votre compte.</em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-newspaper"></i>
                            </div>
                            <div>
                                <strong>Visualisation des articles</strong>
                                <p class="text-muted small">Lisez et partagez les actualités rédigées par la communauté.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <strong>Gestion des activités</strong>
                                <p class="text-muted small">Inscrivez-vous aux activités et synchronisez votre agenda personnel.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <div>
                                <strong>Préférences &amp; Filtres</strong>
                                <p class="text-muted small">
                                    Paramétrez vos types d''événements favoris et vos disponibilités pour un affichage sur mesure.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <strong>Groupes &amp; Ressources</strong>
                                <p class="text-muted small">
                                    Rejoignez des groupes spécifiques pour accéder à leurs ressources dédiées.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <strong>Trombinoscope</strong>
                                <p class="text-muted small">
                                    Présentez-vous aux autres membres en complétant votre fiche.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr class="my-5">
    <section>
        <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
        <p class="text-muted">
            La navigation se fait principalement via la barre située en haut de l''écran.
        </p>

        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-secondary me-3">[Logo]</span>
                <span>
                    Situé en haut à gauche, il vous ramène instantanément sur la <strong>page d''accueil</strong>.
                </span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-list fs-3 me-3"></i>
                <span>
                    <strong>Menu Burger :</strong> Sur mobile, en haut à droite, il permet d''afficher les options de navigation masquées.
                </span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">👻</span>
                <span><strong>Mode Public :</strong> Vous n''êtes pas connecté. Accès limité aux ressources publiques uniquement.</span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">😊</span>
                <span><strong>Mode Membre :</strong> Vous êtes connecté. Accès complet aux ressources de vos groupes.</span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-box-arrow-right fs-3 text-warning me-3"></i>
                <span><strong>Déconnexion :</strong> Cliquez sur ce bouton pour fermer votre session sécurisée.</span>
            </div>

            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Aide :</strong> C''est ici que vous trouverez toutes les informations pour naviguer sur MyClub.
                </span>
            </div>
        </div>
    </section>
</div>
<div class="mt-4 text-center text-muted">
En pied de page 
  <strong><a href="https://myclub.ovh/menu/show/article/28">Tutoriels</a></strong> vous conduit vers le site de 
  <strong><i><u>MyClub</u></i></strong>. 
  Vous y trouverez des <strong>vidéos</strong>, des <strong>articles</strong>, 
  un <strong>dictionnaire</strong> et d’autres ressources pour vous accompagner.
</div>
','<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Pomoc kontekstualna: MyClub</h1>
        <p class="lead">Uprość zarządzanie swoim stowarzyszeniem w kilku kliknięciach.</p>
    </header>
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Prezentacja aplikacji</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <strong>Bezpieczne logowanie</strong>
                                <p class="text-muted small">
                                    Logowanie przez e-mail.
                                    <span class="d-block mt-1 text-dark">
                                        👉 <em>Pierwszy raz? Użyj opcji „Utwórz / zresetuj hasło", aby zainicjować swoje konto.</em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-newspaper"></i>
                            </div>
                            <div>
                                <strong>Przeglądanie artykułów</strong>
                                <p class="text-muted small">
                                    Czytaj i udostępniaj aktualności pisane przez społeczność.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <strong>Zarządzanie aktywnościami</strong>
                                <p class="text-muted small">
                                    Zapisuj się na aktywności i synchronizuj je z osobistym kalendarzem.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <div>
                                <strong>Preferencje i filtry</strong>
                                <p class="text-muted small">
                                    Skonfiguruj ulubione typy wydarzeń i dostępność, aby uzyskać spersonalizowany widok.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <strong>Grupy i zasoby</strong>
                                <p class="text-muted small">
                                    Dołącz do konkretnych grup, aby uzyskać dostęp do ich dedykowanych zasobów.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <strong>Katalog członków</strong>
                                <p class="text-muted small">
                                    Przedstaw się innym członkom, uzupełniając swój profil.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr class="my-5">
    <section>
        <h2 class="h4 mb-4">Co warto zapamiętać</h2>
        <p class="text-muted">
            Nawigacja odbywa się głównie przez górny pasek nawigacyjny.
        </p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-secondary me-3">[Logo]</span>
                <span>
                    W lewym górnym rogu — natychmiast wraca na <strong>stronę główną</strong>.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-list fs-3 me-3"></i>
                <span>
                    <strong>Menu hamburger:</strong> Na urządzeniach mobilnych, w prawym górnym rogu, odsłania ukryte opcje nawigacji.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">👻</span>
                <span><strong>Tryb publiczny:</strong> Nie jesteś zalogowany/a. Dostęp ograniczony wyłącznie do publicznych zasobów.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">😊</span>
                <span><strong>Tryb członka:</strong> Jesteś zalogowany/a. Pełny dostęp do zasobów swoich grup.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-box-arrow-right fs-3 text-warning me-3"></i>
                <span><strong>Wyloguj:</strong> Kliknij tutaj, aby bezpiecznie zakończyć sesję.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Pomoc:</strong> Tutaj znajdziesz wszystkie informacje potrzebne do korzystania z MyClub.
                </span>
            </div>
        </div>
    </section>
</div>
<div class="mt-4 text-center text-muted">
W stopce,
  <strong><a href="https://myclub.ovh/menu/show/article/28">Samouczki</a></strong> przekierują Cię na stronę
  <strong><i><u>MyClub</u></i></strong>.
  Znajdziesz tam <strong>filmy</strong>, <strong>artykuły</strong>,
  <strong>słownik</strong> i inne zasoby pomocnicze.
</div>');
INSERT INTO "Languages" VALUES (57,'Help_PersonManager','People manager help','Aide gestionnaire de personnes','Pomoc zarządcy osób');
INSERT INTO "Languages" VALUES (58,'Help_Redactor','Redactor help','Aide rédateur','Pomoc redaktora');
INSERT INTO "Languages" VALUES (59,'Help_User','User help','Aide utilisateur','Pomoc użytkownika');
INSERT INTO "Languages" VALUES (60,'Help_VisitorInsights','Visitor insights help','Aide statistiques visiteurs','Pomoc statystyk odwiedzających');
INSERT INTO "Languages" VALUES (61,'Help_Webmaster','Webmater help','Aide webmaster','Pomoc webmastera');
INSERT INTO "Languages" VALUES (62,'Home_Header','Home header','<div>
<h4>Un lieu de partage, d’échange et d’entraide autour du monde apicole, dans un esprit de défense de protection de l’abeille et de l’environnement en respectant la biodiversité</h4>
</div>','Nagłówek strony głównej');
INSERT INTO "Languages" VALUES (63,'Home_Footer','Home footer','<div class="alert alert-info">
<div class="container text-center">
<p>Vous avez des ruches et vous voulez apprendre &agrave; mieux vous en occuper ? Vous &ecirc;tes professionnels, passionn&eacute;s ou curieux d&rsquo;en apprendre d&rsquo;avantage sur le monde apicole, participer &agrave; la pr&eacute;servation de l&rsquo;abeille en pr&eacute;servant environnement ?</p>
<p class="fw-semibold d-inline-flex align-items-center gap-3">Suivez l&rsquo;association sur les r&eacute;seaux sociaux <a href="https://www.facebook.com/LesAmisDesAbeilles21" target="_blank" rel="noopener noreferrer" class="text-dark fs-4" aria-label="Facebook"> <i class="bi bi-facebook"></i> </a> <a href="https://www.instagram.com/lesamisdesabeilles21" target="_blank" rel="noopener noreferrer" class="text-dark fs-4" aria-label="Instagram"> <i class="bi bi-instagram"></i> </a></p>
<p class="small text-muted mb-1">&copy; 2026 &ndash; Les Amis des Abeilles 21</p>
<p class="small text-muted">Association apicole &ndash; Rucher &eacute;cole et sensibilisation &agrave; la protection des abeilles</p>
</div>
</div>','Stopka strony głównej');
INSERT INTO "Languages" VALUES (64,'Error403','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">🚫 Error 403 – Unauthorized Access</h1>
    
    <p class="mt-3">
      It looks like you’re trying to access a protected page.<br>
      Don’t worry! If this page appears right after opening your browser, it’s probably because:
    </p>
    <ul class="text-start mx-auto d-inline-block">
      <li>Your browser automatically reopened the <strong>last pages visited</strong>.</li>
      <li>During your last visit to our site, you did not <strong>log out</strong>.</li>
    </ul>
    <p class="mt-3">
      👉 In this case, it’s perfectly normal.
    </p>
    <p class="fw-bold">
      💡 Tip: If you check the <em>“Remember me”</em> option when logging in, you’ll be automatically reconnected next time, and this page won’t show up anymore.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Other possible situations:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ If the application itself triggered this error: <strong>please notify the webmaster</strong>.</li>
      <li>➡️ If you tried to reach a page by typing its address directly: nice try 😉 but this page requires specific permissions.</li>
      <li>➡️ If you manage to display protected information <strong>without seeing this page</strong>: <strong>please notify the webmaster immediately</strong> so it can be fixed.</li>
    </ul>
	  
	<a href="/" class="btn btn-primary mt-3">🏠 Back to homepage</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">🚫 Erreur 403 – Accès non autorisé</h1>
    
    <p class="mt-3">
      Il semble que vous essayiez d’accéder à une page protégée.<br>
      Pas de panique ! Si cette page s’affiche juste après l’ouverture de votre navigateur, c’est probablement parce que :
    </p>
    <ul class="text-start mx-auto d-inline-block">
      <li>Votre navigateur a rouvert automatiquement les <strong>dernières pages visitées</strong>.</li>
      <li>Lors de votre dernière visite sur notre site, vous ne vous étiez pas <strong>déconnecté(e)</strong>.</li>
    </ul>
    <p class="mt-3">
      👉 Dans ce cas, c’est tout à fait normal.
    </p>
    <p class="fw-bold">
      💡 Astuce : Si vous cochez l’option <em>« Se souvenir de moi »</em> lors de votre connexion, vous serez reconnecté(e) automatiquement la prochaine fois, et cette page ne s’affichera plus.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Autres situations possibles :</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Si c’est l’application qui a provoqué cette erreur : <strong>merci de prévenir le webmaster</strong>.</li>
      <li>➡️ Si vous avez essayé d’accéder à une page en tapant directement son adresse : bien tenté 😉 mais cette page nécessite des droits spécifiques.</li>
      <li>➡️ Si vous parvenez à afficher des informations protégées <strong>sans voir cette page</strong> : <strong>merci de prévenir immédiatement le webmaster</strong> pour correction.</li>
    </ul>
	
    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">🚫 Błąd 403 – Brak dostępu</h1>

    <p class="mt-3">
      Wygląda na to, że próbujesz uzyskać dostęp do chronionej strony.<br>
      Spokojnie! Jeśli ta strona pojawia się zaraz po otwarciu przeglądarki, prawdopodobnie dlatego że:
    </p>
    <ul class="text-start mx-auto d-inline-block">
      <li>Twoja przeglądarka automatycznie otworzyła <strong>ostatnio odwiedzone strony</strong>.</li>
      <li>Podczas ostatniej wizyty na naszej stronie nie <strong>wylogowałeś/aś się</strong>.</li>
    </ul>
    <p class="mt-3">
      👉 W takim przypadku jest to całkowicie normalne.
    </p>
    <p class="fw-bold">
      💡 Wskazówka: Jeśli zaznaczysz opcję <em>„Zapamiętaj mnie"</em> podczas logowania, następnym razem zostaniesz automatycznie zalogowany/a i ta strona się nie pojawi.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Inne możliwe sytuacje:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Jeśli to aplikacja wywołała ten błąd: <strong>prosimy poinformować webmastera</strong>.</li>
      <li>➡️ Jeśli próbowałeś/aś wpisać adres strony bezpośrednio w przeglądarce: niezły pomysł 😉 ale ta strona wymaga określonych uprawnień.</li>
      <li>➡️ Jeśli udało Ci się wyświetlić chronione informacje <strong>bez pojawienia się tej strony</strong>: <strong>natychmiast poinformuj webmastera</strong>, aby to naprawił.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Powrót do strony głównej</a>
  </div>
</div>');
INSERT INTO "Languages" VALUES (65,'Error404','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">🔍 Error 404 – Page not found</h1>
    
    <p class="mt-3">
      The page you are looking for doesn’t exist or is no longer available.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Possible causes:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ The application mistakenly sent you here: <strong>please notify the webmaster</strong>.</li>
      <li>➡️ You tried to guess an address in your browser bar: <em>nice try 😉 but this page doesn’t exist</em>.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Back to homepage</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">🔍 Erreur 404 – Page introuvable</h1>
    
    <p class="mt-3">
      La page que vous cherchez n’existe pas ou n’est plus disponible.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Causes possibles :</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ L’application vous a dirigé ici par erreur : <strong>merci de prévenir le webmaster</strong>.</li>
      <li>➡️ Vous avez tenté de deviner une adresse dans la barre du navigateur : <em>bien tenté 😉 mais cette page n’existe pas</em>.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">🔍 Błąd 404 – Strona nie znaleziona</h1>
    <p class="mt-3">
      Strona, której szukasz, nie istnieje lub nie jest już dostępna.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Możliwe przyczyny:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Aplikacja omyłkowo Cię tu przekierowała: <strong>prosimy poinformować webmastera</strong>.</li>
      <li>➡️ Próbowałeś/aś zgadnąć adres w pasku przeglądarki: <em>niezły pomysł 😉 ale ta strona nie istnieje</em>.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót do strony głównej</a>
  </div>
</div>');
INSERT INTO "Languages" VALUES (66,'Error500','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">💥 Error 500 – Internal Server Error</h1>
    
    <p class="mt-3">
      Oops… something went wrong on our side.  
      This error is caused by an internal problem in the application.
    </p>

    <hr class="my-4">

    <h5>ℹ️ What to do?</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ <strong>Please notify the webmaster</strong> so the issue can be fixed.</li>
      <li>➡️ You can also try again later — sometimes the server just needs a little coffee ☕.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Back to homepage</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">💥 Erreur 500 – Erreur interne du serveur</h1>
    
    <p class="mt-3">
      Oups… quelque chose s’est mal passé de notre côté.  
      Cette erreur est due à un problème interne de l’application.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Que faire ?</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ <strong>Prévenez le webmaster</strong> afin qu’il puisse corriger le problème.</li>
      <li>➡️ Vous pouvez aussi réessayer un peu plus tard, parfois le serveur a juste besoin d’un petit café ☕.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">💥 Błąd 500 – Wewnętrzny błąd serwera</h1>
    <p class="mt-3">
      Ups… coś poszło nie tak po naszej stronie.<br>
      Ten błąd wynika z wewnętrznego problemu aplikacji.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Co zrobić?</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ <strong>Prosimy poinformować webmastera</strong>, aby mógł naprawić problem.</li>
      <li>➡️ Możesz też spróbować ponownie za chwilę — czasem serwer po prostu potrzebuje kawy ☕.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót do strony głównej</a>
  </div>
</div>');
INSERT INTO "Languages" VALUES (67,'LoginRequired','<div class="alert alert-warning" role="alert">
  <p>🔒 <strong>Oops… this resource is reserved for logged-in members!</strong></p>
  <p>You need to log in to access it.</p>
  <p>💡 By choosing the "Remember me" option, your browser will roll out the 🟥red carpet🟥 next time, no password required.</p>
</div>','<div class="alert alert-warning" role="alert">
  <!-- Version française -->
  <p>🔒 <strong>Oups… cette ressource est réservée aux membres connectés !</strong></p>
  <p>Il faut se connecter pour la découvrir.</p>
  <p>💡 Avec l''option « Se souvenir de moi », ton navigateur te déroulera le 🟥tapis rouge🟥 la prochaine fois, sans passer par la case mot de passe.</p>
</div>','<div class="alert alert-warning" role="alert">
  <p>🔒 <strong>Ups… ten zasób jest zarezerwowany dla zalogowanych członków!</strong></p>
  <p>Musisz się zalogować, aby uzyskać do niego dostęp.</p>
  <p>💡 Wybierając opcję „Zapamiętaj mnie", Twoja przeglądarka następnym razem rozłoży przed Tobą 🟥czerwony dywan🟥 — bez podawania hasła.</p>
</div>');
INSERT INTO "Languages" VALUES (68,'Error503','<div class="text-center full-screen d-flex flex-column justify-content-center align-items-center">
    <div class="emoji">🚧</div>
	<h1 class="mt-4">Site Under Maintenance</h1>
	<p class="text-muted">You will be redirected to the homepage in 30 seconds...</p>
	<a href="/" class="btn btn-primary mt-3">Return to Homepage Now</a>
</div>
<style>
    .full-screen {
      height: 100vh;
    }
    .emoji {
      font-size: 10rem;
    }
</style>','<div class="text-center full-screen d-flex flex-column justify-content-center align-items-center">
    <div class="emoji">🚧</div>
    <h1 class="mt-4">Site en maintenance</h1>
    <p class="text-muted">Vous serez redirigé vers l’accueil dans 30 secondes...</p>
    <a href="/" class="btn btn-primary mt-3">Retourner à l’accueil maintenant</a>
</div>
<style>
    .full-screen {
      height: 100vh;
    }
    .emoji {
      font-size: 10rem;
    }
</style>','<div class="text-center full-screen d-flex flex-column justify-content-center align-items-center">
    <div class="emoji">🚧</div>
    <h1 class="mt-4">Strona w trakcie konserwacji</h1>
    <p class="text-muted">Za 30 sekund zostaniesz przekierowany/a na stronę główną...</p>
    <a href="/" class="btn btn-primary mt-3">Wróć teraz na stronę główną</a>
</div>
<style>
    .full-screen {
      height: 100vh;
    }
    .emoji {
      font-size: 10rem;
    }
</style>');
INSERT INTO "Languages" VALUES (69,'message_password_reset_sent','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-success">📧 Email sent!</h1>
    
    <p class="mt-3">
      An email with a link to <strong>create a new password</strong> has been sent to the address you entered.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Please note:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Check your <strong>spam</strong> or <strong>junk mail</strong> folder if you don’t see the message in your inbox.</li>
      <li>➡️ Simply click the link in the email to set your new password.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Back to Home</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-success">📧 Courriel envoyé !</h1>
    
    <p class="mt-3">
      Un message contenant un lien pour <strong>créer un nouveau mot de passe</strong> vient d’être envoyé à l’adresse courriel que vous avez indiquée.
    </p>

    <hr class="my-4">

    <h5>ℹ️ À savoir :</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Pensez à vérifier votre dossier <strong>spam</strong> ou <strong>courrier indésirable</strong> si vous ne trouvez pas le message.</li>
      <li>➡️ Cliquez simplement sur le lien présent dans le courriel pour définir votre nouveau mot de passe.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-success">📧 E-mail wysłany!</h1>
    <p class="mt-3">
      E-mail z linkiem do <strong>utworzenia nowego hasła</strong> został wysłany na podany adres.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Uwaga:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Sprawdź folder <strong>spam</strong> lub <strong>niechciana poczta</strong>, jeśli nie widzisz wiadomości w skrzynce głównej.</li>
      <li>➡️ Kliknij po prostu link w e-mailu, aby ustawić nowe hasło.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót na stronę główną</a>
  </div>
</div>');
INSERT INTO "Languages" VALUES (70,'message_password_reset_failed','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">⚠️ Email could not be sent</h1>

    <p class="mt-3">
      The password reset email <strong>could not be sent</strong> to the address you entered.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Please check the following:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Make sure the email address you entered is correct and registered in our system.</li>
      <li>➡️ If the issue continues, please contact the <strong>webmaster</strong> or site administrator.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Back to Home</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">⚠️ Échec de l’envoi du courriel</h1>

    <p class="mt-3">
      Le message de réinitialisation du mot de passe <strong>n’a pas pu être envoyé</strong> à l’adresse courriel indiquée.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Vérifiez les points suivants :</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Assurez-vous que l’adresse courriel saisie est correcte et enregistrée dans notre système.</li>
      <li>➡️ Si le problème persiste, contactez le <strong>webmaster</strong> ou l’administrateur du site.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">⚠️ Nie udało się wysłać e-maila</h1>
    <p class="mt-3">
      E-mail z resetowaniem hasła <strong>nie mógł zostać wysłany</strong> na podany adres.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Sprawdź następujące kwestie:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Upewnij się, że podany adres e-mail jest poprawny i zarejestrowany w naszym systemie.</li>
      <li>➡️ Jeśli problem się powtarza, skontaktuj się z <strong>webmasterem</strong> lub administratorem strony.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót na stronę główną</a>
  </div>
</div>');
INSERT INTO "Languages" VALUES (71,'message_email_unknown','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">📧 Unknown Email Address</h1>

    <p class="mt-3">
      The email address you entered <strong>does not exist</strong> in our system.
    </p>

    <hr class="my-4">

    <h5>🔍 Please check the following:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Make sure you typed your email address correctly, without any spelling mistakes.</li>
      <li>➡️ If you have never created an account, please contact the site administrator to request one.</li>
      <li>➡️ If you are unsure, contact the <strong>webmaster</strong> or the club administrator.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Back to Home</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">📧 Adresse courriel inconnue</h1>

    <p class="mt-3">
      L’adresse courriel que vous avez saisie <strong>n’existe pas</strong> dans notre système.
    </p>

    <hr class="my-4">

    <h5>🔍 Vérifiez les points suivants :</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Assurez-vous que vous avez bien saisi votre adresse courriel sans erreur de frappe.</li>
      <li>➡️ Si vous n’avez jamais créé de compte, vous pouvez en demander un auprès de l’administrateur du site.</li>
      <li>➡️ En cas de doute, contactez le <strong>webmaster</strong> ou le responsable du club.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Retour à l’accueil</a>
  </div>
</div>','<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">📧 Nieznany adres e-mail</h1>
    <p class="mt-3">
      Podany adres e-mail <strong>nie istnieje</strong> w naszym systemie.
    </p>
    <hr class="my-4">
    <h5>🔍 Sprawdź następujące kwestie:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Upewnij się, że adres e-mail został wpisany poprawnie, bez literówek.</li>
      <li>➡️ Jeśli nigdy nie tworzyłeś/aś konta, skontaktuj się z administratorem strony w celu jego założenia.</li>
      <li>➡️ W razie wątpliwości, skontaktuj się z <strong>webmasterem</strong> lub administratorem klubu.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót na stronę główną</a>
  </div>
</div>');
INSERT INTO "Languages" VALUES (72,'connections','Connections','Connexions','Połączenia');
INSERT INTO "Languages" VALUES (73,'ErrorLyricsFileNotFound','<div class=''container text-center mt-5''>
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
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Back to homepage</a>
                    </div>
                </div>','<div class=''container text-center mt-5''>
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
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Retour à l’accueil</a>
                    </div>
                </div>','<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>📁 Plik tekstu nie znaleziony</h1>
                        <p class=''mt-3''>
                        Nie można odnaleźć pliku z tekstem piosenki.<br>
                        Upewnij się, że piosenka istnieje i że jej plik z tekstem (<code>.lrc</code>) ma właściwą nazwę.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Nazwa pliku może nie odpowiadać nazwie piosenki.</li>
                        <li>➡️ Plik mógł zostać przeniesiony lub usunięty.</li>
                        </ul>
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Powrót na stronę główną</a>
                    </div>
                </div>');
INSERT INTO "Languages" VALUES (74,'ErrorLyricsFileNotReadable','<div class=''container text-center mt-5''>
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
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Back to homepage</a>
                    </div>
                </div>','''<div class=''container text-center mt-5''>
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
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Retour à l’accueil</a>
                    </div>
                </div>','<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>🔒 Plik tekstu niedostępny</h1>
                        <p class=''mt-3''>
                        Plik z tekstem piosenki istnieje, ale nie można go odczytać.<br>
                        Sprawdź uprawnienia pliku lub skontaktuj się z administratorem.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Plik może nie mieć odpowiednich uprawnień do odczytu.</li>
                        <li>➡️ Plik może być zablokowany lub uszkodzony.</li>
                        </ul>
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Powrót na stronę główną</a>
                    </div>
                </div>');
INSERT INTO "Languages" VALUES (75,'ErrorLyricsFileReadError','''<div class=''container text-center mt-5''>
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
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Back to homepage</a>
                    </div>
                </div>','<div class=''container text-center mt-5''>
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
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Retour à l’accueil</a>
                    </div>
                </div>','<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>⚠️ Błąd odczytu pliku tekstu</h1>
                        <p class=''mt-3''>
                        Wystąpił nieoczekiwany błąd podczas odczytu pliku z tekstem piosenki.<br>
                        Sprawdź zawartość pliku lub spróbuj ponownie później.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Plik może być uszkodzony.</li>
                        <li>➡️ Serwer napotkał tymczasowy błąd operacji wejścia/wyjścia.</li>
                        </ul>
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Powrót na stronę główną</a>
                    </div>
                </div>');
INSERT INTO "Languages" VALUES (76,'save','Save','Enregistrer','Zapisz');
INSERT INTO "Languages" VALUES (77,'cancel','Cancel','Annuler','Anuluj');
INSERT INTO "Languages" VALUES (78,'User','<div class="alert alert-info mt-2">
    <h5 class="alert-heading">Welcome to your personal space</h5>
    <p>
        Here, you can view and update your information.
    </p>
    <p class="mb-0">
        👉 If the ☰ button is visible in the top right corner, click on it to access the menu.
    </p>
    <p class="mb-0">
        💡 You can also click directly on the links below to access the different options.
    </p>
</div>
<div class="user-links mt-3 mb-3">
    <div class="d-flex flex-wrap gap-3">
        <a href="/user/account" class="{if $page == ''account''}active{/if} text-decoration-none">👤 Account</a>
        <a href="/user/availabilities" class="{if $page == ''account''}active{/if} text-decoration-none">🕒 Availability</a>
        <a href="/user/groups" class="{if $page == ''account''}active{/if} text-decoration-none">🔐 Groups</a>
        <a href="/user/preferences" class="{if $page == ''account''}active{/if} text-decoration-none">⭐ Preferences</a>
        <a href="/user/notifications" class="{if $page == ''account''}active{/if} text-decoration-none">🔔 Notifications</a>
        <a href="/user/statistics" class="{if $page == ''account''}active{/if} text-decoration-none">📊 Statistics</a>
        <a href="/user/directory" class="{if $page == ''account''}active{/if} text-decoration-none">🎭 Member Directory</a>
        <a href="/user/news" class="{if $page == ''account''}active{/if} text-decoration-none">📰 News</a>
        <a href="/user/messages" class="{if $page == ''account''}active{/if} text-decoration-none">💬 Messages</a>
        <a href="/user/notepad" class="{if $page == ''account''}active{/if} text-decoration-none">🗒️ Notepad</a>
        <a href="/user/connections" class="{if $page == ''account''}active{/if} text-decoration-none">🕸️ Connections</a>
    </div>
</div>','<div class="alert alert-info mt-2">
    <h5 class="alert-heading">Bienvenue dans votre espace personnel</h5>
    <p>
        Ici, vous pouvez consulter et mettre à jour vos informations.
    </p>
    <p class="mb-0">
        👉 Si le bouton ☰ est présent en haut à droite, il faut cliquer dessus pour accéder au menu.
    </p>
        <p class="mb-0">
        💡 Vous pouvez aussi cliquer directement sur les liens ci-dessous pour accéder aux différentes options.
    </p>
</div>
<div class="user-links mt-3 mb-3">
    <div class="d-flex flex-wrap gap-3">
        <a href="/user/account" class="{if $page == ''account''}active{/if} text-decoration-none">👤 Compte</a>
        <a href="/user/availabilities" class="{if $page == ''account''}active{/if} text-decoration-none">🕒 Disponibilités</a>
        <a href="/user/groups" class="{if $page == ''account''}active{/if} text-decoration-none">🔐 Groupes</a>
        <a href="/user/preferences" class="{if $page == ''account''}active{/if} text-decoration-none">⭐ Préférences</a>
        <a href="/user/notifications" class="{if $page == ''account''}active{/if} text-decoration-none">🔔 Notifications</a>
        <a href="/user/statistics" class="{if $page == ''account''}active{/if} text-decoration-none">📊 Statistiques</a>
        <a href="/user/directory" class="{if $page == ''account''}active{/if} text-decoration-none">🎭 Trombinoscope</a>
        <a href="/user/news" class="{if $page == ''account''}active{/if} text-decoration-none">📰 News</a>
        <a href="/user/messages" class="{if $page == ''account''}active{/if} text-decoration-none">💬 Messages</a>
        <a href="/user/notepad" class="{if $page == ''account''}active{/if} text-decoration-none">🗒️ Bloc-notes</a>
        <a href="/user/connections" class="{if $page == ''account''}active{/if} text-decoration-none">🕸️ Connexions</a>
    </div>
</div>','<div class="alert alert-info mt-2">
    <h5 class="alert-heading">Witaj w swoim obszarze osobistym</h5>
    <p>
        Tutaj możesz przeglądać i aktualizować swoje informacje.
    </p>
    <p class="mb-0">
        👉 Jeśli przycisk ☰ jest widoczny w prawym górnym rogu, kliknij go, aby uzyskać dostęp do menu.
    </p>
    <p class="mb-0">
        💡 Możesz też kliknąć bezpośrednio na poniższe linki, aby przejść do różnych opcji.
    </p>
</div>
<div class="user-links mt-3 mb-3">
    <div class="d-flex flex-wrap gap-3">
        <a href="/user/account" class="{if $page == ''account''}active{/if} text-decoration-none">👤 Konto</a>
        <a href="/user/availabilities" class="{if $page == ''account''}active{/if} text-decoration-none">🕒 Dostępności</a>
        <a href="/user/groups" class="{if $page == ''account''}active{/if} text-decoration-none">🔐 Grupy</a>
        <a href="/user/preferences" class="{if $page == ''account''}active{/if} text-decoration-none">⭐ Preferencje</a>
        <a href="/user/notifications" class="{if $page == ''account''}active{/if} text-decoration-none">🔔 Powiadomienia</a>
        <a href="/user/statistics" class="{if $page == ''account''}active{/if} text-decoration-none">📊 Statystyki</a>
        <a href="/user/directory" class="{if $page == ''account''}active{/if} text-decoration-none">🎭 Katalog członków</a>
        <a href="/user/news" class="{if $page == ''account''}active{/if} text-decoration-none">📰 Aktualności</a>
        <a href="/user/messages" class="{if $page == ''account''}active{/if} text-decoration-none">💬 Wiadomości</a>
        <a href="/user/notepad" class="{if $page == ''account''}active{/if} text-decoration-none">🗒️ Notatnik</a>
        <a href="/user/connections" class="{if $page == ''account''}active{/if} text-decoration-none">🕸️ Połączenia</a>
    </div>
</div>');
INSERT INTO "Languages" VALUES (79,'notepad','Notepad','Bloc-notes','Notatnik');
INSERT INTO "Languages" VALUES (80,'Admin','<div class="alert alert-warning mt-2">
        <h5 class="alert-heading">Administration access</h5>
        <p>
            From here, you can access the administration areas according to your permissions.
        </p>
        <p class="mb-0">
            🔐 Only the sections you are authorized to access are displayed.
        </p>
    </div>
    <div class="admin-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            {if $isEventManager}
            <li class="nav-item"><a class="nav-link" href="/eventManager">🗓️ Event management</a></li>
            {/if}

            {if $isDesigner}
            <li class="nav-item"><a class="nav-link" href="/designer">🎨 Design</a></li>
            {/if}

            {if $isRedactor}
            <li class="nav-item"><a class="nav-link" href="/redactor">✍️ Content writing</a></li>
            {/if}

            {if $isPersonManager}
            <li class="nav-item"><a class="nav-link" href="/personManager">📇 Member management</a></li>
            {/if}

            {if $isVisitorInsights}
            <li class="nav-item"><a class="nav-link" href="/visitorInsights">🔍 Visitor insights</a></li>
            {/if}

            {if $isWebmaster}
            <li class="nav-item"><a class="nav-link" href="/webmaster">🛠️ Website administration</a></li>
            {/if}
            
        </ul>
    </div>','<div class="alert alert-warning mt-2">
        <h5 class="alert-heading">Accès à l’administration</h5>
        <p>
            Depuis cette page, vous pouvez accéder aux différentes zones d’administration selon vos droits.
        </p>
        <p class="mb-0">
            🔐 Seules les sections auxquelles vous êtes autorisé sont affichées.
        </p>
    </div>

    <div class="admin-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            {if $isEventManager}
            <li class="nav-item"><a class="nav-link" href="/eventManager">🗓️ Gestion des événements</a></li>
            {/if}

            {if $isDesigner}
            <li class="nav-item"><a class="nav-link" href="/designer">🎨 Design</a></li>
            {/if}

            {if $isRedactor}
            <li class="nav-item"><a class="nav-link" href="/redactor">✍️ Rédaction de contenu</a></li>
            {/if}

            {if $isPersonManager}
            <li class="nav-item"><a class="nav-link" href="/personManager">📇 Gestion des membres</a></li>
            {/if}

            {if $isVisitorInsights}
            <li class="nav-item"><a class="nav-link" href="/visitorInsights">🔍 Analyse des visiteurs</a></li>
            {/if}

            {if $isWebmaster}
            <li class="nav-item"><a class="nav-link" href="/webmaster">🛠️ Administration du site</a></li>
            {/if}

        </ul>
    </div>','<div class="alert alert-warning mt-2">
        <h5 class="alert-heading">Dostęp do administracji</h5>
        <p>
            Stąd możesz uzyskać dostęp do obszarów administracyjnych zgodnie z Twoimi uprawnieniami.
        </p>
        <p class="mb-0">
            🔐 Wyświetlane są tylko sekcje, do których masz dostęp.
        </p>
    </div>
    <div class="admin-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            {if $isEventManager}
            <li class="nav-item">
                <a class="nav-link" href="/eventManager">🗓️ Zarządzanie wydarzeniami</a>
            </li>
            {/if}

            {if $isDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/designer">🎨 Design</a>
            </li>
            {/if}

            {if $isRedactor}
            <li class="nav-item">
                <a class="nav-link" href="/redactor">✍️ Redakcja treści</a>
            </li>
            {/if}

            {if $isPersonManager}
            <li class="nav-item">
                <a class="nav-link" href="/personManager">📇 Zarządzanie członkami</a>
            </li>
            {/if}

            {if $isVisitorInsights}
            <li class="nav-item">
                <a class="nav-link" href="/visitorInsights">🔍 Analiza odwiedzających</a>
            </li>
            {/if}

            {if $isWebmaster}
            <li class="nav-item">
                <a class="nav-link" href="/webmaster">🛠️ Administracja stroną</a>
            </li>
            {/if}
        </ul>
    </div>');
INSERT INTO "Languages" VALUES (81,'Designer','<div class="alert alert-info mt-2">
            <h5 class="alert-heading">Design administration</h5>
            <p>This area allows you to configure the visual and structural elements of the application.</p>
            <p class="mb-0">🎨 Only the design tools you are authorized to use are displayed below.</p>
        </div>
        <div class="designer-links mt-3 mb-3">
            <ul class="nav nav-pills gap-3">
                {if $isEventDesigner}
                <li class="nav-item"><a class="nav-link" href="/eventTypes">🗓️ Event types and attributes</a></li>
                <li class="nav-item"><a class="nav-link" href="/needs">📋 Event-related needs</a></li>
                {/if}
                {if $isHomeDesigner}
                <li class="nav-item"><a class="nav-link" href="/settings">🔧 Customization</a></li>
                <li class="nav-item"><a class="nav-link" href="/designs">🧠 Designs</a></li>
                {/if}
                {if $isKanbanDesigner}
                <li class="nav-item"><a class="nav-link" href="/kanban">🟨 Kanban</a></li>
                {/if}
                {if $isMenuDesigner}
                <li class="nav-item"><a class="nav-link" href="/menu">📑 Navigation bars</a></li>
                {/if}
            </ul>
        </div>','<div class="alert alert-info mt-2">
            <h5 class="alert-heading">Administration du design</h5>
            <p>Cette zone permet de configurer les éléments visuels et structurels de l’application.</p>
            <p class="mb-0">🎨 Seuls les outils de conception auxquels vous avez accès sont affichés ci-dessous.</p>
        </div>
        <div class="designer-links mt-3 mb-3">
            <ul class="nav nav-pills gap-3">
                {if $isEventDesigner}
                <li class="nav-item"><a class="nav-link" href="/eventTypes">🗓️ Les types d''événements et leurs attributs</a></li>
                <li class="nav-item"><a class="nav-link" href="/needs">📋 Les besoins associés aux événements</a></li>
                {/if}
                {if $isHomeDesigner}
                <li class="nav-item"><a class="nav-link" href="/settings">🔧 Personnalisation</a></li>
                <li class="nav-item"><a class="nav-link" href="/designs">🧠 Les designs</a></li>
                {/if}
                {if $isKanbanDesigner}
                <li class="nav-item"><a class="nav-link" href="/kanban">🟨 Kanban</a></li>
                {/if}
                {if $isMenuDesigner}
                <li class="nav-item"><a class="nav-link" href="/menu">📑 Les barres de navigations</a></li>
                {/if}
            </ul>
        </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Administracja projektem</h5>
        <p>
            Ten obszar umożliwia konfigurację elementów wizualnych i strukturalnych aplikacji.
        </p>
        <p class="mb-0">
            🎨 Poniżej wyświetlane są tylko narzędzia projektowe, do których masz dostęp.
        </p>
    </div>

    <div class="designer-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            {if $isEventDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/eventTypes">🗓️ Typy wydarzeń i atrybuty</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/needs">📋 Potrzeby związane z wydarzeniami</a>
            </li>
            {/if}

            {if $isHomeDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/settings">🔧 Personalizacja</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/designs">🧠 Projekty</a>
            </li>
            {/if}

            {if $isKanbanDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/kanban">🟨 Kanban</a>
            </li>
            {/if}

            {if $isNavbarDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/navbar">📑 Pasy nawigacyjne</a>
            </li>
            {/if}

        </ul>
    </div>');
INSERT INTO "Languages" VALUES (82,'EventManager','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Event management</h5>
        <p>
            This area allows you to manage events, schedules and participant communication.
        </p>
        <p class="mb-0">
            🗓️ Use the tools below to plan, monitor and analyze your events.
        </p>
    </div>

    <div class="event-manager-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/weekEvents">🗓️ Weekly calendar</a></li>
            <li class="nav-item"><a class="nav-link" href="/nextEvents">📅 Upcoming events</a></li>
            <li class="nav-item"><a class="nav-link" href="/events/guest">📩 Send an invitation</a></li>
            <li class="nav-item"><a class="nav-link" href="/emails">📧 Get emails</a></li>
            <li class="nav-item"><a class="nav-link" href="/events/crossTab">🧮 Pivot table</a></li>
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Gestion des événements</h5>
        <p>
            Cette zone vous permet de gérer les événements, les plannings et la communication avec les participants.
        </p>
        <p class="mb-0">
            🗓️ Utilisez les outils ci-dessous pour planifier, suivre et analyser vos événements.
        </p>
    </div>

    <div class="event-manager-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/weekEvents">🗓️ Calendrier hebdomadaire</a></li>
            <li class="nav-item"><a class="nav-link" href="/nextEvents">📅 Prochains événements</a></li>
            <li class="nav-item"><a class="nav-link" href="/events/guest">📩 Envoyer une invitation</a></li>
            <li class="nav-item"><a class="nav-link" href="/emails">📧 Get emails</a></li>
            <li class="nav-item"><a class="nav-link" href="/events/crossTab">🧮 Tableau croisé dynamique</a></li>
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Zarządzanie wydarzeniami</h5>
        <p>
            Ten obszar umożliwia zarządzanie wydarzeniami, harmonogramami i komunikacją z uczestnikami.
        </p>
        <p class="mb-0">
            🗓️ Skorzystaj z poniższych narzędzi, aby planować, monitorować i analizować swoje wydarzenia.
        </p>
    </div>

    <div class="event-manager-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            <li class="nav-item">
                <a class="nav-link" href="/weekEvents">🗓️ Tygodniowy kalendarz</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/nextEvents">📅 Nadchodzące wydarzenia</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/events/guest">📩 Wyślij zaproszenie</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/emails">📧 Pobierz e-maile</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/events/crossTab">🧮 Tabela przestawna</a>
            </li>

        </ul>
    </div>');
INSERT INTO "Languages" VALUES (83,'Redactor','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Editorial space</h5>
        <p>
            This area is dedicated to writing, managing and analyzing published content.
        </p>
        <p class="mb-0">
            ✍️ Use the tools below to create articles, manage media and track performance.
        </p>
    </div>

    <div class="redactor-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/articles">📰 Articles</a></li>
            <li class="nav-item"><a class="nav-link" href="/media/list">📂 Media</a></li>
            <li class="nav-item"><a class="nav-link" href="/topArticles">📈 Top 50</a></li>
            <li class="nav-item"><a class="nav-link" href="/articles/crossTab">🧮 Pivot table</a></li>
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Espace rédaction</h5>
        <p>
            Cette zone est dédiée à la rédaction, la gestion et l’analyse des contenus publiés.
        </p>
        <p class="mb-0">
            ✍️ Utilisez les outils ci-dessous pour créer des articles, gérer les médias et suivre les performances.
        </p>
    </div>

    <div class="redactor-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/articles">📰 Articles</a></li>
            <li class="nav-item"><a class="nav-link" href="/media/list">📂 Média</a></li>
            <li class="nav-item"><a class="nav-link" href="/topArticles">📈 Top 50</a></li>
            <li class="nav-item"><a class="nav-link" href="/articles/crossTab">🧮 Tableau croisé dynamique</a></li>
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Przestrzeń redakcyjna</h5>
        <p>
            Ten obszar jest przeznaczony do pisania, zarządzania i analizowania opublikowanych treści.
        </p>
        <p class="mb-0">
            ✍️ Skorzystaj z poniższych narzędzi, aby tworzyć artykuły, zarządzać mediami i śledzić wyniki.
        </p>
    </div>

    <div class="redactor-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item">
                <a class="nav-link" href="/articles">📰 Artykuły</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/media/list">📂 Media</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/topArticles">📈 Top 50</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/articles/crossTab">🧮 Tabela przestawna</a>
            </li>
        </ul>
    </div>');
INSERT INTO "Languages" VALUES (84,'PersonManager','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Member management</h5>
        <p>
            This area allows you to manage club members, groups and registrations.
        </p>
        <p class="mb-0">
            👥 Use the tools below to organize, import and manage member data.
        </p>
    </div>

    <div class="person-manager-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/persons">🎭 Members</a></li>
            <li class="nav-item"><a class="nav-link" href="/groups">👫 Groups</a></li>
            <li class="nav-item"><a class="nav-link" href="/registration">🎟️ Registrations</a></li>
            <li class="nav-item"><a class="nav-link" href="/import">📥 Import</a></li>
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Gestion des membres</h5>
        <p>
            Cette zone permet de gérer les membres du club, les groupes et les inscriptions.
        </p>
        <p class="mb-0">
            👥 Utilisez les outils ci-dessous pour organiser, importer et administrer les données des membres.
        </p>
    </div>

    <div class="person-manager-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/persons">🎭 Membres</a></li>
            <li class="nav-item"><a class="nav-link" href="/groups">👫 Groupes</a></li>
            <li class="nav-item"><a class="nav-link" href="/registration">🎟️ Inscriptions</a></li>
            <li class="nav-item"><a class="nav-link" href="/import">📥 Importer</a></li>
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Zarządzanie członkami</h5>
        <p>
            Ten obszar umożliwia zarządzanie członkami klubu, grupami i rejestracjami.
        </p>
        <p class="mb-0">
            👥 Skorzystaj z poniższych narzędzi, aby organizować, importować i zarządzać danymi członków.
        </p>
    </div>
    <div class="person-manager-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item">
                <a class="nav-link" href="/persons">🎭 Członkowie</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/groups">👫 Grupy</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/registration">🎟️ Rejestracje</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/import">📥 Import</a>
            </li>
        </ul>
    </div>');
INSERT INTO "Languages" VALUES (85,'VisitorInsights','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Visitor insights</h5>
        <p>
            This area allows you to monitor visitor activity, analyze traffic sources and trends.
        </p>
        <p class="mb-0">
            👀 Use the tools below to access logs, top pages and alerts requested by members.
        </p>
    </div>

    <div class="visitor-insights-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/referents">☁️ Referring sites</a></li>
            <li class="nav-item"><a class="nav-link" href="/topPages">📈 Top pages by period</a></li>
            <li class="nav-item"><a class="nav-link" href="/crossTab">🧮 Pivot table</a></li>
            <li class="nav-item"><a class="nav-link" href="/logs">📊 Visitors</a></li>
            <li class="nav-item"><a class="nav-link" href="/lastVisits">👁️ Last visits</a></li>
            <li class="nav-item"><a class="nav-link" href="/membersAlerts">📩 Member requested alerts</a></li>
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Observateurs</h5>
        <p>
            Cette zone permet de suivre l’activité des visiteurs, analyser les sources de trafic et les tendances.
        </p>
        <p class="mb-0">
            👀 Utilisez les outils ci-dessous pour accéder aux logs, aux pages les plus consultées et aux alertes demandées par les membres.
        </p>
    </div>

    <div class="visitor-insights-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/referents">☁️ Sites référents</a></li>
            <li class="nav-item"><a class="nav-link" href="/topPages">📈 Top pages</a></li>
            <li class="nav-item"><a class="nav-link" href="/crossTab">🧮 Tableau croisé dynamique</a></li>
            <li class="nav-item"><a class="nav-link" href="/logs">📊 Visiteurs</a></li>
            <li class="nav-item"><a class="nav-link" href="/lastVisits">👁️ Dernières visites</a></li>
            <li class="nav-item"><a class="nav-link" href="/membersAlerts">📩 Alertes demandées par les membres</a></li>
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Analiza odwiedzających</h5>
        <p>
            Ten obszar umożliwia monitorowanie aktywności odwiedzających, analizowanie źródeł ruchu i trendów.
        </p>
        <p class="mb-0">
            👀 Skorzystaj z poniższych narzędzi, aby uzyskać dostęp do logów, najpopularniejszych stron i alertów żądanych przez członków.
        </p>
    </div>
    <div class="visitor-insights-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item">
                <a class="nav-link" href="/referents">☁️ Strony odsyłające</a>
            </li>
            <li class="nav-item" title="Najpopularniejsze strony według okresu">
                <a class="nav-link" href="/topPages">📈 Popularne strony</a>
            </li>
            <li class="nav-item" title="Tabela przestawna">
                <a class="nav-link" href="/crossTab">🧮 Tabela przestawna</a>
            </li>
            <li class="nav-item" title="Odwiedzający">
                <a class="nav-link" href="/logs">📊 Odwiedzający</a>
            </li>
            <li class="nav-item" title="Ostatnie wizyty">
                <a class="nav-link" href="/lastVisits">👁️ Ostatnie wizyty</a>
            </li>
            <li class="nav-item" title="Alerty żądane przez członków">
                <a class="nav-link" href="/membersAlerts">📩 Alerty członków</a>
            </li>
        </ul>
    </div>');
INSERT INTO "Languages" VALUES (86,'Webmaster','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Webmaster Area</h5>
        <p>
            This area allows you to manage the website, databases, notifications, and maintenance.
        </p>
        <p class="mb-0">
            🛠️ Use the tools below to access databases, manage members, manage group registrations with permissions, handle notifications, configure the email server, and put the website into maintenance mode.
        </p>
    </div>

    <div class="webmaster-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/dbbrowser">🗂️ DB Browser</a></li>

            <li class="nav-item"><a class="nav-link" href="/groups">🧑‍🤝‍🧑 Groups</a></li>
            <li class="nav-item"><a class="nav-link" href="/registration">🎟️ Registrations</a></li>
            <li class="nav-item"><a class="nav-link" href="/notifications">🔔 Notifications</a></li>
            <li class="nav-item"><a class="nav-link" href="/sendEmails">📥 Emails</a></li>
            <li class="nav-item"><a class="nav-link" href="/maintenance">🚧 Maintenance</a></li>

            {if $isMyclubWebSite}
            <li class="nav-item"><a class="nav-link" href="/installations">🌐 Installations</a></li>
            {/if}
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Zone Webmaster</h5>
        <p>
            Cette zone permet de gérer le site, les bases de données, les notifications et la maintenance.
        </p>
        <p class="mb-0">
            🛠️ Utilisez les outils ci-dessous pour accéder aux bases de données, gérer les inscriptions, envoyer des emails et effectuer la maintenance.
        </p>
    </div>

    <div class="webmaster-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/dbbrowser">🗂️ DB Browser</a></li>
            <li class="nav-item"><a class="nav-link" href="/groups">🧑‍🤝‍🧑 Groupes</a></li> 
            <li class="nav-item"><a class="nav-link" href="/registration">🎟️ Inscriptions</a></li>
            <li class="nav-item"><a class="nav-link" href="/notifications">🔔 Notifications</a></li>
            <li class="nav-item"><a class="nav-link" href="/sendEmails">📥 Courriels</a></li>
            <li class="nav-item"><a class="nav-link" href="/maintenance">🚧 Maintenance</a></li>

            {if $isMyclubWebSite} 
            <li class="nav-item"><a class="nav-link" href="/installations">🌐 Installations</a></li>
            {/if} 
        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Obszar webmastera</h5>
        <p>
            Ten obszar umożliwia zarządzanie stroną internetową, bazami danych, powiadomieniami i konserwacją.
        </p>
        <p class="mb-0">
            🛠️ Skorzystaj z poniższych narzędzi, aby uzyskać dostęp do baz danych, zarządzać rejestracjami, wysyłać e-maile i przeprowadzać konserwację.
        </p>
    </div>
    <div class="webmaster-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/dbbrowser">🗂️ Przeglądarka bazy danych</a></li>
            <li class="nav-item"><a class="nav-link" href="/groups">🧑‍🤝‍🧑 Grupy</a></li>
            <li class="nav-item"><a class="nav-link" href="/registration">🎟️ Rejestracje</a></li>
            <li class="nav-item"><a class="nav-link" href="/notifications">🔔 Powiadomienia</a></li>
            <li class="nav-item"><a class="nav-link" href="/sendEmails">📥 E-maile</a></li>
            <li class="nav-item"><a class="nav-link" href="/maintenance">🚧 Konserwacja</a></li>
            {if $isMyclubWebSite}
            <li class="nav-item"><a class="nav-link" href="/installations">🌐 Instalacje</a></li>
            {/if}
        </ul>
    </div>');
INSERT INTO "Languages" VALUES (87,'article.error.not_found','Article {id} does not exist','L''article {id} n''existe pas','Artykuł {id} nie istnieje');
INSERT INTO "Languages" VALUES (88,'article.error.unknown_author','Unknown author for article {id}','Auteur inconnu pour l''article {id}','Nieznany autor artykułu {id}');
INSERT INTO "Languages" VALUES (89,'article.error.login_required','You must be logged in to view this article','Il faut être connecté pour pouvoir consulter cet article','Musisz być zalogowany, aby wyświetlić ten artykuł');
INSERT INTO "Languages" VALUES (90,'article.error.update_failed','An error occurred while updating the article','Une erreur est survenue lors de la mise à jour de l''article','Wystąpił błąd podczas aktualizacji artykułu');
INSERT INTO "Languages" VALUES (91,'article.error.title_content_required','Title and content are required','Le titre et le contenu sont obligatoires','Tytuł i treść są wymagane');
INSERT INTO "Languages" VALUES (92,'article.success.updated','Article successfully updated','L''article a été mis à jour avec succès','Artykuł został pomyślnie zaktualizowany');
INSERT INTO "Languages" VALUES (93,'article.success.email_sent','Email sent to subscribers','Un courriel a été envoyé aux abonnés','E-mail wysłany do subskrybentów');
INSERT INTO "Languages" VALUES (94,'article.email.new_title','A new article is available on {root}','Un nouvel article est disponible sur le site {root}','Nowy artykuł jest dostępny na {root}');
INSERT INTO "Languages" VALUES (95,'article.email.body_intro','According to your preferences, this message informs you about a new article','Conformément à vos souhaits, ce message vous signale la présence d''un nouvel article','Zgodnie z Twoimi preferencjami, ta wiadomość informuje Cię o nowym artykule');
INSERT INTO "Languages" VALUES (96,'article.email.unsubscribe','To stop receiving these emails update your preferences','Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences','Aby przestać otrzymywać te e-maile, zaktualizuj swoje preferencje');
INSERT INTO "Languages" VALUES (97,'article.title.crosstab','Redactors vs audience','Rédacteurs vs audience','Redaktorzy vs odbiorcy');
INSERT INTO "Languages" VALUES (98,'article.label.created_by','Created by','Créé par','Utworzony przez');
INSERT INTO "Languages" VALUES (99,'article.label.title','Title','Titre','Tytuł');
INSERT INTO "Languages" VALUES (100,'article.label.last_update','Last update','Dernière modification','Ostatnia modyfikacja');
INSERT INTO "Languages" VALUES (101,'article.label.group','Group','Groupe','Grupa');
INSERT INTO "Languages" VALUES (102,'article.label.published','Published','Publié','Opublikowany');
INSERT INTO "Languages" VALUES (103,'article.label.pool','Survey','Sondage','Ankieta');
INSERT INTO "Languages" VALUES (104,'article.label.content','Content','Contenu','Treść');
INSERT INTO "Languages" VALUES (105,'article.error.email_failed','Failed to send email to subscribers','Échec de l''envoi du courriel aux abonnés','Nie można wysłać emaila do subskrybentów');
INSERT INTO "Languages" VALUES (106,'navbar.designer.event_types','Event types and their attributes','Les types d''événements et leurs attributs','Typy wydarzeń i ich atrybuty');
INSERT INTO "Languages" VALUES (107,'navbar.designer.needs','Needs associated with events','Les besoins associés aux événements','Wymagania związane z wydarzeniami');
INSERT INTO "Languages" VALUES (108,'navbar.designer.settings','Customization','Personnalisation','Personalizacja');
INSERT INTO "Languages" VALUES (109,'navbar.designer.designs','Designs','Les designs','Projekty');
INSERT INTO "Languages" VALUES (110,'navbar.designer.kanban','Kanban board','Kanban','Tablica Kanban');
INSERT INTO "Languages" VALUES (111,'navbar.designer.menu','Navigation menus','Les menus de navigation','Menu nawigacyjne');
INSERT INTO "Languages" VALUES (112,'navbar.designer.translator','Translations','Les traductions','Tłumaczenia');
INSERT INTO "Languages" VALUES (113,'navbar.admin.event_manager','Event manager','Gestionnaire d''événement','Koordynator wydarzeń');
INSERT INTO "Languages" VALUES (114,'navbar.admin.designer','Designer','Designer','Projektant');
INSERT INTO "Languages" VALUES (115,'navbar.admin.redactor','Redactor','Rédacteur','Redaktor');
INSERT INTO "Languages" VALUES (116,'navbar.admin.person_manager','Secretary','Gestionnaire des membres','Sekretarz');
INSERT INTO "Languages" VALUES (117,'navbar.admin.visitor_insights','Observer','Observateur','Obserwator');
INSERT INTO "Languages" VALUES (118,'navbar.admin.webmaster','Webmaster','Webmaster','Webmaster');
INSERT INTO "Languages" VALUES (119,'navbar.event_manager.week_events','Weekly calendar','Calendrier hebdomadaire','Kalendarz tygodniowy');
INSERT INTO "Languages" VALUES (120,'navbar.event_manager.next_events','Upcoming events','Les prochains événements','Nadchodzące wydarzenia');
INSERT INTO "Languages" VALUES (121,'navbar.event_manager.guest_invitation','Send an invitation','Envoyer une « invitation »','Wyślij zaproszenie');
INSERT INTO "Languages" VALUES (122,'navbar.event_manager.emails','Email extraction','Extraction des emails','Eksport adresów e-mail');
INSERT INTO "Languages" VALUES (123,'navbar.event_manager.crosstab','Cross-tabulation table','Tableau croisé dynamique','Tabela przestawna');
INSERT INTO "Languages" VALUES (124,'navbar.person_manager.persons','Members','Membres','Członkowie');
INSERT INTO "Languages" VALUES (125,'navbar.person_manager.groups','Groups','Groupes','Grupy');
INSERT INTO "Languages" VALUES (126,'navbar.person_manager.registration','Registrations','Inscriptions','Rejestracje');
INSERT INTO "Languages" VALUES (127,'navbar.person_manager.import','Import','Importer','Import');
INSERT INTO "Languages" VALUES (128,'navbar.redactor.articles','Articles','Articles','Artykuły');
INSERT INTO "Languages" VALUES (129,'navbar.redactor.media','Media','Médias','Media');
INSERT INTO "Languages" VALUES (130,'navbar.redactor.top_articles','Top 50 articles','Top 50 articles','Top 50 artykułów');
INSERT INTO "Languages" VALUES (131,'navbar.redactor.crosstab','Cross-tabulation table','Tableau croisé dynamique','Tabela przestawna');
INSERT INTO "Languages" VALUES (132,'navbar.visitor_insights.referents','Referring sites','Sites référents','Strony odsyłające');
INSERT INTO "Languages" VALUES (133,'navbar.visitor_insights.top_pages','Top pages by period','Top pages par période','Najpopularniejsze strony w okresie');
INSERT INTO "Languages" VALUES (134,'navbar.visitor_insights.crosstab','Cross-tabulation table','Tableau croisé dynamique','Tabela przestawna');
INSERT INTO "Languages" VALUES (135,'navbar.visitor_insights.logs','Visit details','Détails des visites','Szczegóły wizyt');
INSERT INTO "Languages" VALUES (136,'navbar.visitor_insights.visitors','Visitors','Visiteurs','Odwiedzający');
INSERT INTO "Languages" VALUES (137,'navbar.visitor_insights.analytics','Visit summary','Synthèse des visites','Podsumowanie wizyt');
INSERT INTO "Languages" VALUES (138,'navbar.visitor_insights.last_visits','Latest visits','Dernières visites','Ostatnie wizyty');
INSERT INTO "Languages" VALUES (139,'navbar.visitor_insights.members_alerts','Alerts requested by members','Alertes demandées par les membres','Alerty żądane przez członków');
INSERT INTO "Languages" VALUES (140,'navbar.webmaster.dbbrowser','Database browser','Navigateur de base de données','Przeglądarka bazy danych');
INSERT INTO "Languages" VALUES (141,'navbar.webmaster.groups','Groups','Groupes','Grupy');
INSERT INTO "Languages" VALUES (142,'navbar.webmaster.registration','Registrations','Inscriptions','Rejestracje');
INSERT INTO "Languages" VALUES (143,'navbar.webmaster.notifications','Notifications','Notifications','Powiadomienia');
INSERT INTO "Languages" VALUES (144,'navbar.webmaster.send_emails','Emails','Courriels','Wiadomości e-mail');
INSERT INTO "Languages" VALUES (145,'navbar.webmaster.maintenance','Maintenance','Maintenance','Konserwacja');
INSERT INTO "Languages" VALUES (146,'navbar.webmaster.installations','Installations','Installations','Instalacje');
INSERT INTO "Languages" VALUES (147,'emailCredentials.title','Account to use for sending emails','Compte à utiliser pour envoyer des courriels','Konto do użycia do wysyłania wiadomości e-mail');
INSERT INTO "Languages" VALUES (148,'emailCredentials.email','Email','Email','Email');
INSERT INTO "Languages" VALUES (149,'emailCredentials.password','Password','Mot de passe','Hasło');
INSERT INTO "Languages" VALUES (150,'emailCredentials.host','Host','Hôte','Host');
INSERT INTO "Languages" VALUES (151,'emailCredentials.invalid_email','Please enter a valid email','Veuillez entrer un email valide','Proszę wprowadzić prawidłowy email');
INSERT INTO "Languages" VALUES (152,'menu.add_item','Add item','Ajouter un élément','Dodaj element');
INSERT INTO "Languages" VALUES (153,'menu.edit_item','Edit item','Modifier un élément','Edytuj element');
INSERT INTO "Languages" VALUES (154,'menu.delete_confirm','Delete this item?','Supprimer cet élément ?','Usunąć ten element?');
INSERT INTO "Languages" VALUES (155,'menu.label_required','The label is required.','Le label est requis.','Etykieta jest wymagana.');
INSERT INTO "Languages" VALUES (156,'menu.url_required','The URL is required for a link.','L''URL est requise pour un lien.','Adres URL jest wymagany dla łącza.');
INSERT INTO "Languages" VALUES (157,'menu.save_failed','Save failed.','Échec de la sauvegarde.','Błąd zapisu.');
INSERT INTO "Languages" VALUES (158,'menu.save_error','Error during save:','Erreur lors de la sauvegarde :','Błąd podczas zapisywania:');
INSERT INTO "Languages" VALUES (159,'menu.delete_failed','Delete failed.','Échec de la suppression.','Błąd usuwania.');
INSERT INTO "Languages" VALUES (160,'menu.delete_error','Error during deletion.','Erreur lors de la suppression.','Błąd podczas usuwania.');
INSERT INTO "Languages" VALUES (161,'menu.load_error','Error loading:','Erreur lors du chargement :','Błąd ładowania:');
INSERT INTO "Languages" VALUES (162,'menu.error','Error:','Erreur :','Błąd:');
INSERT INTO "Languages" VALUES (163,'menu.positions_error','Error updating positions:','Erreur mise à jour positions :','Błąd aktualizacji pozycji:');
INSERT INTO "Languages" VALUES (164,'menu.positions_error_generic','Error updating positions.','Erreur mise à jour positions.','Błąd aktualizacji pozycji.');
INSERT INTO "Languages" VALUES (165,'menu.modal_title','Menu Item','Menu Item','Element menu');
INSERT INTO "Languages" VALUES (166,'menu.field_label','Label:','Label :','Etykieta:');
INSERT INTO "Languages" VALUES (167,'menu.field_url','Address:','Adresse :','Adres:');
INSERT INTO "Languages" VALUES (168,'menu.field_url_placeholder','/my/route','/ma/route','/moja/trasa');
INSERT INTO "Languages" VALUES (169,'menu.field_group','Group:','Groupe :','Grupa:');
INSERT INTO "Languages" VALUES (170,'menu.field_none','None','Aucun','Brak');
INSERT INTO "Languages" VALUES (171,'menu.field_visible_for','Visible for:','Visible pour :','Widoczny dla:');
INSERT INTO "Languages" VALUES (172,'menu.field_members','Members','Membres','Członkowie');
INSERT INTO "Languages" VALUES (173,'menu.field_contacts','Contacts','Contacts','Kontakty');
INSERT INTO "Languages" VALUES (174,'menu.field_anonymous','Anonymous','Anonymes','Anonimowi');
INSERT INTO "Languages" VALUES (175,'menu.field_type','Type:','Type :','Typ:');
INSERT INTO "Languages" VALUES (176,'menu.type_link','Link','Lien','Łącze');
INSERT INTO "Languages" VALUES (177,'menu.type_heading','Heading','Titre','Nagłówek');
INSERT INTO "Languages" VALUES (178,'menu.type_divider','Divider','Séparateur','Separator');
INSERT INTO "Languages" VALUES (179,'menu.type_submenu','Submenu','Sous-menu','Podmenu');
INSERT INTO "Languages" VALUES (180,'menu.field_icon','Icon','Icône','Ikona');
INSERT INTO "Languages" VALUES (181,'menu.field_icon_placeholder','bi-house','bi-house','bi-house');
INSERT INTO "Languages" VALUES (182,'menu.field_parent','Parent:','Parent :','Nadrzędny:');
INSERT INTO "Languages" VALUES (183,'menu.page_title','Menu Items','Menu Items','Elementy menu');
INSERT INTO "Languages" VALUES (184,'menu.tab_navbar','Navbar','Navbar','Navbar');
INSERT INTO "Languages" VALUES (185,'menu.tab_sidebar','Sidebar','Sidebar','Sidebar');
INSERT INTO "Languages" VALUES (186,'menu.col_name','Name','Nom','Nazwa');
INSERT INTO "Languages" VALUES (187,'menu.col_url','URL','URL','URL');
INSERT INTO "Languages" VALUES (188,'menu.col_group','Group','Groupe','Grupa');
INSERT INTO "Languages" VALUES (189,'menu.col_members','Members','Membres','Członkowie');
INSERT INTO "Languages" VALUES (190,'menu.col_contacts','Contacts','Contacts','Kontakty');
INSERT INTO "Languages" VALUES (191,'menu.col_anonymous','Anonymous','Anonymes','Anonimowi');
INSERT INTO "Languages" VALUES (192,'menu.col_actions','Actions','Actions','Akcje');
INSERT INTO "Languages" VALUES (193,'menu.col_type','Type','Type','Typ');
INSERT INTO "Languages" VALUES (194,'menu.col_icon','Icon','Icône','Ikona');
INSERT INTO "Languages" VALUES (195,'menu.col_label','Label','Label','Etykieta');
INSERT INTO "Languages" VALUES (196,'menu.col_parent','Parent','Parent','Nadrzędny');
INSERT INTO "Languages" VALUES (197,'emailCredentials.method','Sending method','Méthode d''envoi','Metoda wysyłki');
INSERT INTO "Languages" VALUES (198,'emailCredentials.method_mail','Native PHP mail()','PHP mail() natif','Natywny mail() PHP');
INSERT INTO "Languages" VALUES (199,'emailCredentials.method_smtp','SMTP (PHPMailer)','SMTP (PHPMailer)','SMTP (PHPMailer)');
INSERT INTO "Languages" VALUES (200,'emailCredentials.method_mailjet','Mailjet API','Mailjet API','Mailjet API');
INSERT INTO "Languages" VALUES (201,'emailCredentials.info_mail','Emails sent via mail() may end up in spam or be rejected by some domains (Gmail, Outlook…). Recommended for testing only.','Les courriels envoyés via mail() risquent d''arriver en spam ou d''être rejetés par certains domaines (Gmail, Outlook…). Recommandé uniquement pour les tests.','Wiadomości wysyłane przez mail() mogą trafiać do spamu lub być odrzucane przez niektóre domeny (Gmail, Outlook…). Zalecane wyłącznie do testów.');
INSERT INTO "Languages" VALUES (202,'emailCredentials.info_smtp','Sending limits depend on your SMTP provider (e.g. Gmail: 500/day, OVH: 200/hour). Please check your plan.','Les limites d''envoi dépendent de votre fournisseur SMTP (ex. Gmail : 500/jour, OVH : 200/heure). Vérifiez votre offre.','Limity wysyłki zależą od dostawcy SMTP (np. Gmail: 500/dzień, OVH: 200/godzinę). Sprawdź swój plan.');
INSERT INTO "Languages" VALUES (203,'emailCredentials.info_mailjet','Mailjet free plan: 200 emails/day and 6,000/month. A paid subscription is required beyond these limits.','Plan gratuit Mailjet : 200 e-mails/jour et 6 000/mois. Au-delà, un abonnement payant est nécessaire.','Darmowy plan Mailjet: 200 e-maili/dzień i 6 000/miesiąc. Powyżej tych limitów wymagana jest płatna subskrypcja.');
INSERT INTO "Languages" VALUES (204,'emailCredentials.port','SMTP port','Port SMTP','Port SMTP');
INSERT INTO "Languages" VALUES (205,'emailCredentials.encryption','Encryption','Chiffrement','Szyfrowanie');
INSERT INTO "Languages" VALUES (206,'emailCredentials.no_encryption','None','Aucun','Brak');
INSERT INTO "Languages" VALUES (207,'emailCredentials.mailjet_api_key','Mailjet API key','Clé API Mailjet','Klucz API Mailjet');
INSERT INTO "Languages" VALUES (208,'emailCredentials.mailjet_api_secret','Mailjet API secret','Secret API Mailjet','Sekret API Mailjet');
INSERT INTO "Languages" VALUES (209,'emailCredentials.mailjet_sender','Verified sender address','Adresse expéditeur vérifiée','Zweryfikowany adres nadawcy');
INSERT INTO "Languages" VALUES (210,'emailCredentials.daily_limit','Daily sending limit (0 = unlimited)','Limite d''envoi quotidienne (0 = illimitée)','Dzienny limit wysyłki (0 = bez limitu)');
INSERT INTO "Languages" VALUES (211,'emailCredentials.monthly_limit','Monthly sending limit (0 = unlimited)','Limite d''envoi mensuelle (0 = illimitée)','Miesięczny limit wysyłki (0 = bez limitu)');
INSERT INTO "Languages" VALUES (212,'emailCredentials.limits_hint','Leave blank or set 0 for no limit.','Laisser vide ou mettre 0 pour ne pas limiter.','Pozostaw puste lub wpisz 0, aby nie ograniczać.');
INSERT INTO "Languages" VALUES (213,'person.add.emailAlreadyExistsDetailed','<div class="alert alert-warning">
<h5>⚠️ Email already used</h5>
<p>
A person with this email address already exists in the system.
</p>
<p>
<strong>Existing account:</strong> {name}<br>
<strong>Status:</strong> {status}
</p>
<p class="mt-3">
Before creating a new person, please check:
</p>
<ul>
<li>the list of <strong>active accounts</strong></li>
<li>the list of <strong>disabled accounts</strong></li>
</ul>
<p class="mt-3">
If the account already exists but is disabled, you can simply
<strong>reactivate the existing account</strong> instead of creating a new one.
</p>
</div>','<div class="alert alert-warning">
<h5>⚠️ Adresse courriel déjà utilisée</h5>
<p>
Une personne avec cette adresse courriel existe déjà dans le système.
</p>
<p>
<strong>Compte existant :</strong> {name}<br>
<strong>Statut :</strong> {status}
</p>
<p class="mt-3">
Avant de créer une nouvelle fiche, veuillez vérifier :
</p>
<ul>
<li>la liste des <strong>comptes actifs</strong></li>
<li>la liste des <strong>comptes désactivés</strong></li>
</ul>
<p class="mt-3">
Si la personne existe déjà mais que son compte est désactivé,
vous pouvez simplement <strong>réactiver le compte existant</strong>
au lieu d''en créer un nouveau.
</p>
</div>','<div class="alert alert-warning">
<h5>⚠️ Adres email już użyty</h5>
<p>
Osoba z tym adresem email już istnieje w systemie.
</p>
<p>
<strong>Istniejące konto:</strong> {name}<br>
<strong>Status:</strong> {status}
</p>
<p class="mt-3">
Przed utworzeniem nowej osoby sprawdź:
</p>
<ul>
<li>listę <strong>aktywnych kont</strong></li>
<li>listę <strong>wyłączonych kont</strong></li>
</ul>
<p class="mt-3">
Jeśli konto istnieje, ale jest wyłączone, możesz je po prostu
<strong>ponownie aktywować</strong> zamiast tworzyć nowe.
</p>
</div>');
INSERT INTO "Languages" VALUES (214,'quick_actions','Quick actions','Vous voulez gagner du temps ? Accédez aux actions rapides.','Chcesz zaoszczędzić czas? Przejdź do szybkich działań.');
INSERT INTO "Languages" VALUES (215,'presentation.edit.title','Edit my presentation','Édition de ma présentation','Edytuj moją prezentację');
INSERT INTO "Languages" VALUES (216,'presentation.edit.inDirectory','I wish to appear in the directory','Je souhaite figurer dans le trombinoscope','Chcę figurować w katalogu');
INSERT INTO "Languages" VALUES (217,'presentation.edit.inDirectory.hint','Your presentation will be visible to other members','Votre présentation sera visible par les autres membres','Twoja prezentacja będzie widoczna dla innych członków');
INSERT INTO "Languages" VALUES (218,'presentation.edit.showPhone','Display my phone number in the directory','Afficher mon numéro de téléphone dans le trombinoscope','Wyświetl mój numer telefonu w katalogu');
INSERT INTO "Languages" VALUES (219,'presentation.edit.showEmail','Display my email address in the directory','Afficher mon adresse e-mail dans le trombinoscope','Wyświetl mój adres e-mail w katalogu');
INSERT INTO "Languages" VALUES (220,'presentation.edit.showLocation','Display my location on the public map','Afficher ma localisation sur la carte publique','Wyświetl moją lokalizację na publicznej mapie');
INSERT INTO "Languages" VALUES (221,'presentation.edit.publicLocation.label','Description of your public location','Description de votre localisation publique','Opis Twojej publicznej lokalizacji');
INSERT INTO "Languages" VALUES (222,'presentation.edit.publicLocation.placeholder','You can contact me at 06... if a swarm is near my neighborhood and accessible','Vous pouvez me contacter au 06... si un essaim est proche de mon quartier et accessible','Możesz skontaktować się ze mną pod nr 06... jeśli rój jest blisko mojej dzielnicy i dostępny');
INSERT INTO "Languages" VALUES (223,'presentation.edit.publicLocation.hint','This text will be displayed when clicking on your pin in the directory','Ce texte sera affiché lors du clic sur votre punaise dans le trombinoscope','Ten tekst będzie wyświetlany po kliknięciu pinezki w katalogu');
INSERT INTO "Languages" VALUES (224,'presentation.edit.content.label','My presentation','Ma présentation','Moja prezentacja');
INSERT INTO "Languages" VALUES (225,'presentation.edit.location.label','Place of residence (neighborhood)','Lieu d''habitation (quartier)','Miejsce zamieszkania (dzielnica)');
INSERT INTO "Languages" VALUES (226,'presentation.edit.location.hint','Click on the map to indicate your neighborhood','Cliquez sur la carte pour indiquer votre quartier d''habitation','Kliknij na mapę, aby wskazać swoją dzielnicę');
INSERT INTO "Languages" VALUES (227,'presentation.edit.validation.noContent','Please write your presentation before appearing in the directory','Veuillez rédiger votre présentation avant de figurer dans le trombinoscope','Proszę napisać swoją prezentację przed pojawieniem się w katalogu');
INSERT INTO "Languages" VALUES (228,'directory.index.title','Trombinoscope','Trombinoscope','Trombinoskop');
INSERT INTO "Languages" VALUES (229,'directory.index.subtitle','Browse members who have chosen to share their profile','Découvrez les membres qui ont choisi de partager leur présentation','Przeglądaj członków, którzy zdecydowali się udostępnić swój profil');
INSERT INTO "Languages" VALUES (230,'directory.index.locate_public','Locate public members','Localiser les membres publics','Znajdź publicznych członków');
INSERT INTO "Languages" VALUES (231,'directory.index.locate_members','Locate members','Localiser les membres','Znajdź członków');
INSERT INTO "Languages" VALUES (232,'directory.index.edit_presentation','Edit my presentation','Modifier ma présentation','Edytuj moją prezentację');
INSERT INTO "Languages" VALUES (233,'directory.index.create_presentation','Create my presentation','Créer ma présentation','Utwórz moją prezentację');
INSERT INTO "Languages" VALUES (234,'directory.index.filter_by_group','Filter by group','Filtrer par groupe','Filtruj według grupy');
INSERT INTO "Languages" VALUES (235,'directory.index.all','All','Tous','Wszyscy');
INSERT INTO "Languages" VALUES (236,'directory.index.view_profile','View profile','Voir le profil','Zobacz profil');
INSERT INTO "Languages" VALUES (237,'directory.index.no_members','No member has yet created a presentation in the directory.','Aucun membre n''a encore créé de présentation dans le trombinoscope.','Żaden członek nie utworzył jeszcze prezentacji w katalogu.');
INSERT INTO "Languages" VALUES (238,'directory.index.no_members_group','No member of this group has yet created a presentation in the directory.','Aucun membre de ce groupe n''a encore créé de présentation dans le trombinoscope.','Żaden członek tej grupy nie utworzył jeszcze prezentacji w katalogu.');
INSERT INTO "Languages" VALUES (239,'article.show.title','Articles','Articles','Artykuły');
INSERT INTO "Languages" VALUES (240,'article.show.notify_subscribers','Notify subscribers','Prévenir les abonnés','Powiadom subskrybentów');
INSERT INTO "Languages" VALUES (241,'article.show.manage_gallery','Manage gallery','Gérer la galerie','Zarządzaj galerią');
INSERT INTO "Languages" VALUES (242,'article.show.edit_survey','Edit survey','Modifier le sondage','Edytuj ankietę');
INSERT INTO "Languages" VALUES (243,'article.show.add_survey','Add a survey','Ajouter un sondage','Dodaj ankietę');
INSERT INTO "Languages" VALUES (244,'article.show.edit_order','Edit group order','Modifier la commande groupée','Edytuj zamówienie grupowe');
INSERT INTO "Languages" VALUES (245,'article.show.add_order','Add a group order','Ajouter une commande groupée','Dodaj zamówienie grupowe');
INSERT INTO "Languages" VALUES (246,'article.show.only_creator_can_edit','Only the article creator can edit it','Seul le créateur de l''article peut le modifier','Tylko twórca artykułu może go edytować');
INSERT INTO "Languages" VALUES (247,'article.show.view_survey_results','View survey results','Voir résultats sondage','Zobacz wyniki ankiety');
INSERT INTO "Languages" VALUES (248,'article.show.reply_survey','Reply to survey','Répondre au sondage','Odpowiedz na ankietę');
INSERT INTO "Languages" VALUES (249,'article.show.view_order_results','View order results','Voir résultats commande','Zobacz wyniki zamówienia');
INSERT INTO "Languages" VALUES (250,'article.show.reply_order','Reply to group order','Répondre pour la commande groupée','Odpowiedz na zamówienie grupowe');
INSERT INTO "Languages" VALUES (251,'article.show.created_by','Created by','Créé par','Utworzony przez');
INSERT INTO "Languages" VALUES (252,'article.show.on_date','on','le','dnia');
INSERT INTO "Languages" VALUES (253,'article.show.modified_on','modified on','modifié le','zmodyfikowano dnia');
INSERT INTO "Languages" VALUES (254,'article.show.published','Published','Publié','Opublikowany');
INSERT INTO "Languages" VALUES (255,'article.show.not_published','Not published','Non publié','Nieopublikowany');
INSERT INTO "Languages" VALUES (256,'article.show.group_label','Group:','Groupe:','Grupa:');
INSERT INTO "Languages" VALUES (257,'article.show.gallery','Gallery','Galerie','Galeria');
INSERT INTO "Languages" VALUES (258,'article.show.previous','Previous','Précédent','Poprzedni');
INSERT INTO "Languages" VALUES (259,'article.show.next','Next','Suivant','Następny');
INSERT INTO "Languages" VALUES (260,'article.show.modal_survey_title','Reply to survey','Répondre au sondage','Odpowiedz na ankietę');
INSERT INTO "Languages" VALUES (261,'article.show.modal_survey_loading','Loading survey...','Chargement du sondage...','Ładowanie ankiety...');
INSERT INTO "Languages" VALUES (262,'article.show.modal_order_title','Reply to order','Répondre à la commande','Odpowiedz na zamówienie');
INSERT INTO "Languages" VALUES (263,'article.show.modal_order_loading','Loading order...','Chargement de la commande...','Ładowanie zamówienia...');
INSERT INTO "Languages" VALUES (264,'communication.index.title','Communication Manager','Gestionnaire de communication','Menedżer komunikacji');
INSERT INTO "Languages" VALUES (265,'communication.index.today','Today','Aujourd''hui','Dzisiaj');
INSERT INTO "Languages" VALUES (266,'communication.index.this_month','This month','Ce mois','Ten miesiąc');
INSERT INTO "Languages" VALUES (267,'communication.index.dest','dest.','dest.','odb.');
INSERT INTO "Languages" VALUES (268,'communication.index.send','Send','Envoyer','Wyślij');
INSERT INTO "Languages" VALUES (269,'communication.index.cancel','Cancel','Annuler','Anuluj');
INSERT INTO "Languages" VALUES (270,'communication.index.filter_members','Filter members','Filtrer les membres','Filtruj członków');
INSERT INTO "Languages" VALUES (271,'communication.index.group','Group','Groupe','Grupa');
INSERT INTO "Languages" VALUES (272,'communication.index.all_members','— All members —','— Tous les membres —','— Wszyscy członkowie —');
INSERT INTO "Languages" VALUES (273,'communication.index.password','Password','Mot de passe','Hasło');
INSERT INTO "Languages" VALUES (274,'communication.index.filter_all','All','Tous','Wszyscy');
INSERT INTO "Languages" VALUES (275,'communication.index.filter_created','Created','Créé','Utworzone');
INSERT INTO "Languages" VALUES (276,'communication.index.filter_not_created','Not created','Non créé','Nie utworzone');
INSERT INTO "Languages" VALUES (277,'communication.index.presentation','Presentation','Présentation','Prezentacja');
INSERT INTO "Languages" VALUES (278,'communication.index.filter_filled','Filled','Renseignée','Uzupełniona');
INSERT INTO "Languages" VALUES (279,'communication.index.filter_not_filled','Not filled','Non renseignée','Nieuzupełniona');
INSERT INTO "Languages" VALUES (280,'communication.index.in_public_map','In the public map','Dans la carte publique','Na publicznej mapie');
INSERT INTO "Languages" VALUES (281,'communication.index.filter_yes','Yes','Oui','Tak');
INSERT INTO "Languages" VALUES (282,'communication.index.filter_no','No','Non','Nie');
INSERT INTO "Languages" VALUES (283,'communication.index.refresh','Refresh','Actualiser','Odśwież');
INSERT INTO "Languages" VALUES (284,'communication.index.select_all','Select all','Tout sél.','Zaznacz wszystko');
INSERT INTO "Languages" VALUES (285,'communication.index.subject','Subject','Objet','Temat');
INSERT INTO "Languages" VALUES (286,'communication.index.subject_placeholder','Message subject…','Objet du message…','Temat wiadomości…');
INSERT INTO "Languages" VALUES (287,'communication.index.content','Content','Contenu','Treść');
INSERT INTO "Languages" VALUES (288,'communication.index.modal_confirm_title','Confirm sending','Confirmer l''envoi','Potwierdź wysyłkę');
INSERT INTO "Languages" VALUES (289,'communication.index.modal_cancel','Cancel','Annuler','Anuluj');
INSERT INTO "Languages" VALUES (290,'communication.index.modal_confirm','Confirm','Confirmer','Potwierdź');
INSERT INTO "Languages" VALUES (291,'communication.index.sending','Sending…','Envoi en cours…','Wysyłanie…');
INSERT INTO "Languages" VALUES (292,'navbar.admin.communication_manager','Communication Manager','Gestionnaire de communication','Menedżer komunikacji');
INSERT INTO "Languages" VALUES (293,'communication.filters.desactivated_accounts','Deactivated accounts','Comptes désactivés','Konta dezaktywowane');
INSERT INTO "Languages" VALUES (294,'designer.home_settings.title','Home page customization','Personnalisation de la page d''accueil','Personalizacja strony głównej');
INSERT INTO "Languages" VALUES (295,'designer.home_settings.force_language','Force this language','Forcer cette langue','Wymuś ten język');
INSERT INTO "Languages" VALUES (296,'designer.home_settings.preview_hint','Preview — click to edit','Aperçu — cliquez pour éditer','Podgląd — kliknij, aby edytować');
INSERT INTO "Languages" VALUES (297,'designer.home_settings.section_header','Header','En-tête','Nagłówek');
INSERT INTO "Languages" VALUES (298,'designer.home_settings.section_article','Main article','Article principal','Główny artykuł');
INSERT INTO "Languages" VALUES (299,'designer.home_settings.section_latest','Latest articles','Derniers articles','Ostatnie artykuły');
INSERT INTO "Languages" VALUES (300,'designer.home_settings.section_footer','Footer','Pied de page','Stopka');
INSERT INTO "Languages" VALUES (301,'designer.home_settings.preview_empty','Empty','Vide','Pusty');
INSERT INTO "Languages" VALUES (302,'designer.home_settings.preview_article_auto','1st paragraph of the latest article / featured article','1er paragraphe du dernier article / article mis en avant','1. akapit ostatniego artykułu / wyróżnionego artykułu');
INSERT INTO "Languages" VALUES (303,'designer.home_settings.preview_hidden','Section hidden','Section masquée','Sekcja ukryta');
INSERT INTO "Languages" VALUES (304,'designer.home_settings.preview_latest_more','more…','autres…','więcej…');
INSERT INTO "Languages" VALUES (305,'designer.home_settings.editor_placeholder_title','Click on a section','Cliquez sur une section','Kliknij sekcję');
INSERT INTO "Languages" VALUES (306,'designer.home_settings.editor_placeholder_subtitle','to display its editor','pour afficher son éditeur','aby wyświetlić jej edytor');
INSERT INTO "Languages" VALUES (307,'designer.home_settings.editor_select_hint','Select a section in the preview','Sélectionnez une section dans l''aperçu','Wybierz sekcję w podglądzie');
INSERT INTO "Languages" VALUES (308,'designer.home_settings.header_description','HTML content displayed at the top of the home page. Active language:','Contenu HTML affiché en haut de la page d''accueil. Langue active :','Zawartość HTML wyświetlana na górze strony głównej. Aktywny język:');
INSERT INTO "Languages" VALUES (309,'designer.home_settings.header_table_hint','(Languages table)','(table Languages)','(tabela Languages)');
INSERT INTO "Languages" VALUES (310,'designer.home_settings.footer_description','HTML content displayed at the bottom of the home page. Active language:','Contenu HTML affiché en bas de la page d''accueil. Langue active :','Zawartość HTML wyświetlana na dole strony głównej. Aktywny język:');
INSERT INTO "Languages" VALUES (311,'designer.home_settings.article_label','ID of the article to feature','ID de l''article à mettre en avant','ID artykułu do wyróżnienia');
INSERT INTO "Languages" VALUES (312,'designer.home_settings.article_description','Enter the identifier of a specific article. Enter 0 to automatically display the first paragraph of the latest published article or the currently featured article.','Entrez l''identifiant d''un article spécifique. Saisissez 0 pour afficher automatiquement le premier paragraphe du dernier article publié ou de l''article actuellement mis en avant.','Wprowadź identyfikator konkretnego artykułu. Wpisz 0, aby automatycznie wyświetlić pierwszy akapit ostatniego opublikowanego artykułu lub aktualnie wyróżnionego artykułu.');
INSERT INTO "Languages" VALUES (313,'designer.home_settings.article_zero_hint','0 = latest article or featured article','0 = dernier article ou article mis en avant','0 = ostatni artykuł lub wyróżniony artykuł');
INSERT INTO "Languages" VALUES (314,'designer.home_settings.latest_label','Number of latest articles to display','Nombre de derniers articles à afficher','Liczba ostatnich artykułów do wyświetlenia');
INSERT INTO "Languages" VALUES (315,'designer.home_settings.latest_description','Indicate how many recent articles to list. Enter 0 to completely hide this section. Value between 0 and 50.','Indiquez combien d''articles récents lister. Saisissez 0 pour masquer complètement cette section. Valeur entre 0 et 50.','Podaj, ile ostatnich artykułów wyświetlić. Wpisz 0, aby całkowicie ukryć tę sekcję. Wartość od 0 do 50.');
INSERT INTO "Languages" VALUES (316,'designer.home_settings.title_edit_header','Edit the header','Éditer l''en-tête','Edytuj nagłówek');
INSERT INTO "Languages" VALUES (317,'designer.home_settings.title_edit_article','Configure the main article','Configurer l''article principal','Skonfiguruj główny artykuł');
INSERT INTO "Languages" VALUES (318,'designer.home_settings.title_edit_latest','Configure the latest articles list','Configurer la liste des derniers articles','Skonfiguruj listę ostatnich artykułów');
INSERT INTO "Languages" VALUES (319,'designer.home_settings.title_edit_footer','Edit the footer','Éditer le pied de page','Edytuj stopkę');
INSERT INTO "Languages" VALUES (320,'home.index.title','What do you want to do?','Que souhaitez-vous faire ?','Co chcesz zrobić?');
INSERT INTO "Languages" VALUES (321,'home.index.subtitle','Select an action below to get started.','Sélectionnez une action ci-dessous pour commencer.','Wybierz działanie poniżej, aby rozpocząć.');
INSERT INTO "Languages" VALUES (322,'home.index.public_space','🌎 Public space','🌎 L''espace public','🌎 Przestrzeń publiczna');
INSERT INTO "Languages" VALUES (323,'home.index.tab_member','👤 My member space','👤 Mon espace membre','👤 Moja przestrzeń członka');
INSERT INTO "Languages" VALUES (324,'home.index.tab_roles','🔑 My permissions','🔑 Mes autorisations','🔑 Moje uprawnienia');
INSERT INTO "Languages" VALUES (325,'home.index.member_actions','👥 Actions available to all members','👥 Actions disponibles pour tous les membres','👥 Działania dostępne dla wszystkich członków');
INSERT INTO "Languages" VALUES (326,'home.action.contact.title','Contact the Club','Contacter le Club','Skontaktuj się z Klubem');
INSERT INTO "Languages" VALUES (327,'home.action.contact.desc','Send a message to the administrators','Envoyer un message aux responsables','Wyślij wiadomość do administratorów');
INSERT INTO "Languages" VALUES (328,'home.action.signin.title','Sign in','Me connecter','Zaloguj się');
INSERT INTO "Languages" VALUES (329,'home.action.signin.desc','Access my member space','Accéder à mon espace membre','Uzyskaj dostęp do mojej przestrzeni członka');
INSERT INTO "Languages" VALUES (330,'home.action.search_article.title','Search for an article','Rechercher un article','Wyszukaj artykuł');
INSERT INTO "Languages" VALUES (331,'home.action.search_article.desc','Find club articles','Trouver des articles du club','Znajdź artykuły klubu');
INSERT INTO "Languages" VALUES (332,'home.action.calendar.title','View calendar','Voir le calendrier','Zobacz kalendarz');
INSERT INTO "Languages" VALUES (333,'home.action.calendar.desc_public','View upcoming events','Visualiser les événements à venir','Wyświetl nadchodzące wydarzenia');
INSERT INTO "Languages" VALUES (334,'home.action.calendar.desc_member','Browse events','Consulter les événements','Przeglądaj wydarzenia');
INSERT INTO "Languages" VALUES (335,'home.action.profile.title','Profile','Profil','Profil');
INSERT INTO "Languages" VALUES (336,'home.action.profile.desc','Update my information','Mettre à jour mes informations','Zaktualizuj moje informacje');
INSERT INTO "Languages" VALUES (337,'home.action.statistics.title','Statistics','Statistiques','Statystyki');
INSERT INTO "Languages" VALUES (338,'home.action.statistics.desc','History and activity','Historique et activité','Historia i aktywność');
INSERT INTO "Languages" VALUES (339,'home.action.connections.title','Connections','Connexions','Połączenia');
INSERT INTO "Languages" VALUES (340,'home.action.connections.desc','My events in common with other members','Mes événements en commun avec les autres membres','Moje wspólne wydarzenia z innymi członkami');
INSERT INTO "Languages" VALUES (341,'home.action.next_events.title','Upcoming events','Evénements à venir','Nadchodzące wydarzenia');
INSERT INTO "Languages" VALUES (342,'home.action.next_events.desc','Browse and register for upcoming sessions','Consulter et m''inscrire aux prochaines séances','Przeglądaj i zapisuj się na nadchodzące sesje');
INSERT INTO "Languages" VALUES (343,'home.action.directory.title','Member directory','Trombinoscope','Katalog członków');
INSERT INTO "Languages" VALUES (344,'home.action.directory.desc','View club members and create/edit my presentation','Voir les membres du club et créer/modifier ma présentation','Przeglądaj członków klubu i utwórz/edytuj swoją prezentację');
INSERT INTO "Languages" VALUES (345,'home.action.news.title','News','News','Aktualności');
INSERT INTO "Languages" VALUES (346,'home.action.news.desc','View club news (articles, events, surveys, presentations...) from the last 7 days','Voir les nouvelles du club (articles, événements, sondages, présentation ...) des 7 derniers jours','Zobacz nowości klubu (artykuły, wydarzenia, ankiety, prezentacje...) z ostatnich 7 dni');
INSERT INTO "Languages" VALUES (347,'home.action.messages.title','Messages','Messages','Wiadomości');
INSERT INTO "Languages" VALUES (348,'home.action.messages.desc','View messages from the last 7 days','Voir les messages des 7 derniers jours','Zobacz wiadomości z ostatnich 7 dni');
INSERT INTO "Languages" VALUES (349,'home.role.event_manager','🗓️ Event manager','🗓️ Animateur','🗓️ Animator');
INSERT INTO "Languages" VALUES (350,'home.action.manage_sessions.title','Manage sessions','Gérer les séances','Zarządzaj sesjami');
INSERT INTO "Languages" VALUES (351,'home.action.manage_sessions.desc','Create, edit, cancel a session and track registrations','Créer, modifier, annuler une séance et suivre les inscriptions','Twórz, edytuj, anuluj sesję i śledź rejestracje');
INSERT INTO "Languages" VALUES (352,'home.action.send_invitation.title','Send an invitation','Envoyer une invitation','Wyślij zaproszenie');
INSERT INTO "Languages" VALUES (353,'home.action.send_invitation.desc','Send an invitation to a non-member for a session','Envoyer une invitation à une personne non membre du club pour une séance','Wyślij zaproszenie do osoby niebędącej członkiem klubu na sesję');
INSERT INTO "Languages" VALUES (354,'home.action.stats_animators.title','Animator statistics','Statistiques animateurs','Statystyki animatorów');
INSERT INTO "Languages" VALUES (355,'home.action.stats_animators.desc','View animator statistics (number of sessions led by type and participants)','Voir les statistiques des animateurs du club (nombre de séances animées par types et participants)','Zobacz statystyki animatorów klubu (liczba sesji prowadzonych według typów i uczestników)');
INSERT INTO "Languages" VALUES (356,'home.role.designer','🎨 Designer','🎨 Designer','🎨 Projektant');
INSERT INTO "Languages" VALUES (357,'home.action.event_types.title','Event types and their attributes','Types d''événements et leurs attributs','Typy wydarzeń i ich atrybuty');
INSERT INTO "Languages" VALUES (358,'home.action.event_types.desc','Define club event types and associated attributes','Définir les types d''événements du club et les attributs associés','Zdefiniuj typy wydarzeń klubu i powiązane atrybuty');
INSERT INTO "Languages" VALUES (359,'home.action.session_needs.title','Session needs','Les besoins d''une séance','Potrzeby sesji');
INSERT INTO "Languages" VALUES (360,'home.action.session_needs.desc','Define the needs associated with each club event type','Définir les besoins associés à chaque type d''événement du club','Zdefiniuj potrzeby powiązane z każdym typem wydarzenia klubu');
INSERT INTO "Languages" VALUES (361,'home.action.site_settings.title','Site settings','Paramètres du site','Ustawienia witryny');
INSERT INTO "Languages" VALUES (362,'home.action.site_settings.desc','Define general website settings and available language(s)','Définir les paramètres généraux du site web du club et la(les) langues disponibles','Zdefiniuj ogólne ustawienia witryny klubu i dostępne języki');
INSERT INTO "Languages" VALUES (363,'home.action.kanban.title','Kanban','Kanban','Kanban');
INSERT INTO "Languages" VALUES (364,'home.action.kanban.desc','Manage projects via Kanban boards','Gérer des projets via des tableaux Kanban','Zarządzaj projektami za pomocą tablic Kanban');
INSERT INTO "Languages" VALUES (365,'home.action.navigation.title','Navigation','Navigation','Nawigacja');
INSERT INTO "Languages" VALUES (366,'home.action.navigation.desc','Manage the club website navigation bars','Gérer les barres de navigation du site web du club','Zarządzaj paskami nawigacyjnymi witryny klubu');
INSERT INTO "Languages" VALUES (367,'home.role.redactor','✍️ Redactor','✍️ Rédacteur','✍️ Redaktor');
INSERT INTO "Languages" VALUES (368,'home.action.article.title','Article','Article','Artykuł');
INSERT INTO "Languages" VALUES (369,'home.action.article.desc','Write and publish a new article','Rédiger et publier un nouvel article','Napisz i opublikuj nowy artykuł');
INSERT INTO "Languages" VALUES (370,'home.action.medias.title','Media','Medias','Media');
INSERT INTO "Languages" VALUES (371,'home.action.medias.desc','Manage club media (photos, documents...)','Gérer les médias du club (photos, documents ...)','Zarządzaj mediami klubu (zdjęcia, dokumenty...)');
INSERT INTO "Languages" VALUES (372,'home.action.top_articles.title','Popular articles','Articles populaires','Popularne artykuły');
INSERT INTO "Languages" VALUES (373,'home.action.top_articles.desc','View the most popular club articles by period','Voir les articles les plus populaires du club par période','Zobacz najpopularniejsze artykuły klubu według okresu');
INSERT INTO "Languages" VALUES (374,'home.action.stats_redactors.title','Redactor statistics','Statistiques rédacteurs','Statystyki redaktorów');
INSERT INTO "Languages" VALUES (375,'home.action.stats_redactors.desc','View redactor statistics (number of articles published per redactor and period)','Voir les statistiques des rédacteurs du club (nombre d''articles publiés par rédacteur et par période)','Zobacz statystyki redaktorów klubu (liczba artykułów opublikowanych na redaktora i okres)');
INSERT INTO "Languages" VALUES (376,'home.role.secretary','📇 Secretary','📇 Secrétaire','📇 Sekretarz');
INSERT INTO "Languages" VALUES (377,'home.action.manage_members.title','Manage members','Gérer les membres','Zarządzaj członkami');
INSERT INTO "Languages" VALUES (378,'home.action.manage_members.desc','View and administer club members','Consulter et administrer les adhérents','Przeglądaj i administruj członkami klubu');
INSERT INTO "Languages" VALUES (379,'home.action.manage_groups.title','Manage groups','Gérer les groupes','Zarządzaj grupami');
INSERT INTO "Languages" VALUES (380,'home.action.manage_groups.desc','View and administer member groups (without permissions)','Consulter et administrer les groupes (sans autorisation) de membres','Przeglądaj i administruj grupami członków (bez uprawnień)');
INSERT INTO "Languages" VALUES (381,'home.action.manage_registrations.title','Manage registrations','Gérer les inscriptions','Zarządzaj rejestracjami');
INSERT INTO "Languages" VALUES (382,'home.action.manage_registrations.desc','View and administer member registrations to groups without permissions','Consulter et administrer les inscriptions des adhérents aux groupes sans autorisation','Przeglądaj i administruj rejestracjami członków do grup bez uprawnień');
INSERT INTO "Languages" VALUES (383,'home.action.import_members.title','Import members','Importer des membres','Importuj członków');
INSERT INTO "Languages" VALUES (384,'home.action.import_members.desc','Import members from a CSV file','Importer des adhérents à partir d''un fichier CSV','Importuj członków z pliku CSV');
INSERT INTO "Languages" VALUES (385,'home.role.observer','🔍 Observer','🔍 Observateur','🔍 Obserwator');
INSERT INTO "Languages" VALUES (386,'home.action.referrers.title','Referrer sites','Site référents','Witryny odsyłające');
INSERT INTO "Languages" VALUES (387,'home.action.referrers.desc','See where club website visitors come from','Voir d''où viennent les visiteurs du site web du club','Zobacz skąd przychodzą odwiedzający witrynę klubu');
INSERT INTO "Languages" VALUES (388,'home.action.top_pages.title','Popular pages','Pages populaires','Popularne strony');
INSERT INTO "Languages" VALUES (389,'home.action.top_pages.desc','View the most popular club pages by period','Voir les pages les plus populaires du club par période','Zobacz najpopularniejsze strony klubu według okresu');
INSERT INTO "Languages" VALUES (390,'home.action.stats_visitors.title','Visitor statistics (members)','Statistiques visiteurs (membres)','Statystyki odwiedzających (członkowie)');
INSERT INTO "Languages" VALUES (391,'home.action.stats_visitors.desc','View visitor statistics (number of pages viewed per visitor and period)','Voir les statistiques des visiteurs du site web du club (nombre de pages vues par visiteur et par période)','Zobacz statystyki odwiedzających (liczba wyświetlonych stron na odwiedzającego i okres)');
INSERT INTO "Languages" VALUES (392,'home.action.visitor_logs.title','Visitor logs','Logs visiteurs','Logi odwiedzających');
INSERT INTO "Languages" VALUES (393,'home.action.visitor_logs.desc','View club website visitor logs','Voir les logs des visiteurs du site web du club','Zobacz logi odwiedzających witrynę klubu');
INSERT INTO "Languages" VALUES (394,'home.action.visitor_charts.title','Visitor charts','Graphiques visiteurs','Wykresy odwiedzających');
INSERT INTO "Languages" VALUES (395,'home.action.visitor_charts.desc','View club website visitor charts','Voir les graphiques des visiteurs du site web du club','Zobacz wykresy odwiedzających witrynę klubu');
INSERT INTO "Languages" VALUES (396,'home.action.visitor_analytics.title','Visitor analytics','Analyses des visiteurs','Analizy odwiedzających');
INSERT INTO "Languages" VALUES (397,'home.action.visitor_analytics.desc','View visitor charts (OS, browsers, hardware, screen resolution)','Voir les graphiques des visiteurs du site web du club (OS, navigateurs, matériel, résolution d''écran)','Zobacz wykresy odwiedzających (OS, przeglądarki, sprzęt, rozdzielczość ekranu)');
INSERT INTO "Languages" VALUES (398,'home.action.last_visits.title','Last visits','Dernières visites','Ostatnie wizyty');
INSERT INTO "Languages" VALUES (399,'home.action.last_visits.desc','View the last visits of club members','Voir la dernière visites des membres du club','Zobacz ostatnie wizyty członków klubu');
INSERT INTO "Languages" VALUES (400,'home.role.webmaster','🛠️ Webmaster','🛠️ Webmaster','🛠️ Webmaster');
INSERT INTO "Languages" VALUES (401,'home.action.site_config.title','Site configuration','Configuration site','Konfiguracja witryny');
INSERT INTO "Languages" VALUES (402,'home.action.site_config.desc','Technical settings','Paramètres techniques','Ustawienia techniczne');
INSERT INTO "Languages" VALUES (403,'LegalNotices','Your legal notices here','Vos mentions légales ici','Twoje informacje prawne tutaj');
INSERT INTO "Languages" VALUES (404,'communication.index.deactivated_accounts','Deactivated accounts','Comptes désactivés','Dezaktywowane konta');
INSERT INTO "Languages" VALUES (405,'designer.home_settings.image_banner_desc','Image for the banner','Image pour la bannière','Obraz dla bannera');
INSERT INTO "Languages" VALUES (406,'designer.home_settings.image_home_desc','Image for the Home button in the navigation bar','Image pour le bouton Home de la barre de navigation','Obraz przycisku Home na pasku nawigacji');
INSERT INTO "Languages" VALUES (407,'designer.home_settings.image_logo_desc','Watermark image','Image en filigrane','Obraz znaku wodnego');
INSERT INTO "Languages" VALUES (408,'designer.home_settings.title_edit_images','Image editing','Édition des images','Edycja obrazów');
INSERT INTO "Languages" VALUES (409,'navbar.redactor.crossTab','Editors cross-tab','Tableau croisé des rédacteurs','Tabela krzyżowa redaktorów');
INSERT INTO "Languages" VALUES (410,'Help_KanbanDesigner','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Kanban Board</h1>
    <p class="lead">Manage your projects visually by moving cards between 4 columns.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">The 4 columns</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">💡</span>
              <div><strong>Ideas / Backlog</strong>
              <p class="text-muted small">All ideas to explore. No commitment to implement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">✅</span>
              <div><strong>To do</strong>
              <p class="text-muted small">Validated tasks ready to be handled.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🔧</span>
              <div><strong>In progress</strong>
              <p class="text-muted small">Work currently in development or processing.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🏁</span>
              <div><strong>Done</strong>
              <p class="text-muted small">Completed tasks, but also <strong>rejected ideas</strong> archived here for reference.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Cards</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-tags-fill"></i></div>
              <div><strong>Customisable types</strong>
              <p class="text-muted small">Each project defines its own card types (e.g. 🚀 Feature, 🐛 Bug…). Unlimited types.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-palette-fill"></i></div>
              <div><strong>Bootstrap colours</strong>
              <p class="text-muted small">All standard Bootstrap colours are available. 👉 <em>Colour meaning is specific to each project.</em></p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hand-index-thumb-fill"></i></div>
              <div><strong>Moving a card</strong>
              <p class="text-muted small">Drag &amp; drop a card to the destination column. It moves there immediately.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-history"></i></div>
              <div><strong>Movement history</strong>
              <p class="text-muted small">Every move is recorded with date and time (e.g. 💡 → ✅). You can add a <strong>note</strong> to each step. 👉 <em>Available via the 👁️ View button.</em></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <p class="text-muted">The three action buttons on each card:</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3"><i class="bi bi-eye-fill"></i></span>
        <span><strong>View:</strong> Shows the full card details and its movement <strong>history</strong> with notes.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-warning text-dark fs-6 me-3"><i class="bi bi-pencil-fill"></i></span>
        <span><strong>Edit:</strong> Edit the title, description and type of the card.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3"><i class="bi bi-trash-fill"></i></span>
        <span><strong>Delete:</strong> Permanently deletes the card after confirmation.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Only members with the <strong>KanbanDesigner</strong> authorisation can access the Kanban Board. Only the <strong>project creator</strong> can modify or configure it.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Kanban Board</h1>
    <p class="lead">Gérez vos projets visuellement en déplaçant des cartes entre 4 colonnes.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les 4 colonnes</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">💡</span>
              <div><strong>Idées / Backlog</strong>
              <p class="text-muted small">Toutes les idées à explorer. Aucun engagement de réalisation.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">✅</span>
              <div><strong>À faire</strong>
              <p class="text-muted small">Tâches validées et prêtes à être prises en charge.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🔧</span>
              <div><strong>En cours</strong>
              <p class="text-muted small">Travaux actuellement en développement ou en traitement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🏁</span>
              <div><strong>Terminé</strong>
              <p class="text-muted small">Tâches finalisées, mais aussi les <strong>idées non retenues</strong> que l''on archive ici pour mémoire.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les cartes</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-tags-fill"></i></div>
              <div><strong>Types personnalisables</strong>
              <p class="text-muted small">Chaque projet définit ses propres types de carte (ex. 🚀 Fonctionnalité, 🐛 Bug…). Le nombre de types est illimité.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-palette-fill"></i></div>
              <div><strong>Couleurs Bootstrap</strong>
              <p class="text-muted small">Toutes les couleurs standard Bootstrap sont disponibles. 👉 <em>La signification d''une couleur est propre à chaque projet.</em></p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hand-index-thumb-fill"></i></div>
              <div><strong>Déplacer une carte</strong>
              <p class="text-muted small">Faites glisser une carte par <strong>drag &amp; drop</strong> vers la colonne de destination. Elle s''y positionne immédiatement.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-history"></i></div>
              <div><strong>Historique des déplacements</strong>
              <p class="text-muted small">Chaque déplacement est enregistré automatiquement avec sa date et heure (ex. 💡 → ✅). Vous pouvez ajouter une <strong>remarque</strong> à chaque étape. 👉 <em>Accessible via le bouton 👁️ Visualiser de la carte.</em></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <p class="text-muted">Les trois boutons d''action présents sur chaque carte :</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3"><i class="bi bi-eye-fill"></i></span>
        <span><strong>Visualiser :</strong> Affiche le détail complet de la carte et son <strong>historique</strong> de déplacements avec les remarques.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-warning text-dark fs-6 me-3"><i class="bi bi-pencil-fill"></i></span>
        <span><strong>Modifier :</strong> Édite le titre, le détail et le type de la carte.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3"><i class="bi bi-trash-fill"></i></span>
        <span><strong>Supprimer :</strong> Supprime définitivement la carte après confirmation.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> Seuls les membres avec l''autorisation <strong>KanbanDesigner</strong> accèdent au Kanban Board. Seul le <strong>créateur d''un projet</strong> peut le modifier ou le configurer.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Kanban Board</h1>
    <p class="lead">Zarządzaj projektami wizualnie, przesuwając karty między 4 kolumnami.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">4 kolumny</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">💡</span>
              <div><strong>Pomysły / Backlog</strong>
              <p class="text-muted small">Wszystkie pomysły do zbadania. Brak zobowiązania do realizacji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">✅</span>
              <div><strong>Do zrobienia</strong>
              <p class="text-muted small">Zatwierdzone zadania gotowe do realizacji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🔧</span>
              <div><strong>W toku</strong>
              <p class="text-muted small">Prace aktualnie w trakcie realizacji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <span class="fs-4 me-3">🏁</span>
              <div><strong>Ukończone</strong>
              <p class="text-muted small">Ukończone zadania, ale też <strong>odrzucone pomysły</strong> zarchiwizowane tutaj dla pamięci.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Karty</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-tags-fill"></i></div>
              <div><strong>Konfigurowalne typy</strong>
              <p class="text-muted small">Każdy projekt definiuje własne typy kart (np. 🚀 Funkcja, 🐛 Błąd…). Liczba typów jest nieograniczona.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-palette-fill"></i></div>
              <div><strong>Kolory Bootstrap</strong>
              <p class="text-muted small">Dostępne są wszystkie standardowe kolory Bootstrap. 👉 <em>Znaczenie koloru jest specyficzne dla każdego projektu.</em></p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hand-index-thumb-fill"></i></div>
              <div><strong>Przenoszenie karty</strong>
              <p class="text-muted small">Przeciągnij kartę metodą <strong>drag &amp; drop</strong> do docelowej kolumny. Karta zostaje tam natychmiast.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-history"></i></div>
              <div><strong>Historia przesunięć</strong>
              <p class="text-muted small">Każde przesunięcie jest automatycznie rejestrowane z datą i godziną (np. 💡 → ✅). Do każdego kroku można dodać <strong>uwagę</strong>. 👉 <em>Dostępne przez przycisk 👁️ Podgląd karty.</em></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <p class="text-muted">Trzy przyciski akcji na każdej karcie:</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-success fs-6 me-3"><i class="bi bi-eye-fill"></i></span>
        <span><strong>Podgląd:</strong> Wyświetla pełne szczegóły karty i jej <strong>historię</strong> przesunięć z uwagami.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-warning text-dark fs-6 me-3"><i class="bi bi-pencil-fill"></i></span>
        <span><strong>Edytuj:</strong> Edytuje tytuł, opis i typ karty.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <span class="badge bg-danger fs-6 me-3"><i class="bi bi-trash-fill"></i></span>
        <span><strong>Usuń:</strong> Trwale usuwa kartę po potwierdzeniu.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Tylko członkowie z uprawnieniem <strong>KanbanDesigner</strong> mają dostęp do Kanban Board. Tylko <strong>twórca projektu</strong> może go modyfikować lub konfigurować.</span>
      </div>
    </div>
  </section>
</div>');
INSERT INTO "Languages" VALUES (411,'Help_Observers','<div class="container my-5">

  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Observers</h1>
    <p class="lead">Track visitor activity, analyse traffic sources and trends.</p>
  </header>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Available tools</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bar-chart-line-fill"></i></div>
              <div><strong>Visitor statistics</strong>
              <p class="text-muted small">Combined chart of <em>Unique visitors</em> and <em>Page views</em> for the selected period (day / week / month / year), with a detailed table below.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-pie-chart-fill"></i></div>
              <div><strong>Visitor summary</strong>
              <p class="text-muted small">Pie charts showing distribution by <em>Operating system</em>, <em>Browser</em>, <em>Screen resolution</em> and <em>Hardware</em>.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-cloud-arrow-up-fill"></i></div>
              <div><strong>Referrer sites</strong>
              <p class="text-muted small">Visit origins: <em>direct</em>, <em>internal</em>, <em>external</em>, then detail of source URLs. Navigate by Day / Week / Month / Year.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-trophy-fill"></i></div>
              <div><strong>Top visited pages</strong>
              <p class="text-muted small">Ranking of the most visited URIs with visit count and percentage. Period selectable via drop-down list.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Dynamic cross-table</strong>
              <p class="text-muted small">Crosses <em>URIs</em> visited by <em>User</em>. Filterable by Period, URI, Email and Group. Shows total per row. 👉 <em>Use the <strong>Hide</strong> button to collapse the table.</em></p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Last visits</strong>
              <p class="text-muted small">Lists recently connected members: last page visited, timestamp, OS and browser. Also shows the number of active visitors in real time.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-text-fill"></i></div>
              <div><strong>Logs</strong>
              <p class="text-muted small">Raw log of all requests: Date, Type, Browser, OS, Page visited, Visitor, HTTP code, Message. Combinable multi-filters, paginated results.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-fill"></i></div>
              <div><strong>Member alerts</strong>
              <p class="text-muted small">Summary of each member''s notification preferences: alert on new <em>Event</em> and/or new <em>Article</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <hr class="my-5">

  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <p class="text-muted">Features common to most pages:</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Time navigation:</strong> The <kbd>Day</kbd> <kbd>Week</kbd> <kbd>Month</kbd> <kbd>Year</kbd> buttons and the <kbd>&lt;</kbd> <kbd>&gt;</kbd> arrows let you navigate through time on all statistics pages.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-funnel-fill fs-3 text-primary me-3"></i>
        <span><strong>Filters:</strong> The <em>Cross-table</em> and <em>Logs</em> pages offer combinable filters. Click <strong>Filter</strong> to apply and <strong>Reset</strong> to clear.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access to the <em>Observers</em> area is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>

</div>','<div class="container my-5">

  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Observateurs</h1>
    <p class="lead">Suivez l''activité des visiteurs, analysez les sources de trafic et les tendances.</p>
  </header>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les outils disponibles</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bar-chart-line-fill"></i></div>
              <div><strong>Statistiques des visiteurs</strong>
              <p class="text-muted small">Graphique combiné <em>Visiteurs uniques</em> et <em>Pages vues</em> sur la période choisie (jour / semaine / mois / année). Tableau détaillé en dessous du graphique.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-pie-chart-fill"></i></div>
              <div><strong>Synthèse des visiteurs</strong>
              <p class="text-muted small">Camemberts de répartition par <em>Système d''exploitation</em>, <em>Navigateur</em>, <em>Résolution d''écran</em> et <em>Matériel</em>.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-cloud-arrow-up-fill"></i></div>
              <div><strong>Sites référents</strong>
              <p class="text-muted small">Origine des visites : <em>direct</em>, <em>interne</em>, <em>externe</em>, puis détail des URLs sources. Navigation par Jour / Semaine / Mois / Année.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-trophy-fill"></i></div>
              <div><strong>Top des pages visitées</strong>
              <p class="text-muted small">Classement des URI les plus consultées avec le nombre de visites et le pourcentage. Période sélectionnable via la liste déroulante.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Tableau croisé dynamique</strong>
              <p class="text-muted small">Croise les <em>URI</em> visitées par <em>Utilisateur</em>. Filtrable par Période, URI, Email et Groupe. Affiche le total par ligne. 👉 <em>Bouton <strong>Masquer</strong> pour replier le tableau.</em></p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Dernières visites</strong>
              <p class="text-muted small">Liste les membres connectés récemment : dernière page consultée, horodatage, OS et navigateur. Affiche aussi le nombre de visiteurs actifs en temps réel.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-text-fill"></i></div>
              <div><strong>Logs</strong>
              <p class="text-muted small">Journal brut de toutes les requêtes : Date, Type, Navigateur, OS, Page visitée, Visiteur, Code HTTP, Message. Multi-filtres combinables, résultats paginés.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-fill"></i></div>
              <div><strong>Alertes demandées par les membres</strong>
              <p class="text-muted small">Récapitulatif des préférences de notification de chaque membre : alerte sur nouvel <em>Événement</em> et/ou nouvel <em>Article</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <hr class="my-5">

  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <p class="text-muted">Fonctionnalités communes à la plupart des pages :</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Navigation temporelle :</strong> Les boutons <kbd>Jour</kbd> <kbd>Semaine</kbd> <kbd>Mois</kbd> <kbd>Année</kbd> et les flèches <kbd>&lt;</kbd> <kbd>&gt;</kbd> permettent de naviguer dans le temps sur toutes les pages de statistiques.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-funnel-fill fs-3 text-primary me-3"></i>
        <span><strong>Filtres :</strong> Les pages <em>Tableau croisé</em> et <em>Logs</em> proposent des filtres combinables. Cliquez sur <strong>Filtrer</strong> pour appliquer et <strong>Réinitialiser</strong> pour effacer.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès à la zone <em>Observateurs</em> est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>

</div>','<div class="container my-5">

  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Obserwatorzy</h1>
    <p class="lead">Śledź aktywność odwiedzających, analizuj źródła ruchu i trendy.</p>
  </header>

  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Dostępne narzędzia</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bar-chart-line-fill"></i></div>
              <div><strong>Statystyki odwiedzających</strong>
              <p class="text-muted small">Łączny wykres <em>Unikalnych odwiedzających</em> i <em>Wyświetleń stron</em> za wybrany okres (dzień / tydzień / miesiąc / rok). Szczegółowa tabela pod wykresem.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-pie-chart-fill"></i></div>
              <div><strong>Podsumowanie odwiedzających</strong>
              <p class="text-muted small">Wykresy kołowe rozkładu według <em>Systemu operacyjnego</em>, <em>Przeglądarki</em>, <em>Rozdzielczości ekranu</em> i <em>Sprzętu</em>.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-cloud-arrow-up-fill"></i></div>
              <div><strong>Witryny odsyłające</strong>
              <p class="text-muted small">Źródła wizyt: <em>bezpośrednie</em>, <em>wewnętrzne</em>, <em>zewnętrzne</em>, następnie szczegóły źródłowych URL. Nawigacja Dzień / Tydzień / Miesiąc / Rok.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-trophy-fill"></i></div>
              <div><strong>Najpopularniejsze strony</strong>
              <p class="text-muted small">Ranking najczęściej odwiedzanych URI z liczbą wizyt i procentem. Okres wybierany z listy rozwijanej.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Dynamiczna tabela krzyżowa</strong>
              <p class="text-muted small">Krzyżuje <em>URI</em> odwiedzone przez <em>Użytkownika</em>. Filtrowanie według Okresu, URI, Email i Grupy. Wyświetla sumę w wierszu. 👉 <em>Przycisk <strong>Ukryj</strong> zwija tabelę.</em></p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Ostatnie wizyty</strong>
              <p class="text-muted small">Lista ostatnio zalogowanych członków: ostatnia odwiedzona strona, znacznik czasu, OS i przeglądarka. Pokazuje też liczbę aktywnych odwiedzających w czasie rzeczywistym.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-text-fill"></i></div>
              <div><strong>Logi</strong>
              <p class="text-muted small">Surowy dziennik wszystkich żądań: Data, Typ, Przeglądarka, OS, Odwiedzona strona, Odwiedzający, Kod HTTP, Wiadomość. Łączne filtry, wyniki paginowane.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-fill"></i></div>
              <div><strong>Alerty zamówione przez członków</strong>
              <p class="text-muted small">Podsumowanie preferencji powiadomień każdego członka: alert o nowym <em>Wydarzeniu</em> i/lub nowym <em>Artykule</em>.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <hr class="my-5">

  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <p class="text-muted">Funkcje wspólne dla większości stron:</p>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Nawigacja czasowa:</strong> Przyciski <kbd>Dzień</kbd> <kbd>Tydzień</kbd> <kbd>Miesiąc</kbd> <kbd>Rok</kbd> oraz strzałki <kbd>&lt;</kbd> <kbd>&gt;</kbd> umożliwiają nawigację w czasie na wszystkich stronach statystyk.</span>
      </div>
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-funnel-fill fs-3 text-primary me-3"></i>
        <span><strong>Filtry:</strong> Strony <em>Tabela krzyżowa</em> i <em>Logi</em> oferują łączne filtry. Kliknij <strong>Filtruj</strong> aby zastosować i <strong>Resetuj</strong> aby wyczyścić.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp do strefy <em>Obserwatorzy</em> jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>

</div>');
INSERT INTO "Languages" VALUES (412,'Help_Referents','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Referrer Sites</h1>
    <p class="lead">Find out where your visitors come from before arriving on the site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>direct</strong>
              <p class="text-muted small">The visitor typed the URL directly or used a bookmark. No referring site.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-house-fill"></i></div>
              <div><strong>internal</strong>
              <p class="text-muted small">Navigation between pages within the site itself.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-arrow-in-right"></i></div>
              <div><strong>external</strong>
              <p class="text-muted small">The visitor arrived via a link on another website (social network, search engine, partner site…).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Source URLs</strong>
              <p class="text-muted small">Below the three summary lines, the exact URLs of external referrers are listed with their visit count.
              <span class="d-block mt-1 text-dark">👉 <em>A high count on a specific URL indicates an active inbound link worth monitoring.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Time navigation:</strong> Switch between <kbd>Day</kbd> <kbd>Week</kbd> <kbd>Month</kbd> <kbd>Year</kbd> and use the <kbd>&lt;</kbd> <kbd>&gt;</kbd> arrows to move through time.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Sites référents</h1>
    <p class="lead">Identifiez d''où viennent vos visiteurs avant d''arriver sur le site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>direct</strong>
              <p class="text-muted small">Le visiteur a tapé l''URL directement ou utilisé un favori. Aucun site référent.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-house-fill"></i></div>
              <div><strong>interne</strong>
              <p class="text-muted small">Navigation entre les pages du site lui-même.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-arrow-in-right"></i></div>
              <div><strong>externe</strong>
              <p class="text-muted small">Le visiteur est arrivé via un lien sur un autre site (réseau social, moteur de recherche, site partenaire…).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>URLs sources</strong>
              <p class="text-muted small">Sous les trois lignes de synthèse, les URLs exactes des référents externes sont listées avec leur nombre de visites.
              <span class="d-block mt-1 text-dark">👉 <em>Un compteur élevé sur une URL précise signale un lien entrant actif à surveiller.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Navigation temporelle :</strong> Basculez entre <kbd>Jour</kbd> <kbd>Semaine</kbd> <kbd>Mois</kbd> <kbd>Année</kbd> et utilisez les flèches <kbd>&lt;</kbd> <kbd>&gt;</kbd> pour naviguer dans le temps.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Witryny odsyłające</h1>
    <p class="lead">Dowiedz się, skąd przychodzą Twoi odwiedzający przed dotarciem na stronę.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-arrow-right-circle-fill"></i></div>
              <div><strong>bezpośrednie</strong>
              <p class="text-muted small">Odwiedzający wpisał URL bezpośrednio lub użył zakładki. Brak witryny odsyłającej.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-house-fill"></i></div>
              <div><strong>wewnętrzne</strong>
              <p class="text-muted small">Nawigacja między stronami w obrębie samej witryny.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-box-arrow-in-right"></i></div>
              <div><strong>zewnętrzne</strong>
              <p class="text-muted small">Odwiedzający przyszedł przez link na innej stronie (sieć społecznościowa, wyszukiwarka, strona partnerska…).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Źródłowe URL</strong>
              <p class="text-muted small">Pod trzema wierszami podsumowania wyświetlane są dokładne URL zewnętrznych odsyłaczy wraz z liczbą wizyt.
              <span class="d-block mt-1 text-dark">👉 <em>Wysoki licznik przy konkretnym URL wskazuje aktywny link przychodzący warty monitorowania.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Nawigacja czasowa:</strong> Przełączaj między <kbd>Dzień</kbd> <kbd>Tydzień</kbd> <kbd>Miesiąc</kbd> <kbd>Rok</kbd> i używaj strzałek <kbd>&lt;</kbd> <kbd>&gt;</kbd> do poruszania się w czasie.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>');
INSERT INTO "Languages" VALUES (413,'Help_TopPages','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Top Visited Pages</h1>
    <p class="lead">Identify which pages attract the most traffic on your site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hash"></i></div>
              <div><strong>Rank</strong>
              <p class="text-muted small">Pages are sorted from most to least visited for the selected period.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>URI</strong>
              <p class="text-muted small">The path of the visited page (e.g. <code>/nextEvents</code>, <code>/user/sign_in</code>).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Visits &amp; Percentage</strong>
              <p class="text-muted small">Number of hits for the period and its share of total traffic, illustrated by a progress bar.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
              <div><strong>Period</strong>
              <p class="text-muted small">Select the analysis window via the drop-down list (e.g. last 7 days, last 30 days…).</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-bar-chart-fill fs-3 text-primary me-3"></i>
        <span><strong>Usage tip:</strong> A URI with a very high percentage indicates a strategic entry point — check that its content is up to date.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Top des pages visitées</h1>
    <p class="lead">Identifiez les pages qui attirent le plus de trafic sur votre site.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hash"></i></div>
              <div><strong>Rang</strong>
              <p class="text-muted small">Les pages sont triées de la plus visitée à la moins visitée pour la période sélectionnée.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>URI</strong>
              <p class="text-muted small">Le chemin de la page visitée (ex. <code>/nextEvents</code>, <code>/user/sign_in</code>).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Visites &amp; Pourcentage</strong>
              <p class="text-muted small">Nombre de passages sur la période et sa part du trafic total, illustrée par une barre de progression.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
              <div><strong>Période</strong>
              <p class="text-muted small">Sélectionnez la fenêtre d''analyse via la liste déroulante (ex. 7 derniers jours, 30 derniers jours…).</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-bar-chart-fill fs-3 text-primary me-3"></i>
        <span><strong>Conseil :</strong> Une URI avec un très fort pourcentage représente un point d''entrée stratégique — vérifiez que son contenu est à jour.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Najpopularniejsze strony</h1>
    <p class="lead">Zidentyfikuj strony przyciągające największy ruch.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-hash"></i></div>
              <div><strong>Ranga</strong>
              <p class="text-muted small">Strony posortowane od najczęściej do najrzadziej odwiedzanych w wybranym okresie.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>URI</strong>
              <p class="text-muted small">Ścieżka odwiedzonej strony (np. <code>/nextEvents</code>, <code>/user/sign_in</code>).</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-fill"></i></div>
              <div><strong>Wizyty &amp; Procent</strong>
              <p class="text-muted small">Liczba wejść w danym okresie i jej udział w całkowitym ruchu, zilustrowany paskiem postępu.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar3"></i></div>
              <div><strong>Okres</strong>
              <p class="text-muted small">Wybierz okno analizy z listy rozwijanej (np. ostatnie 7 dni, ostatnie 30 dni…).</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-bar-chart-fill fs-3 text-primary me-3"></i>
        <span><strong>Wskazówka:</strong> URI z bardzo wysokim procentem to strategiczny punkt wejścia — sprawdź, czy jej treść jest aktualna.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>');
INSERT INTO "Languages" VALUES (414,'Help_Crosstab','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Dynamic Cross-Table</h1>
    <p class="lead">Cross-reference which pages each user has visited over a given period.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">How it works</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>URI × User matrix</strong>
              <p class="text-muted small">Rows = visited URIs. Columns = users (email). Each cell shows the number of visits by that user on that page. The <strong>Total</strong> column sums all users per URI.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-slash-fill"></i></div>
              <div><strong>Hide / Show</strong>
              <p class="text-muted small">The <strong>Hide</strong> button in the table header collapses the matrix to save screen space. Click again to expand.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Filters</strong>
              <p class="text-muted small">Combine four criteria to narrow results:
                <span class="d-block mt-1">📅 <strong>Period</strong> — analysis window</span>
                <span class="d-block">🔗 <strong>URI</strong> — filter on a specific page</span>
                <span class="d-block">📧 <strong>Email</strong> — filter on a specific user</span>
                <span class="d-block">👥 <strong>Group</strong> — restrict to a member group</span>
                <span class="d-block mt-1 text-dark">👉 <em>Click <strong>Filter</strong> to apply, <strong>Reset</strong> to clear.</em></span>
              </p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-person-lines-fill fs-3 text-primary me-3"></i>
        <span><strong>Usage tip:</strong> Filter by Email to see the complete browsing path of a specific member, or by Group to compare the habits of a team.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Tableau croisé dynamique</h1>
    <p class="lead">Croisez les pages visitées avec les utilisateurs sur une période donnée.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Fonctionnement</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Matrice URI × Utilisateur</strong>
              <p class="text-muted small">Lignes = URI visitées. Colonnes = utilisateurs (email). Chaque cellule indique le nombre de passages de cet utilisateur sur cette page. La colonne <strong>Total</strong> agrège tous les utilisateurs par URI.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-slash-fill"></i></div>
              <div><strong>Masquer / Afficher</strong>
              <p class="text-muted small">Le bouton <strong>Masquer</strong> en en-tête du tableau replie la matrice pour gagner de l''espace. Cliquez à nouveau pour la déplier.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Filtres</strong>
              <p class="text-muted small">Combinez quatre critères pour affiner les résultats :
                <span class="d-block mt-1">📅 <strong>Période</strong> — fenêtre d''analyse</span>
                <span class="d-block">🔗 <strong>URI</strong> — filtrer sur une page précise</span>
                <span class="d-block">📧 <strong>Email</strong> — filtrer sur un utilisateur précis</span>
                <span class="d-block">👥 <strong>Groupe</strong> — restreindre à un groupe de membres</span>
                <span class="d-block mt-1 text-dark">👉 <em>Cliquez sur <strong>Filtrer</strong> pour appliquer, <strong>Réinitialiser</strong> pour effacer.</em></span>
              </p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-person-lines-fill fs-3 text-primary me-3"></i>
        <span><strong>Conseil :</strong> Filtrez par Email pour voir le parcours complet d''un membre, ou par Groupe pour comparer les habitudes d''une équipe.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Dynamiczna tabela krzyżowa</h1>
    <p class="lead">Skrzyżuj odwiedzone strony z użytkownikami w danym okresie.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Jak to działa</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Macierz URI × Użytkownik</strong>
              <p class="text-muted small">Wiersze = odwiedzone URI. Kolumny = użytkownicy (email). Każda komórka pokazuje liczbę wejść danego użytkownika na daną stronę. Kolumna <strong>Łącznie</strong> sumuje wszystkich użytkowników dla URI.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-eye-slash-fill"></i></div>
              <div><strong>Ukryj / Pokaż</strong>
              <p class="text-muted small">Przycisk <strong>Ukryj</strong> w nagłówku tabeli zwija macierz, aby zaoszczędzić miejsce. Kliknij ponownie, aby rozwinąć.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-funnel-fill"></i></div>
              <div><strong>Filtry</strong>
              <p class="text-muted small">Łącz cztery kryteria, aby zawęzić wyniki:
                <span class="d-block mt-1">📅 <strong>Okres</strong> — okno analizy</span>
                <span class="d-block">🔗 <strong>URI</strong> — filtruj na konkretnej stronie</span>
                <span class="d-block">📧 <strong>Email</strong> — filtruj na konkretnym użytkowniku</span>
                <span class="d-block">👥 <strong>Grupa</strong> — ogranicz do grupy członków</span>
                <span class="d-block mt-1 text-dark">👉 <em>Kliknij <strong>Filtruj</strong> aby zastosować, <strong>Resetuj</strong> aby wyczyścić.</em></span>
              </p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-person-lines-fill fs-3 text-primary me-3"></i>
        <span><strong>Wskazówka:</strong> Filtruj po Email, aby zobaczyć pełną ścieżkę przeglądania członka, lub po Grupie, aby porównać nawyki zespołu.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>');
INSERT INTO "Languages" VALUES (415,'Help_VisitorGraf','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Visitor Statistics</h1>
    <p class="lead">Visualise visitor trends and page views over time.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the chart</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
              <div><strong>Unique visitors</strong>
              <p class="text-muted small">Line chart (left axis) — number of distinct visitors per period unit. A visitor counted once per session regardless of pages viewed.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
              <div><strong>Page views</strong>
              <p class="text-muted small">Bar chart (right axis) — total number of pages loaded. A high Pages/Visitor ratio indicates strong engagement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-range"></i></div>
              <div><strong>Time navigation</strong>
              <p class="text-muted small">Switch between <kbd>By day</kbd> <kbd>By week</kbd> <kbd>By month</kbd> <kbd>By year</kbd>. Use <kbd>&lt;&lt;</kbd> <kbd>&lt;</kbd> <kbd>Today</kbd> <kbd>&gt;</kbd> <kbd>&gt;&gt;</kbd> to move through time.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Detail table</strong>
              <p class="text-muted small">Below the chart, a table lists each period unit with Unique visitors, Page views and the Pages/Visitor ratio.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-graph-up-arrow fs-3 text-primary me-3"></i>
        <span><strong>Usage tip:</strong> A spike in page views without a corresponding rise in unique visitors suggests an existing member is browsing deeply — check the cross-table for details.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Statistiques des visiteurs</h1>
    <p class="lead">Visualisez les tendances de fréquentation et les pages vues dans le temps.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le graphique</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
              <div><strong>Visiteurs uniques</strong>
              <p class="text-muted small">Courbe (axe gauche) — nombre de visiteurs distincts par unité de période. Un visiteur est compté une seule fois par session quelle que soit la navigation.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
              <div><strong>Pages vues</strong>
              <p class="text-muted small">Barres (axe droit) — nombre total de pages chargées. Un ratio Pages/Visiteur élevé indique un fort engagement.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-range"></i></div>
              <div><strong>Navigation temporelle</strong>
              <p class="text-muted small">Basculez entre <kbd>Par jour</kbd> <kbd>Par semaine</kbd> <kbd>Par mois</kbd> <kbd>Par année</kbd>. Utilisez <kbd>&lt;&lt;</kbd> <kbd>&lt;</kbd> <kbd>Aujourd''hui</kbd> <kbd>&gt;</kbd> <kbd>&gt;&gt;</kbd> pour naviguer.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Tableau détaillé</strong>
              <p class="text-muted small">Sous le graphique, un tableau liste chaque unité de période avec Visiteurs uniques, Pages vues et le ratio Pages/Visiteur.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-graph-up-arrow fs-3 text-primary me-3"></i>
        <span><strong>Conseil :</strong> Un pic de pages vues sans hausse des visiteurs uniques suggère qu''un membre existant navigue en profondeur — consultez le tableau croisé pour plus de détails.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Statystyki odwiedzających</h1>
    <p class="lead">Wizualizuj trendy odwiedzin i wyświetlenia stron w czasie.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie wykresu</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-people-fill"></i></div>
              <div><strong>Unikalni odwiedzający</strong>
              <p class="text-muted small">Linia (lewa oś) — liczba odrębnych odwiedzających na jednostkę okresu. Odwiedzający liczony raz na sesję niezależnie od przeglądanych stron.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
              <div><strong>Wyświetlenia stron</strong>
              <p class="text-muted small">Słupki (prawa oś) — całkowita liczba załadowanych stron. Wysoki wskaźnik Strony/Odwiedzający wskazuje na duże zaangażowanie.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-range"></i></div>
              <div><strong>Nawigacja czasowa</strong>
              <p class="text-muted small">Przełączaj między <kbd>Dzień</kbd> <kbd>Tydzień</kbd> <kbd>Miesiąc</kbd> <kbd>Rok</kbd>. Używaj <kbd>&lt;&lt;</kbd> <kbd>&lt;</kbd> <kbd>Dzisiaj</kbd> <kbd>&gt;</kbd> <kbd>&gt;&gt;</kbd> do nawigacji.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-table"></i></div>
              <div><strong>Szczegółowa tabela</strong>
              <p class="text-muted small">Pod wykresem tabela zawiera każdą jednostkę okresu z Unikalnymi odwiedzającymi, Wyświetleniami stron i wskaźnikiem Strony/Odwiedzający.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-graph-up-arrow fs-3 text-primary me-3"></i>
        <span><strong>Wskazówka:</strong> Skok wyświetleń stron bez wzrostu unikalnych odwiedzających sugeruje, że istniejący członek intensywnie przegląda — sprawdź tabelę krzyżową po szczegóły.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>');
INSERT INTO "Languages" VALUES (416,'Help_Analytics','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Visitor Summary</h1>
    <p class="lead">Understand the technical profile of your visitors at a glance.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">The four charts</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-laptop-fill"></i></div>
              <div><strong>Operating systems</strong>
              <p class="text-muted small">Pie chart of OS used: Linux, Windows, iOS, Android… Helps prioritise compatibility testing.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-browser-chrome"></i></div>
              <div><strong>Browsers</strong>
              <p class="text-muted small">Distribution of browsers: Firefox, Chrome, Safari, Mobile Safari… Useful for ensuring cross-browser compatibility.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-aspect-ratio-fill"></i></div>
              <div><strong>Screen resolutions</strong>
              <p class="text-muted small">Breakdown of screen sizes used to access the site. Indicates whether mobile or desktop visitors predominate.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-phone-fill"></i></div>
              <div><strong>Hardware</strong>
              <p class="text-muted small">Type of device: desktop, mobile, tablet. Guides responsive design priorities.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Time navigation:</strong> Switch between <kbd>Day</kbd> <kbd>Week</kbd> <kbd>Month</kbd> <kbd>Year</kbd> and use the arrows to select the desired period. All four charts update simultaneously.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Synthèse des visiteurs</h1>
    <p class="lead">Comprenez le profil technique de vos visiteurs en un coup d''œil.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Les quatre graphiques</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-laptop-fill"></i></div>
              <div><strong>Systèmes d''exploitation</strong>
              <p class="text-muted small">Camembert des OS utilisés : Linux, Windows, iOS, Android… Aide à prioriser les tests de compatibilité.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-browser-chrome"></i></div>
              <div><strong>Navigateurs</strong>
              <p class="text-muted small">Répartition des navigateurs : Firefox, Chrome, Safari, Mobile Safari… Utile pour vérifier la compatibilité cross-browser.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-aspect-ratio-fill"></i></div>
              <div><strong>Résolutions d''écran</strong>
              <p class="text-muted small">Répartition des tailles d''écran utilisées pour accéder au site. Indique si les visiteurs mobile ou bureau prédominent.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-phone-fill"></i></div>
              <div><strong>Matériel</strong>
              <p class="text-muted small">Type d''appareil : ordinateur, mobile, tablette. Guide les priorités de design responsive.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Navigation temporelle :</strong> Basculez entre <kbd>Jour</kbd> <kbd>Semaine</kbd> <kbd>Mois</kbd> <kbd>Année</kbd> et utilisez les flèches pour sélectionner la période souhaitée. Les quatre graphiques se mettent à jour simultanément.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Podsumowanie odwiedzających</h1>
    <p class="lead">Zrozum profil techniczny swoich odwiedzających jednym spojrzeniem.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Cztery wykresy</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-laptop-fill"></i></div>
              <div><strong>Systemy operacyjne</strong>
              <p class="text-muted small">Wykres kołowy używanych OS: Linux, Windows, iOS, Android… Pomaga ustalić priorytety testów kompatybilności.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-browser-chrome"></i></div>
              <div><strong>Przeglądarki</strong>
              <p class="text-muted small">Rozkład przeglądarek: Firefox, Chrome, Safari, Mobile Safari… Przydatne do sprawdzania kompatybilności cross-browser.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-aspect-ratio-fill"></i></div>
              <div><strong>Rozdzielczości ekranu</strong>
              <p class="text-muted small">Rozkład rozmiarów ekranów używanych do odwiedzania strony. Wskazuje, czy przeważają odwiedzający mobilni czy stacjonarni.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-phone-fill"></i></div>
              <div><strong>Sprzęt</strong>
              <p class="text-muted small">Typ urządzenia: komputer, telefon, tablet. Kieruje priorytetami projektowania responsywnego.</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-calendar-range fs-3 text-primary me-3"></i>
        <span><strong>Nawigacja czasowa:</strong> Przełączaj między <kbd>Dzień</kbd> <kbd>Tydzień</kbd> <kbd>Miesiąc</kbd> <kbd>Rok</kbd> i używaj strzałek do wyboru okresu. Wszystkie cztery wykresy aktualizują się jednocześnie.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>');
INSERT INTO "Languages" VALUES (417,'Help_LastVisits','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Last Visits</h1>
    <p class="lead">See who is on the site right now and who visited recently.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Member &amp; Email</strong>
              <p class="text-muted small">Full name and email address of the member. Sorted by most recent activity first.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Last page</strong>
              <p class="text-muted small">The last URI visited by the member, highlighted in pink. Click to navigate to that page.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>Last activity</strong>
              <p class="text-muted small">Relative time (e.g. <em>Just now</em>, <em>23 hours</em>, <em>7 days</em>) and exact timestamp of the last recorded action.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-display-fill"></i></div>
              <div><strong>OS &amp; Browser</strong>
              <p class="text-muted small">Operating system and browser used during the last session (e.g. Linux / Firefox 140, Android / Chrome Mobile).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-activity"></i></div>
              <div><strong>Active visitor count</strong>
              <p class="text-muted small">The badge at the top shows the number of visitors <strong>currently connected</strong>, and the number of <strong>active users</strong> over the recent period.
              <span class="d-block mt-1 text-dark">👉 <em>The page does not auto-refresh — reload to get the latest data.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Dernières visites</h1>
    <p class="lead">Voyez qui est sur le site en ce moment et qui a visité récemment.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Membre &amp; Email</strong>
              <p class="text-muted small">Nom complet et adresse email du membre. Trié par activité la plus récente en premier.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Dernière page</strong>
              <p class="text-muted small">La dernière URI visitée par le membre, surlignée en rose. Cliquez pour naviguer vers cette page.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>Dernière activité</strong>
              <p class="text-muted small">Temps relatif (ex. <em>À l''instant</em>, <em>23 heures</em>, <em>7 jours</em>) et horodatage exact de la dernière action enregistrée.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-display-fill"></i></div>
              <div><strong>OS &amp; Navigateur</strong>
              <p class="text-muted small">Système d''exploitation et navigateur utilisés lors de la dernière session (ex. Linux / Firefox 140, Android / Chrome Mobile).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-activity"></i></div>
              <div><strong>Compteurs d''activité</strong>
              <p class="text-muted small">Le badge en haut indique le nombre de visiteurs <strong>actuellement connectés</strong> et le nombre d''<strong>utilisateurs actifs</strong> sur la période récente.
              <span class="d-block mt-1 text-dark">👉 <em>La page ne se rafraîchit pas automatiquement — rechargez pour obtenir les données les plus récentes.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Ostatnie wizyty</h1>
    <p class="lead">Zobacz, kto jest teraz na stronie i kto odwiedził ją ostatnio.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Członek &amp; Email</strong>
              <p class="text-muted small">Pełne imię i nazwisko oraz adres email członka. Posortowane według najnowszej aktywności.</p></div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-link-45deg"></i></div>
              <div><strong>Ostatnia strona</strong>
              <p class="text-muted small">Ostatnie URI odwiedzone przez członka, wyróżnione różowym kolorem. Kliknij, aby przejść do tej strony.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-clock-fill"></i></div>
              <div><strong>Ostatnia aktywność</strong>
              <p class="text-muted small">Czas względny (np. <em>Przed chwilą</em>, <em>23 godziny</em>, <em>7 dni</em>) i dokładny znacznik czasu ostatniej zarejestrowanej akcji.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-display-fill"></i></div>
              <div><strong>OS &amp; Przeglądarka</strong>
              <p class="text-muted small">System operacyjny i przeglądarka użyte podczas ostatniej sesji (np. Linux / Firefox 140, Android / Chrome Mobile).</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-activity"></i></div>
              <div><strong>Liczniki aktywności</strong>
              <p class="text-muted small">Etykieta u góry pokazuje liczbę odwiedzających <strong>aktualnie połączonych</strong> i liczbę <strong>aktywnych użytkowników</strong> w ostatnim okresie.
              <span class="d-block mt-1 text-dark">👉 <em>Strona nie odświeża się automatycznie — przeładuj, aby uzyskać najnowsze dane.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>');
INSERT INTO "Languages" VALUES (418,'Help_AlertAsked','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Contextual Help: Member Alerts</h1>
    <p class="lead">See which members have requested to be notified of new content.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Reading the table</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Member</strong>
              <p class="text-muted small">Full name of the member (with nickname in brackets if set). Members are listed alphabetically.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-slash-fill"></i></div>
              <div><strong>No alert</strong>
              <p class="text-muted small">An <strong>X</strong> in this column means the member has chosen not to receive any notification.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-event-fill"></i></div>
              <div><strong>Event</strong>
              <p class="text-muted small">An <strong>X</strong> means the member is notified by email when a new event is published.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-newspaper"></i></div>
              <div><strong>Article</strong>
              <p class="text-muted small">An <strong>X</strong> means the member is notified by email when a new article is published.
              <span class="d-block mt-1 text-dark">👉 <em>A member can subscribe to both types simultaneously.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Key points</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-gear-fill fs-3 text-primary me-3"></i>
        <span><strong>Member preferences:</strong> Each member manages their own notification settings from their personal profile. This page is a read-only overview for administrators.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions:</strong> Access is restricted to members with administration authorisation.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : Alertes demandées par les membres</h1>
    <p class="lead">Consultez quels membres souhaitent être notifiés lors de la publication de nouveaux contenus.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Lire le tableau</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Membre</strong>
              <p class="text-muted small">Nom complet du membre (avec surnom entre parenthèses si renseigné). Les membres sont listés par ordre alphabétique.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-slash-fill"></i></div>
              <div><strong>Aucune alerte</strong>
              <p class="text-muted small">Un <strong>X</strong> dans cette colonne indique que le membre a choisi de ne recevoir aucune notification.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-event-fill"></i></div>
              <div><strong>Evénement</strong>
              <p class="text-muted small">Un <strong>X</strong> indique que le membre est notifié par email lors de la publication d''un nouvel événement.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-newspaper"></i></div>
              <div><strong>Article</strong>
              <p class="text-muted small">Un <strong>X</strong> indique que le membre est notifié par email lors de la publication d''un nouvel article.
              <span class="d-block mt-1 text-dark">👉 <em>Un membre peut souscrire aux deux types simultanément.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Ce qu''il faut retenir</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-gear-fill fs-3 text-primary me-3"></i>
        <span><strong>Préférences membres :</strong> Chaque membre gère ses propres paramètres de notification depuis son profil personnel. Cette page est une vue d''ensemble en lecture seule pour les administrateurs.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Permissions :</strong> L''accès est réservé aux membres disposant de l''autorisation d''administration.</span>
      </div>
    </div>
  </section>
</div>','<div class="container my-5">
  <header class="mb-5 border-bottom pb-3">
    <h1 class="display-5 fw-bold text-primary">Pomoc kontekstowa: Alerty zamówione przez członków</h1>
    <p class="lead">Sprawdź, którzy członkowie chcą być powiadamiani o nowych treściach.</p>
  </header>
  <section class="mb-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title h4 mb-4">Czytanie tabeli</h2>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-person-fill"></i></div>
              <div><strong>Członek</strong>
              <p class="text-muted small">Pełne imię i nazwisko członka (z pseudonimem w nawiasach, jeśli podano). Członkowie są wymienieni alfabetycznie.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-bell-slash-fill"></i></div>
              <div><strong>Brak alertu</strong>
              <p class="text-muted small"><strong>X</strong> w tej kolumnie oznacza, że członek wybrał brak powiadomień.</p></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex align-items-start mb-3">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-calendar-event-fill"></i></div>
              <div><strong>Wydarzenie</strong>
              <p class="text-muted small"><strong>X</strong> oznacza, że członek otrzymuje powiadomienie e-mail przy publikacji nowego wydarzenia.</p></div>
            </div>
            <div class="d-flex align-items-start">
              <div class="bg-primary text-white rounded p-2 me-3"><i class="bi bi-newspaper"></i></div>
              <div><strong>Artykuł</strong>
              <p class="text-muted small"><strong>X</strong> oznacza, że członek otrzymuje powiadomienie e-mail przy publikacji nowego artykułu.
              <span class="d-block mt-1 text-dark">👉 <em>Członek może subskrybować oba typy jednocześnie.</em></span></p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <hr class="my-5">
  <section>
    <h2 class="h4 mb-4">Co warto zapamiętać</h2>
    <div class="list-group">
      <div class="list-group-item d-flex align-items-center">
        <i class="bi bi-gear-fill fs-3 text-primary me-3"></i>
        <span><strong>Preferencje członków:</strong> Każdy członek zarządza własnymi ustawieniami powiadomień z poziomu swojego profilu. Ta strona jest widokiem tylko do odczytu dla administratorów.</span>
      </div>
      <div class="list-group-item d-flex align-items-center bg-light">
        <i class="bi bi-shield-lock-fill fs-3 text-primary me-3"></i>
        <span><strong>Uprawnienia:</strong> Dostęp jest zarezerwowany dla członków posiadających uprawnienie administracyjne.</span>
      </div>
    </div>
  </section>
</div>');
INSERT INTO "Languages" VALUES (419,'attribute_details','🔍 Attribute details','🔍 Détail des attributs','🔍 Szczegóły atrybutów');
INSERT INTO "Languages" VALUES (420,'designer.home_settings.paragraphs_label','Number of paragraphs to display','Nombre de paragraphes à afficher','Liczba akapitów do wyświetlenia');
INSERT INTO "Languages" VALUES (421,'designer.home_settings.paragraphs_description','Limits the number of paragraphs shown from the featured article on the home page.','Limite le nombre de paragraphes affichés de l''article mis en avant sur la page d''accueil.','Ogranicza liczbę akapitów wyświetlanych z wyróżnionego artykułu na stronie głównej.');
INSERT INTO "Languages" VALUES (422,'designer.home_settings.paragraphs_zero_hint','0 = display the entire article','0 = afficher l''article entier','0 = wyświetl cały artykuł');
INSERT INTO "Languages" VALUES (423,'designer.home_settings.section_images','Images','Images','Obrazy');
INSERT INTO "Languages" VALUES (424,'designer.home_settings.article_featured_status','Article #{n} will be displayed on the home page.','L''article #{n} sera affiché en page d''accueil.','Artykuł #{n} zostanie wyświetlony na stronie głównej.');
INSERT INTO "Languages" VALUES (425,'designer.home_settings.article_auto_status','The first paragraph of the last published article (or featured one) will be displayed.','Le 1er paragraphe du dernier article publié (ou celui mis en avant) sera affiché.','Zostanie wyświetlony pierwszy akapit ostatniego opublikowanego artykułu (lub wyróżnionego).');
INSERT INTO "Languages" VALUES (426,'designer.home_settings.paragraphs_all','(entire article)','(article entier)','(cały artykuł)');
INSERT INTO "Languages" VALUES (427,'designer.home_settings.paragraphs_count','({n} paragraph(s))','({n} paragraphe(s))','({n} akapit(y))');
INSERT INTO "Languages" VALUES (428,'designer.home_settings.article_auto_preview_label','1st paragraph of last article / featured article','1er paragraphe du dernier article / article mis en avant','1. akapit ostatniego artykułu / wyróżnionego artykułu');
INSERT INTO "Languages" VALUES (429,'designer.home_settings.latest_hidden_status','The "latest articles" section <strong>will not be displayed</strong>.','La section « derniers articles » <strong>ne sera pas affichée</strong>.','Sekcja „ostatnie artykuły" <strong>nie będzie wyświetlana</strong>.');
INSERT INTO "Languages" VALUES (430,'designer.home_settings.latest_count_status','The <strong>{n}</strong> latest articles will be listed.','Les <strong>{n}</strong> derniers articles seront listés.','<strong>{n}</strong> ostatnich artykułów zostanie wylistowanych.');
INSERT INTO "Languages" VALUES (431,'designer.home_settings.image_processing','Processing…','Traitement en cours…','Przetwarzanie…');
INSERT INTO "Languages" VALUES (432,'designer.home_settings.image_to_save','Unsaved','À sauvegarder','Do zapisania');
INSERT INTO "Languages" VALUES (433,'designer.home_settings.image_read_error','Unable to read the image.','Impossible de lire l''image.','Nie można odczytać obrazu.');
INSERT INTO "Languages" VALUES (434,'article.change_owner.title','Change owner','Changer le propriétaire','Zmień właściciela');
INSERT INTO "Languages" VALUES (435,'article.change_owner.current_owner','Current owner:','Propriétaire actuel :','Aktualny właściciel:');
INSERT INTO "Languages" VALUES (436,'article.change_owner.new_owner','New owner','Nouveau propriétaire','Nowy właściciel');
INSERT INTO "Languages" VALUES (437,'article.change_owner.select_redactor','-- Select a redactor --','-- Sélectionner un rédacteur --','-- Wybierz redaktora --');
INSERT INTO "Languages" VALUES (438,'article.publish.title','Article publication','Publication de l''article','Publikacja artykułu');
INSERT INTO "Languages" VALUES (439,'article.publish.created_by','Created by:','Créé par :','Utworzony przez:');
INSERT INTO "Languages" VALUES (440,'article.publish.is_published','Article published','Article publié','Artykuł opublikowany');
INSERT INTO "Languages" VALUES (441,'article.publish.do_publish','Publish this article','Publier cet article','Opublikuj ten artykuł');
INSERT INTO "Languages" VALUES (442,'article.publish.spotlight','Feature this article','Mettre à la une','Wyróżnij ten artykuł');
INSERT INTO "Languages" VALUES (443,'article.publish.spotlight_date','Select the date until which the article will be featured','Sélectionnez la date jusqu''à laquelle l''article sera mis à la une','Wybierz datę, do której artykuł będzie wyróżniony');
INSERT INTO "Languages" VALUES (444,'visitor_insights.statistics.title','Visitor Statistics','Statistiques des visiteurs','Statystyki odwiedzających');
INSERT INTO "Languages" VALUES (445,'visitor_insights.statistics.by_day','By day','Par jour','Według dnia');
INSERT INTO "Languages" VALUES (446,'visitor_insights.statistics.by_week','By week','Par semaine','Według tygodnia');
INSERT INTO "Languages" VALUES (447,'visitor_insights.statistics.by_month','By month','Par mois','Według miesiąca');
INSERT INTO "Languages" VALUES (448,'visitor_insights.statistics.by_year','By year','Par année','Według roku');
INSERT INTO "Languages" VALUES (449,'visitor_insights.statistics.today','Today','Aujourd''hui','Dzisiaj');
INSERT INTO "Languages" VALUES (450,'visitor_insights.statistics.details_of','Details of','Détails des','Szczegóły');
INSERT INTO "Languages" VALUES (451,'visitor_insights.statistics.unique_visitors','Unique visitors','Visiteurs uniques','Unikalni odwiedzający');
INSERT INTO "Languages" VALUES (452,'visitor_insights.statistics.page_views','Page views','Pages vues','Odsłony stron');
INSERT INTO "Languages" VALUES (453,'visitor_insights.statistics.pages_per_visitor','Pages/Visitor','Pages/Visiteur','Strony/Odwiedzający');
INSERT INTO "Languages" VALUES (454,'visitor_insights.statistics.chart.2xx','2XX – Success','2XX – Succès','2XX – Sukces');
INSERT INTO "Languages" VALUES (455,'visitor_insights.statistics.chart.3xx','3XX – Redirects','3XX – Redirections','3XX – Przekierowania');
INSERT INTO "Languages" VALUES (456,'visitor_insights.statistics.chart.4xx','4XX – Client errors','4XX – Erreurs client','4XX – Błędy klienta');
INSERT INTO "Languages" VALUES (457,'visitor_insights.statistics.chart.5xx','5XX – Server errors','5XX – Erreurs serveur','5XX – Błędy serwera');
INSERT INTO "Languages" VALUES (458,'account.form.imported.title','Imported account','Compte importé','Importowane konto');
INSERT INTO "Languages" VALUES (459,'account.form.imported.webmaster_message','The account information must be updated in the external system. In case of an email address change, update it here first before performing a new import.','Il faut mettre à jour les informations du compte dans le système externe. En cas de changement d''adresse courriel, il faut d''abord la mettre à jour ici avant de faire une nouvelle importation.','Informacje o koncie muszą zostać zaktualizowane w systemie zewnętrznym. W przypadku zmiany adresu e-mail należy najpierw zaktualizować go tutaj przed wykonaniem nowego importu.');
INSERT INTO "Languages" VALUES (460,'account.form.imported.readonly_message','Greyed-out fields cannot be edited here. They are synchronised with the external system.','Les champs grisés ne sont pas modifiables ici. Ils sont synchronisés avec le système externe.','Wyszarzone pola nie są edytowalne tutaj. Są synchronizowane z systemem zewnętrznym.');
INSERT INTO "Languages" VALUES (461,'account.form.email.label','Email','Email','Email');
INSERT INTO "Languages" VALUES (462,'account.form.email.invalid','Please enter a valid email address','Veuillez saisir une adresse email valide','Proszę podać prawidłowy adres email');
INSERT INTO "Languages" VALUES (463,'account.form.firstname.label','First name','Prénom','Imię');
INSERT INTO "Languages" VALUES (464,'account.form.firstname.required','First name is required.','Le prénom est requis.','Imię jest wymagane.');
INSERT INTO "Languages" VALUES (465,'account.form.lastname.label','Last name','Nom','Nazwisko');
INSERT INTO "Languages" VALUES (466,'account.form.lastname.required','Last name is required.','Le nom est requis.','Nazwisko jest wymagane.');
INSERT INTO "Languages" VALUES (467,'account.form.nickname.label','Nickname','Pseudo','Pseudonim');
INSERT INTO "Languages" VALUES (468,'account.form.emoji.select_label','Select an Emoji','Sélectionnez un Emoji','Wybierz Emoji');
INSERT INTO "Languages" VALUES (469,'account.form.emoji.paste_placeholder','Paste your emoji here…','Collez votre emoji ici…','Wklej emoji tutaj…');
INSERT INTO "Languages" VALUES (470,'account.form.emoji.getemoji_title','Open getemoji.com to copy an emoji','Ouvrir getemoji.com pour copier un emoji','Otwórz getemoji.com, aby skopiować emoji');
INSERT INTO "Languages" VALUES (471,'account.form.emoji.missing_elements','Emoji picker: missing elements','Emoji picker : éléments manquants','Emoji picker: brakujące elementy');
INSERT INTO "Languages" VALUES (472,'account.form.emoji.none_detected','⚠ No emoji detected. Try 😊','⚠ Aucun emoji détecté. Essayez 😊','⚠ Nie wykryto emoji. Spróbuj 😊');
INSERT INTO "Languages" VALUES (473,'account.form.emoji.selected','✓ Emoji « %s » selected!','✓ Emoji « %s » sélectionné !','✓ Emoji « %s » wybrany!');
INSERT INTO "Languages" VALUES (474,'account.form.gravatar.use','Use my gravatar','Utiliser mon gravatar','Użyj mojego gravatara');
INSERT INTO "Languages" VALUES (475,'account.form.admin_only.note','Fields visible and editable only by PersonManager','Champs visibles et modifiables uniquement par le PersonManager','Pola widoczne i edytowalne tylko przez PersonManager');
INSERT INTO "Languages" VALUES (476,'account.form.alert.label','Alert','Alerte','Alert');
INSERT INTO "Languages" VALUES (477,'account.form.member_info.label','Member info','Info membre','Info o członku');
INSERT INTO "Languages" VALUES (478,'account.form.cancel','Cancel','Annuler','Anuluj');
INSERT INTO "Languages" VALUES (479,'account.form.submit','Save','Valider','Zatwierdź');
INSERT INTO "Languages" VALUES (480,'navbar.webmaster.turnstile','Anti-spam protection (Turnstile)','Protection anti-spam (Turnstile)','Ochrona antyspamowa (Turnstile)');
INSERT INTO "Languages" VALUES (481,'turnstile.title','Anti-spam protection (Turnstile)','Protection anti-spam (Turnstile)','Ochrona antyspamowa (Turnstile)');
INSERT INTO "Languages" VALUES (482,'turnstile.alert.not_configured','No key configured — Turnstile verification is disabled. The contact form remains protected by the honeypot, timing check and rate limiting.','Aucune clé configurée — la vérification Turnstile est désactivée. Le formulaire de contact reste protégé par le honeypot, le timing et le rate limiting.','Brak skonfigurowanego klucza — weryfikacja Turnstile jest wyłączona. Formularz kontaktowy jest nadal chroniony przez honeypot, kontrolę czasu i rate limiting.');
INSERT INTO "Languages" VALUES (483,'turnstile.info.get_keys','Get your free keys at Cloudflare Turnstile (up to 1 million verifications/month).','Obtenez vos clés gratuitement sur Cloudflare Turnstile (jusqu''à 1 million de vérifications/mois).','Uzyskaj bezpłatne klucze na Cloudflare Turnstile (do 1 miliona weryfikacji/miesiąc).');
INSERT INTO "Languages" VALUES (484,'turnstile.info.localhost','For local testing, use the universal Cloudflare keys.','Pour les tests en local, utilisez les clés universelles Cloudflare.','Do testów lokalnych użyj uniwersalnych kluczy Cloudflare.');
INSERT INTO "Languages" VALUES (485,'turnstile.field.site_key','Site Key','Site Key','Klucz witryny');
INSERT INTO "Languages" VALUES (486,'turnstile.field.site_key.hint','Integrated into the contact form HTML.','Intégrée dans le HTML du formulaire de contact.','Zintegrowany z kodem HTML formularza kontaktowego.');
INSERT INTO "Languages" VALUES (487,'turnstile.field.site_key.public','public key','clé publique','klucz publiczny');
INSERT INTO "Languages" VALUES (488,'turnstile.field.secret_key','Secret Key','Secret Key','Klucz tajny');
INSERT INTO "Languages" VALUES (489,'turnstile.field.secret_key.private','private key','clé privée','klucz prywatny');
INSERT INTO "Languages" VALUES (490,'turnstile.field.secret_key.not_configured','Not configured','Non configurée','Nieskonfigurowany');
INSERT INTO "Languages" VALUES (491,'turnstile.field.secret_key.hint','Leave empty to keep the current key.','Laisser vide pour conserver la clé actuelle.','Pozostaw puste, aby zachować bieżący klucz.');
INSERT INTO "Languages" VALUES (492,'dbbrowser.records.empty','No records found.','Aucun enregistrement trouvé.','Nie znaleziono żadnych rekordów.');
INSERT INTO "Languages" VALUES (493,'dbbrowser.delete.confirm','Are you sure you want to delete this record?','Voulez-vous vraiment supprimer cet enregistrement ?','Czy na pewno chcesz usunąć ten rekord?');
INSERT INTO "Languages" VALUES (494,'user_connections.details.title','Connections details','Détails des connexions','Szczegóły połączeń');
INSERT INTO "Languages" VALUES (495,'user_connections.table.people','People','Personnes','Osoby');
INSERT INTO "Languages" VALUES (496,'user_connections.table.common_events','Common events','Événements communs','Wspólne wydarzenia');
INSERT INTO "Languages" VALUES (497,'user_connections.table.intensity','Intensity','Intensité','Intensywność');
INSERT INTO "Languages" VALUES (498,'user_connections.modal.common_events','Common events','Événements communs','Wspólne wydarzenia');
INSERT INTO "Languages" VALUES (499,'user_connections.modal.close','Close','Fermer','Zamknij');
INSERT INTO "Languages" VALUES (500,'events.calendar.page_title','Upcoming Events','Évènements des prochaines semaines','Nadchodzące wydarzenia');
INSERT INTO "Languages" VALUES (501,'events.calendar.heading','Events Calendar','Calendrier des événements','Kalendarz wydarzeń');
INSERT INTO "Languages" VALUES (502,'events.calendar.week','Week','Semaine','Tydzień');
INSERT INTO "Languages" VALUES (503,'events.calendar.no_event','No event','Aucun événement','Brak wydarzeń');
INSERT INTO "Languages" VALUES (504,'events.calendar.no_events_scheduled','No events scheduled for the coming weeks.','Aucun événement programmé pour les prochaines semaines.','Brak zaplanowanych wydarzeń na najbliższe tygodnie.');
INSERT INTO "Languages" VALUES (505,'events.calendar.welcome.title','Welcome to the outings calendar!','Bienvenue sur le calendrier des sorties !','Witamy w kalendarzu wyjazdów!');
INSERT INTO "Languages" VALUES (506,'events.calendar.welcome.description','Find all our events here over 3 weeks.','Retrouvez ici tous nos événements sur 3 semaines.','Znajdź tutaj wszystkie nasze wydarzenia na 3 tygodnie.');
INSERT INTO "Languages" VALUES (507,'events.calendar.welcome.members_only','Most are reserved for members, except those in bold with a link.','La plupart sont réservés aux membres, sauf ceux en gras avec un lien.','Większość jest zarezerwowana dla członków, z wyjątkiem tych pogrubionych z linkiem.');
INSERT INTO "Languages" VALUES (508,'events.calendar.welcome.colored_squares','Colored squares = special info, hover to see details.','Carrés colorés = infos spéciales, survolez pour voir le détail.','Kolorowe kwadraty = specjalne informacje, najedź, aby zobaczyć szczegóły.');
INSERT INTO "Languages" VALUES (509,'events.calendar.welcome.group_name','[Group name] = reserved for members of that group.','[Nom de groupe] = réservé aux membres de ce groupe.','[Nazwa grupy] = zarezerwowane dla członków tej grupy.');
INSERT INTO "Languages" VALUES (510,'events.calendar.welcome.agenda','Mark your calendars! 📆✨','À vos agendas ! 📆✨','Do kalendarzy! 📆✨');
INSERT INTO "Languages" VALUES (511,'events.calendar.welcome.register_button','Register for a public event','S''inscrire à un événement public','Zarejestruj się na wydarzenie publiczne');
INSERT INTO "Languages" VALUES (512,'events.calendar.rss_subscribe','Subscribe to RSS feed','S''abonner au flux RSS','Subskrybuj kanał RSS');
INSERT INTO "Languages" VALUES (513,'events.filter.by_preferences','Only events matching my preferences','Uniquement les événements qui correspondent à mes préférences','Tylko wydarzenia pasujące do moich preferencji');
INSERT INTO "Languages" VALUES (514,'events.click_to_detail','Click on an event row to view details, register or unregister','Cliquer sur la ligne d''un événement pour voir le détail, s''inscrire ou se désinscrire','Kliknij wiersz wydarzenia, aby zobaczyć szczegóły, zapisać się lub wypisać');
INSERT INTO "Languages" VALUES (515,'events.no_attribute','No attribute','Aucun attribut','Brak atrybutu');
INSERT INTO "Languages" VALUES (516,'events.email.modal_title','Send an email','Envoyer un courriel','Wyślij wiadomość e-mail');
INSERT INTO "Languages" VALUES (517,'events.email.message_type','Message type','Type de message','Typ wiadomości');
INSERT INTO "Languages" VALUES (518,'events.email.select_type','Select a type','Sélectionnez un type','Wybierz typ');
INSERT INTO "Languages" VALUES (519,'events.email.type.new','New event','Nouvel événement','Nowe wydarzenie');
INSERT INTO "Languages" VALUES (520,'events.email.type.reminder','Reminder','Rappel','Przypomnienie');
INSERT INTO "Languages" VALUES (521,'events.email.type.canceled','Canceled','Annulé','Odwołane');
INSERT INTO "Languages" VALUES (522,'events.email.type.modified','Modified','Modifié','Zmodyfikowane');
INSERT INTO "Languages" VALUES (523,'events.email.recipients','Recipients','Destinataires','Odbiorcy');
INSERT INTO "Languages" VALUES (524,'events.email.select_type_first','Select a message type first','Sélectionnez d''abord un type de message','Najpierw wybierz typ wiadomości');
INSERT INTO "Languages" VALUES (525,'events.email.message','Message','Message','Wiadomość');
INSERT INTO "Languages" VALUES (526,'events.email.message_placeholder','Enter your message...','Saisissez votre message...','Wprowadź wiadomość...');
INSERT INTO "Languages" VALUES (527,'events.email.send','Send','Envoyer','Wyślij');
INSERT INTO "Languages" VALUES (528,'events.form.modal_title','Manage an event','Gérer un événement','Zarządzaj wydarzeniem');
INSERT INTO "Languages" VALUES (529,'events.form.title_label','Title','Titre','Tytuł');
INSERT INTO "Languages" VALUES (530,'events.form.title_placeholder','Title in the calendar','Titre dans le calendrier','Tytuł w kalendarzu');
INSERT INTO "Languages" VALUES (531,'events.form.description_placeholder','Event details','Détails de l''événement','Szczegóły wydarzenia');
INSERT INTO "Languages" VALUES (532,'events.form.location_label','Location','Lieu','Miejsce');
INSERT INTO "Languages" VALUES (533,'events.form.location_placeholder','Street / place name, city','Rue / lieu dit, ville','Ulica / nazwa miejsca, miasto');
INSERT INTO "Languages" VALUES (534,'events.form.event_type','Event type','Type d''événement','Typ wydarzenia');
INSERT INTO "Languages" VALUES (535,'events.form.date_time_duration','Date / Time / Duration (h)','Date / Heure / Durée (h)','Data / Godzina / Czas trwania (h)');
INSERT INTO "Languages" VALUES (536,'events.form.attributes','Attributes','Attributs','Atrybuty');
INSERT INTO "Languages" VALUES (537,'events.form.add','Add','Ajouter','Dodaj');
INSERT INTO "Languages" VALUES (538,'events.form.needs','Needs','Besoins','Potrzeby');
INSERT INTO "Languages" VALUES (539,'events.form.need_type_placeholder','Need type','Type de besoin','Typ potrzeby');
INSERT INTO "Languages" VALUES (540,'events.form.select_need_type_first','Select a need type first','Sélectionnez d''abord un type de besoin','Najpierw wybierz typ potrzeby');
INSERT INTO "Languages" VALUES (541,'events.form.max_participants','Maximum number of participants','Nombre max de participants','Maksymalna liczba uczestników');
INSERT INTO "Languages" VALUES (542,'events.form.unlimited','0 = unlimited','0 = illimité','0 = nieograniczone');
INSERT INTO "Languages" VALUES (543,'events.form.audience_label','Audience','Public','Publiczność');
INSERT INTO "Languages" VALUES (544,'events.form.audience.members_only','Club members only','Membres du club uniquement','Tylko członkowie klubu');
INSERT INTO "Languages" VALUES (545,'events.form.audience.guests','Club members and by invitation','Membres du club et sur « invitation »','Członkowie klubu i na zaproszenie');
INSERT INTO "Languages" VALUES (546,'events.form.audience.all','Everyone','Tous','Wszyscy');
INSERT INTO "Languages" VALUES (547,'events.form.create','Create','Créer','Utwórz');
INSERT INTO "Languages" VALUES (548,'events.duplicate.modal_title','What would you like to do?','Que souhaitez-vous faire ?','Co chcesz zrobić?');
INSERT INTO "Languages" VALUES (549,'events.duplicate.today','Duplicate today at 23:59','Dupliquer aujourd''hui à 23:59','Duplikuj dzisiaj o 23:59');
INSERT INTO "Languages" VALUES (550,'events.duplicate.tomorrow','Duplicate tomorrow at the same time','Dupliquer demain même heure','Duplikuj jutro o tej samej godzinie');
INSERT INTO "Languages" VALUES (551,'events.duplicate.next_week','Duplicate same day/time next week','Dupliquer même jour/heure la semaine prochaine','Duplikuj ten sam dzień/godzinę w przyszłym tygodniu');
INSERT INTO "Languages" VALUES (552,'events.duplicate.confirm','Confirm','Confirmer','Potwierdź');
INSERT INTO "Languages" VALUES (553,'chat.no_messages','No messages yet.\nBe the first to write!','Aucun message pour le moment.\nSoyez le premier à écrire !','Brak wiadomości.\nBądź pierwszą osobą, która napisze!');
INSERT INTO "Languages" VALUES (554,'chat.online','Online:','En ligne :','Online:');
INSERT INTO "Languages" VALUES (555,'chat.placeholder','Write your message...','Écrivez votre message...','Napisz wiadomość...');
INSERT INTO "Languages" VALUES (556,'chat.send','Send','Envoyer','Wyślij');
INSERT INTO "Languages" VALUES (557,'chat.edit_modal.title','Edit message','Modifier le message','Edytuj wiadomość');
INSERT INTO "Languages" VALUES (558,'chat.edit_modal.message_label','Message:','Message :','Wiadomość:');
INSERT INTO "Languages" VALUES (559,'chat.edit_modal.delete','Delete','Supprimer','Usuń');
INSERT INTO "Languages" VALUES (560,'chat.edit_modal.cancel','Cancel','Annuler','Anuluj');
INSERT INTO "Languages" VALUES (561,'chat.edit_modal.save','Save','Enregistrer','Zapisz');
INSERT INTO "Languages" VALUES (562,'chat.error.send_failed','Unable to send message','Impossible d''envoyer le message','Nie można wysłać wiadomości');
INSERT INTO "Languages" VALUES (563,'chat.confirm.delete','Are you sure you want to delete this message?','Êtes-vous sûr de vouloir supprimer ce message ?','Czy na pewno chcesz usunąć tę wiadomość?');
INSERT INTO "Languages" VALUES (564,'chat.error.update_failed','An error occurred while editing the message','Une erreur est survenue lors de la modification du message','Wystąpił błąd podczas edycji wiadomości');
INSERT INTO "Languages" VALUES (565,'chat.error.delete_failed','An error occurred while deleting the message','Une erreur est survenue lors de la suppression du message','Wystąpił błąd podczas usuwania wiadomości');
INSERT INTO "Languages" VALUES (566,'chat.no_active_users','No active users','Aucun utilisateur actif','Brak aktywnych użytkowników');
INSERT INTO "Languages" VALUES (567,'chat.no_messages','No messages yet.\nBe the first to write!','Aucun message pour le moment.\nSoyez le premier à écrire !','Brak wiadomości.\nBądź pierwszy!');
INSERT INTO "Languages" VALUES (568,'chat.online','Online:','En ligne :','Online:');
INSERT INTO "Languages" VALUES (571,'chat.edit_title','Edit message','Modifier le message','Edytuj wiadomość');
INSERT INTO "Languages" VALUES (572,'chat.edit_icon_title','Edit','Modifier','Edytuj');
INSERT INTO "Languages" VALUES (573,'chat.message_label','Message:','Message :','Wiadomość:');
INSERT INTO "Languages" VALUES (574,'chat.delete','Delete','Supprimer','Usuń');
INSERT INTO "Languages" VALUES (575,'chat.cancel','Cancel','Annuler','Anuluj');
INSERT INTO "Languages" VALUES (576,'chat.save','Save','Enregistrer','Zapisz');
INSERT INTO "Metadata" VALUES (1,'MyClub',41,0,1000000,NULL,10,36,6,NULL,0,NULL);
INSERT INTO "Person" VALUES (1,'webmaster@myclub.foo','e427c26faca947919b18b797bc143a35100e4de48c34b70b26202d3a7d8e51f7','my first name','my last name','my nick name or nothing',NULL,'0',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2025-01-01',0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,'');
INSERT INTO "PersonGroup" VALUES (1,1,1);
INSERT INTO "Settings" VALUES (1,'Title','title');
INSERT INTO "Settings" VALUES (2,'LegalNotices','LegalNotices');
INSERT INTO "Settings" VALUES (3,'SpotlightArticle','');
INSERT INTO "Settings" VALUES (4,'Home_header','<h1 class="text-center">🚧🔧🛠️ Under Construction 🛠️🔧🚧</h1>');
INSERT INTO "Settings" VALUES (5,'Home_footer','<div style="text-align:center; font-size:2.4em; line-height:1.4;">
🚧👷‍♂️🔧👷‍♀️🚧<br>
<b>WORK IN PROGRESS</b><br>
🚧👷‍♀️🔧👷‍♂️🚧
</div>');
COMMIT;
