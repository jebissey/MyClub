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
INSERT INTO "Group" VALUES (1,'Webmaster',0,0);
INSERT INTO "GroupAuthorization" VALUES (1,1,1);
INSERT INTO "Languages" VALUES (1,'select_language','Select language','Sélectionner la langue',' ');
INSERT INTO "Languages" VALUES (2,'language','Language','Langue',' ');
INSERT INTO "Languages" VALUES (3,'my_data','My ddata','Mes données',' ');
INSERT INTO "Languages" VALUES (4,'admin_zone','Admin zone','Zone d''administration',' ');
INSERT INTO "Languages" VALUES (5,'logout','Logout','Déconnexion',' ');
INSERT INTO "Languages" VALUES (6,'contextual_help','Contextual help','Aide contextuelle',' ');
INSERT INTO "Languages" VALUES (7,'vote','Vote','Voter',' ');
INSERT INTO "Languages" VALUES (8,'connection_required','(You must be connected)','(Vous devez être connecté)',' ');
INSERT INTO "Languages" VALUES (9,'home','Home','Accueil',' ');
INSERT INTO "Languages" VALUES (10,'created_by','Created by','Créé par',' ');
INSERT INTO "Languages" VALUES (11,'modified_on','modified on','modifié le',' ');
INSERT INTO "Languages" VALUES (12,'on','on','le',' ');
INSERT INTO "Languages" VALUES (13,'eventsAvailableForYou','Your events','Vos événements',' ');
INSERT INTO "Languages" VALUES (14,'eventsAvailableForAll','The events','Les événements',' ');
INSERT INTO "Languages" VALUES (15,'type','Type','Type',' ');
INSERT INTO "Languages" VALUES (16,'summary','Summary','Sommaire',' ');
INSERT INTO "Languages" VALUES (17,'location','Location','Lieu',' ');
INSERT INTO "Languages" VALUES (18,'date_time','Date and time','Date et heure',' ');
INSERT INTO "Languages" VALUES (19,'duration','Duration','Durée',' ');
INSERT INTO "Languages" VALUES (20,'attributes','Attributes','Attribut',' ');
INSERT INTO "Languages" VALUES (21,'description','Description','Description',' ');
INSERT INTO "Languages" VALUES (22,'participants','Participants','Participants',' ');
INSERT INTO "Languages" VALUES (23,'audience','Audience','Audience',' ');
INSERT INTO "Languages" VALUES (24,'ClubMembersOnly','Members','Membres',' ');
INSERT INTO "Languages" VALUES (25,'All','Public','Public',' ');
INSERT INTO "Languages" VALUES (26,'register','Register','S''inscrire',' ');
INSERT INTO "Languages" VALUES (27,'unregister','Unregister','Se désinscrire',' ');
INSERT INTO "Languages" VALUES (28,'fullyBooked','Fully booked','Complet',' ');
INSERT INTO "Languages" VALUES (29,'noAttributes','No attributes','Aucun attribut',' ');
INSERT INTO "Languages" VALUES (30,'noParticipant','No participant at this time','Aucun participant pour le moment',' ');
INSERT INTO "Languages" VALUES (31,'login','Login','Connexion',' ');
INSERT INTO "Languages" VALUES (32,'edit','Edit','Modifier',' ');
INSERT INTO "Languages" VALUES (33,'messages','Messages','Messages',' ');
INSERT INTO "Languages" VALUES (34,'delete','Delete','Supprimer',' ');
INSERT INTO "Languages" VALUES (35,'duplicate','Duplicate','Dupliquer',' ');
INSERT INTO "Languages" VALUES (36,'sendEmail','Send email','Envoyer courriel',' ');
INSERT INTO "Languages" VALUES (37,'news','News','News',' ');
INSERT INTO "Languages" VALUES (38,'directory','Directory','Trombinoscope',' ');
INSERT INTO "Languages" VALUES (39,'statistics','Statistics','Statistiques',' ');
INSERT INTO "Languages" VALUES (40,'preferences','Preferences','Préférences',' ');
INSERT INTO "Languages" VALUES (41,'groups','Groups','Groupes',' ');
INSERT INTO "Languages" VALUES (42,'availabilities','Availabilities','Disponibilités',' ');
INSERT INTO "Languages" VALUES (43,'account','Account','Compte',' ');
INSERT INTO "Languages" VALUES (44,'Guest','Guest','Invité',' ');
INSERT INTO "Languages" VALUES (45,'morning','Morning','Matin',' ');
INSERT INTO "Languages" VALUES (46,'afternoon','Afternoon','Après-midi',' ');
INSERT INTO "Languages" VALUES (47,'evening','Evening','Soir',' ');
INSERT INTO "Languages" VALUES (48,'Message_UnknownUser','Unknown user (email)','Utilisateur inconnu (courriel)',' ');
INSERT INTO "Languages" VALUES (49,'comboSeparatorHome','--- Home ---','--- Accueil ---',' ');
INSERT INTO "Languages" VALUES (50,'comboSeparatorMessages','--- Messages ---','--- Messages ---',' ');
INSERT INTO "Languages" VALUES (51,'comboSeparatorErrorPages','--- Error pages ---','--- Pages d''erreur ---',' ');
INSERT INTO "Languages" VALUES (52,'comboSeparatorHelp','--- Help ---','--- Aides ---',' ');
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
</div>
','<div class="container my-3">
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
</div>',' ');
INSERT INTO "Languages" VALUES (54,'Help_Designer','Designer help','Aide designer',' ');
INSERT INTO "Languages" VALUES (55,'Help_EventManager','Event manager help','Aide gestionnaire d''événements',' ');
INSERT INTO "Languages" VALUES (56,'Help_Home','<div class="container my-5">
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
  <strong><a href="https://myclub.alwaysdata.net/navbar/show/article/28">Tutorials</a></strong> will take you to the 
  <strong><i><u>MyClub</u></i></strong> website. 
  There you will find <strong>videos</strong>, <strong>articles</strong>, 
  a <strong>dictionary</strong>, and other resources to support you.
</div>','<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Aide Contextuelle : MyClub</h1>
        <p class="lead">Simplifiez la gestion de votre vie associative en quelques clics.</p>
    </header>
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Présentation de l''''application</h2>

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
                                    Paramétrez vos types d''''événements favoris et vos disponibilités pour un affichage sur mesure.
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
        <h2 class="h4 mb-4">Ce qu''''il faut retenir</h2>
        <p class="text-muted">
            La navigation se fait principalement via la barre située en haut de l''''écran.
        </p>

        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-secondary me-3">[Logo]</span>
                <span>
                    Situé en haut à gauche, il vous ramène instantanément sur la <strong>page d''''accueil</strong>.
                </span>
            </div>

            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-list fs-3 me-3"></i>
                <span>
                    <strong>Menu Burger :</strong> Sur mobile, en haut à droite, il permet d''''afficher les options de navigation masquées.
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
                    <strong>Aide :</strong> C''''est ici que vous trouverez toutes les informations pour naviguer sur MyClub.
                </span>
            </div>
        </div>
    </section>
</div>
<div class="mt-4 text-center text-muted">
En pied de page 
  <strong><a href="https://myclub.alwaysdata.net/navbar/show/article/28">Tutoriels</a></strong> vous conduit vers le site de 
  <strong><i><u>MyClub</u></i></strong>. 
  Vous y trouverez des <strong>vidéos</strong>, des <strong>articles</strong>, 
  un <strong>dictionnaire</strong> et d’autres ressources pour vous accompagner.
</div>',' ');
INSERT INTO "Languages" VALUES (57,'Help_PersonManager','People manager help','Aide gestionnaire de personnes',' ');
INSERT INTO "Languages" VALUES (58,'Help_Redactor','Redactor help','Aide rédateur',' ');
INSERT INTO "Languages" VALUES (59,'Help_User','User help','Aide utilisateur',' ');
INSERT INTO "Languages" VALUES (60,'Help_VisitorInsights','Visitor insights help','Aide statistiques visiteurs',' ');
INSERT INTO "Languages" VALUES (61,'Help_Webmaster','Webmater help','Aide webmaster',' ');
INSERT INTO "Languages" VALUES (62,'Home_Header','Home header','En-tête d''accueil',' ');
INSERT INTO "Languages" VALUES (63,'Home_Footer','Home footer','Pied de page d''accueil',' ');
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
</div>',' ');
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
</div>',' ');
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
</div>',' ');
INSERT INTO "Languages" VALUES (67,'LoginRequired','<div class="alert alert-warning" role="alert">
  <p>🔒 <strong>Oops… this resource is reserved for logged-in members!</strong></p>
  <p>You need to log in to access it.</p>
  <p>💡 By choosing the "Remember me" option, your browser will roll out the 🟥red carpet🟥 next time, no password required.</p>
</div>','<div class="alert alert-warning" role="alert">
  <!-- Version française -->
  <p>🔒 <strong>Oups… cette ressource est réservée aux membres connectés !</strong></p>
  <p>Il faut se connecter pour la découvrir.</p>
  <p>💡 Avec l''option « Se souvenir de moi », ton navigateur te déroulera le 🟥tapis rouge🟥 la prochaine fois, sans passer par la case mot de passe.</p>
</div>',' ');
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
</style>',' ');
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
</div>',' ');
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
</div>',' ');
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
</div>',' ');
INSERT INTO "Languages" VALUES (72,'connections','Connections','Connexions',' ');
INSERT INTO "Languages" VALUES (73,'ErrorLyricsFileNotFound','<div class=''''container text-center mt-5''''>
                    <div class=''''card shadow-lg rounded-3 p-4''''>
                        <h1 class=''''text-danger''''>📁 Lyrics File Not Found</h1>
                        <p class=''''mt-3''''>
                        The lyrics file could not be found.<br>
                        Please make sure the song exists and that its lyrics file (<code>.lrc</code>) is correctly named.
                        </p>
                        <ul class=''''text-start mx-auto d-inline-block mt-3''''>
                        <li>➡️ The file name might not match the song name.</li>
                        <li>➡️ The file might have been moved or deleted.</li>
                        </ul>
                        <a href=''/'' class=''''btn btn-primary mt-4''''>🏠 Back to homepage</a>
                    </div>
                </div>','<div class=''''container text-center mt-5''''>
                    <div class=''''card shadow-lg rounded-3 p-4''''>
                        <h1 class=''''text-danger''''>📁 Fichier de paroles introuvable</h1>
                        <p class=''''mt-3''''>
                        Le fichier de paroles est introuvable.<br>
                        Vérifie que la chanson existe et que son fichier <code>.lrc</code> porte bien le même nom.
                        </p>
                        <ul class=''''text-start mx-auto d-inline-block mt-3''''>
                        <li>➡️ Le nom du fichier ne correspond pas à celui de la chanson.</li>
                        <li>➡️ Le fichier a été déplacé ou supprimé.</li>
                        </ul>
                        <a href=''/'' class=''''btn btn-primary mt-4''''>🏠 Retour à l’accueil</a>
                    </div>
                </div>',' ');
INSERT INTO "Languages" VALUES (74,'ErrorLyricsFileNotReadable','<div class=''''container text-center mt-5''''>
                    <div class=''''card shadow-lg rounded-3 p-4''''>
                        <h1 class=''''text-danger''''>🔒 Lyrics File Not Readable</h1>
                        <p class=''''mt-3''''>
                        The lyrics file exists but cannot be read.<br>
                        Please check file permissions or contact the administrator.
                        </p>
                        <ul class=''''text-start mx-auto d-inline-block mt-3''''>
                        <li>➡️ The file might not have proper read permissions.</li>
                        <li>➡️ The file might be locked or corrupted.</li>
                        </ul>
                        <a href=''/'' class=''''btn btn-primary mt-4''''>🏠 Back to homepage</a>
                    </div>
                </div>','''<div class=''''container text-center mt-5''''>
                    <div class=''''card shadow-lg rounded-3 p-4''''>
                        <h1 class=''''text-danger''''>🔒 Fichier de paroles non lisible</h1>
                        <p class=''''mt-3''''>
                        Le fichier de paroles existe mais n’a pas pu être lu.<br>
                        Vérifie les permissions du fichier ou contacte l’administrateur.
                        </p>
                        <ul class=''''text-start mx-auto d-inline-block mt-3''''>
                        <li>➡️ Le fichier n’a peut-être pas les droits de lecture suffisants.</li>
                        <li>➡️ Le fichier est peut-être verrouillé ou corrompu.</li>
                        </ul>
                        <a href=''/'' class=''''btn btn-primary mt-4''''>🏠 Retour à l’accueil</a>
                    </div>
                </div>',' ');
INSERT INTO "Languages" VALUES (75,'ErrorLyricsFileReadError','''<div class=''''container text-center mt-5''''>
                    <div class=''''card shadow-lg rounded-3 p-4''''>
                        <h1 class=''''text-danger''''>⚠️ Lyrics File Reading Error</h1>
                        <p class=''''mt-3''''>
                        We encountered an unexpected error while reading the lyrics file.<br>
                        Please verify the file content or try again later.
                        </p>
                        <ul class=''''text-start mx-auto d-inline-block mt-3''''>
                        <li>➡️ The file might be corrupted.</li>
                        <li>➡️ The server encountered a temporary I/O error.</li>
                        </ul>
                        <a href=''/'' class=''''btn btn-primary mt-4''''>🏠 Back to homepage</a>
                    </div>
                </div>','<div class=''''container text-center mt-5''''>
                    <div class=''''card shadow-lg rounded-3 p-4''''>
                        <h1 class=''''text-danger''''>⚠️ Erreur de lecture du fichier de paroles</h1>
                        <p class=''''mt-3''''>
                        Une erreur est survenue lors de la lecture du fichier de paroles.<br>
                        Vérifie le contenu du fichier ou réessaie plus tard.
                        </p>
                        <ul class=''''text-start mx-auto d-inline-block mt-3''''>
                        <li>➡️ Le fichier est peut-être corrompu.</li>
                        <li>➡️ Le serveur a rencontré une erreur d’accès disque temporaire.</li>
                        </ul>
                        <a href=''/'' class=''''btn btn-primary mt-4''''>🏠 Retour à l’accueil</a>
                    </div>
                </div>',' ');
INSERT INTO "Languages" VALUES (76,'save','Save','Enregistrer',' ');
INSERT INTO "Languages" VALUES (77,'cancel','Cancel','Annuler',' ');
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
</div>',' ');
INSERT INTO "Languages" VALUES (79,'notepad','Notepad','Bloc-notes',' ');
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
            <li class="nav-item">
                <a class="nav-link" href="/eventManager">🗓️ Event management</a>
            </li>
            {/if}

            {if $isDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/designer">🎨 Design</a>
            </li>
            {/if}

            {if $isRedactor}
            <li class="nav-item">
                <a class="nav-link" href="/redactor">✍️ Content writing</a>
            </li>
            {/if}

            {if $isPersonManager}
            <li class="nav-item">
                <a class="nav-link" href="/personManager">📇 Member management</a>
            </li>
            {/if}

            {if $isVisitorInsights}
            <li class="nav-item">
                <a class="nav-link" href="/visitorInsights">🔍 Visitor insights</a>
            </li>
            {/if}

            {if $isWebmaster}
            <li class="nav-item">
                <a class="nav-link" href="/webmaster">🛠️ Website administration</a>
            </li>
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
            <li class="nav-item">
                <a class="nav-link" href="/eventManager">🗓️ Gestion des événements</a>
            </li>
            {/if}

            {if $isDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/designer">🎨 Design</a>
            </li>
            {/if}

            {if $isRedactor}
            <li class="nav-item">
                <a class="nav-link" href="/redactor">✍️ Rédaction de contenu</a>
            </li>
            {/if}

            {if $isPersonManager}
            <li class="nav-item">
                <a class="nav-link" href="/personManager">📇 Gestion des membres</a>
            </li>
            {/if}

            {if $isVisitorInsights}
            <li class="nav-item">
                <a class="nav-link" href="/visitorInsights">🔍 Analyse des visiteurs</a>
            </li>
            {/if}

            {if $isWebmaster}
            <li class="nav-item">
                <a class="nav-link" href="/webmaster">🛠️ Administration du site</a>
            </li>
            {/if}

        </ul>
    </div>',' ');
INSERT INTO "Languages" VALUES (81,'Designer','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Design administration</h5>
        <p>
            This area allows you to configure the visual and structural elements of the application.
        </p>
        <p class="mb-0">
            🎨 Only the design tools you are authorized to use are displayed below.
        </p>
    </div>

    <div class="designer-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            {if $isEventDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/eventTypes">🗓️ Event types and attributes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/needs">📋 Event-related needs</a>
            </li>
            {/if}

            {if $isHomeDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/settings">🔧 Customization</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/designs">🧠 Designs</a>
            </li>
            {/if}

            {if $isKanbanDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/kanban">🟨 Kanban</a>
            </li>
            {/if}

            {if $isNavbarDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/navbar">📑 Navigation bars</a>
            </li>
            {/if}

        </ul>
    </div>','<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Administration du design</h5>
        <p>
            Cette zone permet de configurer les éléments visuels et structurels de l’application.
        </p>
        <p class="mb-0">
            🎨 Seuls les outils de conception auxquels vous avez accès sont affichés ci-dessous.
        </p>
    </div>

    <div class="designer-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            {if $isEventDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/eventTypes">🗓️ Les types d''événements et leurs attributs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/needs">📋 Les besoins associés aux événements</a>
            </li>
            {/if}

            {if $isHomeDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/settings">🔧 Personnalisation</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/designs">🧠 Les designs</a>
            </li>
            {/if}

            {if $isKanbanDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/kanban">🟨 Kanban</a>
            </li>
            {/if}

            {if $isNavbarDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/navbar">📑 Les barres de navigations</a>
            </li>
            {/if}
        </ul>
    </div>',' ');
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

            <li class="nav-item">
                <a class="nav-link" href="/weekEvents">🗓️ Weekly calendar</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/nextEvents">📅 Upcoming events</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/events/guest">📩 Send an invitation</a>
            </li>

            <li class="nav-item"
                <a class="nav-link" href="/emails">📧 Get emails</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/events/crossTab">🧮 Pivot table</a>
            </li>

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

            <li class="nav-item">
                <a class="nav-link" href="/weekEvents">🗓️ Calendrier hebdomadaire</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/nextEvents">📅 Prochains événements</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/events/guest">📩 Envoyer une invitation</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/emails">📧 Get emails</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/events/crossTab">🧮 Tableau croisé dynamique</a>
            </li>

        </ul>
    </div>',' ');
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

            <li class="nav-item">
                <a class="nav-link" href="/articles">📰 Articles</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/media/list">📂 Media</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/topArticles">📈 Top 50</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/articles/crossTab">🧮 Pivot table</a>
            </li>

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

            <li class="nav-item" title="Articles">
                <a class="nav-link" href="/articles">📰 Articles</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/media/list">📂 Média</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/topArticles">📈 Top 50</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/articles/crossTab">🧮 Tableau croisé dynamique</a>
            </li>

        </ul>
    </div>',' ');
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

            <li class="nav-item">
                <a class="nav-link" href="/persons">🎭 Members</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/groups">👫 Groups</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/registration">🎟️ Registrations</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/import">📥 Import</a>
            </li>

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

            <li class="nav-item">
                <a class="nav-link" href="/persons">🎭 Membres</a>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/groups">👫 Groupes</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/registration">🎟️ Inscriptions</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/import">📥 Importer</a>
            </li>
        </ul>
    </div>',' ');
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

            <li class="nav-item">
                <a class="nav-link" href="/referents">☁️ Referring sites</a>
            </li>

            <li class="nav-item" title="Top pages by period">
                <a class="nav-link" href="/topPages">📈 Top pages</a>
            </li>

            <li class="nav-item" title="Pivot table">
                <a class="nav-link" href="/crossTab">🧮 Tableau croisé dynamique</a>
            </li>

            <li class="nav-item" title="Visitors">
                <a class="nav-link" href="/logs">📊 Visitors</a>
            </li>

            <li class="nav-item" title="Last visits">
                <a class="nav-link" href="/lastVisits">👁️ Last visits</a>
            </li>

            <li class="nav-item" title="Member requested alerts">
                <a class="nav-link" href="/membersAlerts">📩 Member requested alerts</a>
            </li>

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

            <li class="nav-item" title="Sites référents">
                <a class="nav-link" href="/referents"><h2>☁️</h2></a>
            </li>

            <li class="nav-item" title="Top pages par période">
                <a class="nav-link" href="/topPages"><h2>📈</h2></a>
            </li>

            <li class="nav-item" title="Tableau croisé dynamique">
                <a class="nav-link" href="/crossTab"><h2>🧮</h2></a>
            </li>

            <li class="nav-item" title="Visiteurs">
                <a class="nav-link" href="/logs"><h2>📊</h2></a>
            </li>

            <li class="nav-item" title="Dernières visites">
                <a class="nav-link" href="/lastVisits"><h2>👁️</h2></a>
            </li>

            <li class="nav-item" title="Alertes demandées par les membres">
                <a class="nav-link" href="/membersAlerts"><h2>📩</h2></a>
            </li>

        </ul>
    </div>',' ');
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
    </div>
','<div class="alert alert-info mt-2">
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
    </div>
',' ');
INSERT INTO "Languages" VALUES (87,'article.error.not_found','Article {id} does not exist','L''article {id} n''existe pas','');
INSERT INTO "Languages" VALUES (88,'article.error.unknown_author','Unknown author for article {id}','Auteur inconnu pour l''article {id}','');
INSERT INTO "Languages" VALUES (89,'article.error.login_required','You must be logged in to view this article','Il faut être connecté pour pouvoir consulter cet article','');
INSERT INTO "Languages" VALUES (90,'article.error.update_failed','An error occurred while updating the article','Une erreur est survenue lors de la mise à jour de l''article','');
INSERT INTO "Languages" VALUES (91,'article.error.title_content_required','Title and content are required','Le titre et le contenu sont obligatoires','');
INSERT INTO "Languages" VALUES (92,'article.success.updated','Article successfully updated','L''article a été mis à jour avec succès','');
INSERT INTO "Languages" VALUES (93,'article.success.email_sent','Email sent to subscribers','Un courriel a été envoyé aux abonnés','');
INSERT INTO "Languages" VALUES (94,'article.email.new_title','A new article is available on {root}','Un nouvel article est disponible sur le site {root}','');
INSERT INTO "Languages" VALUES (95,'article.email.body_intro','According to your preferences, this message informs you about a new article','Conformément à vos souhaits, ce message vous signale la présence d''un nouvel article','');
INSERT INTO "Languages" VALUES (96,'article.email.unsubscribe','To stop receiving these emails update your preferences','Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences','');
INSERT INTO "Languages" VALUES (97,'article.title.crosstab','Redactors vs audience','Rédacteurs vs audience','');
INSERT INTO "Languages" VALUES (98,'article.label.created_by','Created by','Créé par','');
INSERT INTO "Languages" VALUES (99,'article.label.title','Title','Titre','');
INSERT INTO "Languages" VALUES (100,'article.label.last_update','Last update','Dernière modification','');
INSERT INTO "Languages" VALUES (101,'article.label.group','Group','Groupe','');
INSERT INTO "Languages" VALUES (102,'article.label.published','Published','Publié','');
INSERT INTO "Languages" VALUES (103,'article.label.pool','Survey','Sondage','');
INSERT INTO "Languages" VALUES (104,'article.label.content','Content','Contenu','');
INSERT INTO "Languages" VALUES (105,'article.error.email_failed','Failed to send email to subscribers','Échec de l''envoi du courriel aux abonnés','Nie można wysłać emaila do subskrybentów');
INSERT INTO "Languages" VALUES (106,'navbar.designer.event_types','Event types and their attributes','Les types d''événements et leurs attributs','Typy wydarzeń i ich atrybuty');
INSERT INTO "Languages" VALUES (107,'navbar.designer.needs','Needs associated with events','Les besoins associés aux événements','Wymagania związane z wydarzeniami');
INSERT INTO "Languages" VALUES (108,'navbar.designer.settings','Customization','Personnalisation','Personalizacja');
INSERT INTO "Languages" VALUES (109,'navbar.designer.designs','Designs','Les designs','Projekty');
INSERT INTO "Languages" VALUES (110,'navbar.designer.kanban','Kanban board','Kanban','Tablica Kanban');
INSERT INTO "Languages" VALUES (111,'navbar.designer.menu','Navigation menus','Les menus de navigation','Menu nawigacyjne');
INSERT INTO "Languages" VALUES (112,'navbar.designer.translator','Translations','Les traductions','Tłumaczenia');
INSERT INTO "Languages" VALUES (120,'navbar.admin.event_manager','Event manager','Animateur','Koordynator wydarzeń');
INSERT INTO "Languages" VALUES (121,'navbar.admin.designer','Designer','Designer','Projektant');
INSERT INTO "Languages" VALUES (122,'navbar.admin.redactor','Redactor','Rédacteur','Redaktor');
INSERT INTO "Languages" VALUES (123,'navbar.admin.person_manager','Secretary','Secrétaire','Sekretarz');
INSERT INTO "Languages" VALUES (124,'navbar.admin.visitor_insights','Observer','Observateur','Obserwator');
INSERT INTO "Languages" VALUES (125,'navbar.admin.webmaster','Webmaster','Webmaster','Webmaster');
INSERT INTO "Languages" VALUES (139,'navbar.event_manager.week_events','Weekly calendar','Calendrier hebdomadaire','Kalendarz tygodniowy');
INSERT INTO "Languages" VALUES (140,'navbar.event_manager.next_events','Upcoming events','Les prochains événements','Nadchodzące wydarzenia');
INSERT INTO "Languages" VALUES (141,'navbar.event_manager.guest_invitation','Send an invitation','Envoyer une « invitation »','Wyślij zaproszenie');
INSERT INTO "Languages" VALUES (142,'navbar.event_manager.emails','Email extraction','Extraction des emails','Eksport adresów e-mail');
INSERT INTO "Languages" VALUES (143,'navbar.event_manager.crosstab','Cross-tabulation table','Tableau croisé dynamique','Tabela przestawna');
INSERT INTO "Languages" VALUES (144,'navbar.person_manager.persons','Members','Membres','Członkowie');
INSERT INTO "Languages" VALUES (145,'navbar.person_manager.groups','Groups','Groupes','Grupy');
INSERT INTO "Languages" VALUES (146,'navbar.person_manager.registration','Registrations','Inscriptions','Rejestracje');
INSERT INTO "Languages" VALUES (147,'navbar.person_manager.import','Import','Importer','Import');
INSERT INTO "Languages" VALUES (148,'navbar.redactor.articles','Articles','Articles','Artykuły');
INSERT INTO "Languages" VALUES (149,'navbar.redactor.media','Media','Médias','Media');
INSERT INTO "Languages" VALUES (150,'navbar.redactor.top_articles','Top 50 articles','Top 50 articles','Top 50 artykułów');
INSERT INTO "Languages" VALUES (151,'navbar.redactor.crosstab','Cross-tabulation table','Tableau croisé dynamique','Tabela przestawna');
INSERT INTO "Languages" VALUES (152,'navbar.visitor_insights.referents','Referring sites','Sites référents','Strony odsyłające');
INSERT INTO "Languages" VALUES (153,'navbar.visitor_insights.top_pages','Top pages by period','Top pages par période','Najpopularniejsze strony w okresie');
INSERT INTO "Languages" VALUES (154,'navbar.visitor_insights.crosstab','Cross-tabulation table','Tableau croisé dynamique','Tabela przestawna');
INSERT INTO "Languages" VALUES (155,'navbar.visitor_insights.logs','Visit details','Détails des visites','Szczegóły wizyt');
INSERT INTO "Languages" VALUES (156,'navbar.visitor_insights.visitors','Visitors','Visiteurs','Odwiedzający');
INSERT INTO "Languages" VALUES (157,'navbar.visitor_insights.analytics','Visit summary','Synthèse des visites','Podsumowanie wizyt');
INSERT INTO "Languages" VALUES (158,'navbar.visitor_insights.last_visits','Latest visits','Dernières visites','Ostatnie wizyty');
INSERT INTO "Languages" VALUES (159,'navbar.visitor_insights.members_alerts','Alerts requested by members','Alertes demandées par les membres','Alerty żądane przez członków');
INSERT INTO "Languages" VALUES (160,'navbar.webmaster.dbbrowser','Database browser','Navigateur de base de données','Przeglądarka bazy danych');
INSERT INTO "Languages" VALUES (161,'navbar.webmaster.groups','Groups','Groupes','Grupy');
INSERT INTO "Languages" VALUES (162,'navbar.webmaster.registration','Registrations','Inscriptions','Rejestracje');
INSERT INTO "Languages" VALUES (163,'navbar.webmaster.notifications','Notifications','Notifications','Powiadomienia');
INSERT INTO "Languages" VALUES (164,'navbar.webmaster.send_emails','Emails','Courriels','Wiadomości e-mail');
INSERT INTO "Languages" VALUES (165,'navbar.webmaster.maintenance','Maintenance','Maintenance','Konserwacja');
INSERT INTO "Languages" VALUES (166,'navbar.webmaster.installations','Installations','Installations','Instalacje');
INSERT INTO "Languages" VALUES (167,'emailCredentials.title','Account to use for sending emails','Compte à utiliser pour envoyer des courriels','Konto do użycia do wysyłania wiadomości e-mail');
INSERT INTO "Languages" VALUES (168,'emailCredentials.email','Email','Email','Email');
INSERT INTO "Languages" VALUES (169,'emailCredentials.password','Password','Mot de passe','Hasło');
INSERT INTO "Languages" VALUES (170,'emailCredentials.host','Host','Hôte','Host');
INSERT INTO "Languages" VALUES (171,'emailCredentials.invalid_email','Please enter a valid email','Veuillez entrer un email valide','Proszę wprowadzić prawidłowy email');
INSERT INTO "Languages" VALUES (172,'emailCredentials.method','Sending method','Méthode d''envoi','Metoda wysyłki');
INSERT INTO "Languages" VALUES (173,'emailCredentials.method_mail','Native PHP mail()','PHP mail() natif','Natywny mail() PHP');
INSERT INTO "Languages" VALUES (174,'emailCredentials.method_smtp','SMTP (PHPMailer)','SMTP (PHPMailer)','SMTP (PHPMailer)');
INSERT INTO "Languages" VALUES (175,'emailCredentials.method_mailjet','Mailjet API','Mailjet API','Mailjet API');
INSERT INTO "Languages" VALUES (176,'emailCredentials.info_mail','Emails sent via mail() may end up in spam or be rejected by some domains (Gmail, Outlook…). Recommended for testing only.','Les courriels envoyés via mail() risquent d''arriver en spam ou d''être rejetés par certains domaines (Gmail, Outlook…). Recommandé uniquement pour les tests.','Wiadomości wysyłane przez mail() mogą trafiać do spamu lub być odrzucane przez niektóre domeny (Gmail, Outlook…). Zalecane wyłącznie do testów.');
INSERT INTO "Languages" VALUES (177,'emailCredentials.info_smtp','Sending limits depend on your SMTP provider (e.g. Gmail: 500/day, OVH: 200/hour). Please check your plan.','Les limites d''envoi dépendent de votre fournisseur SMTP (ex. Gmail : 500/jour, OVH : 200/heure). Vérifiez votre offre.','Limity wysyłki zależą od dostawcy SMTP (np. Gmail: 500/dzień, OVH: 200/godzinę). Sprawdź swój plan.');
INSERT INTO "Languages" VALUES (178,'emailCredentials.info_mailjet','Mailjet free plan: 200 emails/day and 6,000/month. A paid subscription is required beyond these limits.','Plan gratuit Mailjet : 200 e-mails/jour et 6 000/mois. Au-delà, un abonnement payant est nécessaire.','Darmowy plan Mailjet: 200 e-maili/dzień i 6 000/miesiąc. Powyżej tych limitów wymagana jest płatna subskrypcja.');
INSERT INTO "Languages" VALUES (179,'emailCredentials.port','SMTP port','Port SMTP','Port SMTP');
INSERT INTO "Languages" VALUES (180,'emailCredentials.encryption','Encryption','Chiffrement','Szyfrowanie');
INSERT INTO "Languages" VALUES (181,'emailCredentials.no_encryption','None','Aucun','Brak');
INSERT INTO "Languages" VALUES (182,'emailCredentials.mailjet_api_key','Mailjet API key','Clé API Mailjet','Klucz API Mailjet');
INSERT INTO "Languages" VALUES (183,'emailCredentials.mailjet_api_secret','Mailjet API secret','Secret API Mailjet','Sekret API Mailjet');
INSERT INTO "Languages" VALUES (184,'emailCredentials.mailjet_sender','Verified sender address','Adresse expéditeur vérifiée','Zweryfikowany adres nadawcy');
INSERT INTO "Languages" VALUES (185,'emailCredentials.daily_limit','Daily sending limit (0 = unlimited)','Limite d''envoi quotidienne (0 = illimitée)','Dzienny limit wysyłki (0 = bez limitu)');
INSERT INTO "Languages" VALUES (186,'emailCredentials.monthly_limit','Monthly sending limit (0 = unlimited)','Limite d''envoi mensuelle (0 = illimitée)','Miesięczny limit wysyłki (0 = bez limitu)');
INSERT INTO "Languages" VALUES (187,'emailCredentials.limits_hint','Leave blank or set 0 for no limit.','Laisser vide ou mettre 0 pour ne pas limiter.','Pozostaw puste lub wpisz 0, aby nie ograniczać.');
INSERT INTO "Metadata" VALUES (1,'MyClub',20,0,1000000,NULL,10,36,6,NULL,0,NULL);
INSERT INTO "Person" VALUES (1,'webmaster@myclub.foo','e427c26faca947919b18b797bc143a35100e4de48c34b70b26202d3a7d8e51f7','my first name','my last name','my nick name or nothing',NULL,'0',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2025-01-01',0,0,0,NULL,NULL,NULL,NULL,NULL,'');
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
