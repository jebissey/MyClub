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
                <h2 class="fw-bold text-primary">Administration Area Tools</h2>
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
                                <h3 class="h5 fw-bold mb-2">Access and Permissions</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Visibility:</strong> The yellow key only appears in the top bar for members with the appropriate permissions.
                                    </li>
                                    <li>
                                        <strong>Smart navigation:</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Multiple areas → selection menu</li>
                                            <li>→ Single area → automatic redirection (time saver 😊)</li>
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
                                <h3 class="h5 fw-bold mb-2">Mobile Optimization</h3>
                                <p class="text-muted small">
                                    Shortcuts are also displayed directly here — no need to open the ☰ menu on smaller screens.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h4 mb-3 fw-bold">Key Takeaways</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>
                    The <strong>yellow key</strong> is only visible to members with permissions.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>
                    A single administration area → automatic redirection.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>
                    On mobile: direct shortcuts on this page.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span>
                    <strong>Contextual help:</strong> available in each module via the help icon.
                </span>
            </div>
        </div>
    </section>
</div>','<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Les outils des zones d’administration</h2>
                <p class="lead text-muted mb-4">
                    Cette page permet d’accéder aux outils d''administration selon vos droits.
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
                                        <strong>Visibilité :</strong> La clé jaune n’apparaît dans la barre supérieure que pour les membres ayant des autorisations.
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
                                    Les raccourcis apparaissent aussi directement ici — plus besoin d’ouvrir le menu ☰ sur les écrans étroits.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h4 mb-3 fw-bold">Ce qu’il faut retenir</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>
                    La <strong>clé jaune</strong> n’est visible que pour les membres ayant des autorisations.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>
                    Une seule zone d’administration → redirection automatique.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>
                    Sur mobile : raccourcis directs sur cette page.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span>
                    <strong>Aide contextuelle :</strong> disponible dans chaque module via l’icône d’aide.
                </span>
            </div>
        </div>
    </section>
</div>','<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Narzędzia obszarów administracyjnych</h2>
                <p class="lead text-muted mb-4">
                    Ta strona daje dostęp do narzędzi administracyjnych zgodnie z Twoimi uprawnieniami.
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
                                        <strong>Widoczność:</strong> Żółty klucz pojawia się na górnym pasku tylko dla członków z odpowiednimi uprawnieniami.
                                    </li>
                                    <li>
                                        <strong>Inteligentna nawigacja:</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Wiele obszarów → menu wyboru</li>
                                            <li>→ Jeden obszar → automatyczne przekierowanie (oszczędność czasu 😊)</li>
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
                                <h3 class="h5 fw-bold mb-2">Optymalizacja na urządzenia mobilne</h3>
                                <p class="text-muted small">
                                    Skróty są wyświetlane bezpośrednio tutaj — nie trzeba otwierać menu ☰ na małych ekranach.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <h2 class="h4 mb-3 fw-bold">Co warto zapamiętać</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>
                    <strong>Żółty klucz</strong> jest widoczny tylko dla członków z uprawnieniami.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>
                    Jeden obszar administracyjny → automatyczne przekierowanie.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>
                    Na urządzeniach mobilnych: skróty bezpośrednio na tej stronie.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span>
                    <strong>Pomoc kontekstualna:</strong> dostępna w każdym module poprzez ikonę pomocy.
                </span>
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
INSERT INTO "Languages" VALUES (113,'navbar.admin.event_manager','Event manager','Animateur','Koordynator wydarzeń');
INSERT INTO "Languages" VALUES (114,'navbar.admin.designer','Designer','Designer','Projektant');
INSERT INTO "Languages" VALUES (115,'navbar.admin.redactor','Redactor','Rédacteur','Redaktor');
INSERT INTO "Languages" VALUES (116,'navbar.admin.person_manager','Secretary','Secrétaire','Sekretarz');
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
INSERT INTO "Metadata" VALUES (1,'MyClub',26,0,1000000,NULL,10,36,6,NULL,0,NULL);
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
