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
	PRIMARY KEY("Id")
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
	"VapidPublicKey"	TEXT,
	"VapidPrivateKey"	TEXT,
	"SendEmailAddress"	TEXT,
	"SendEmailPassword"	TEXT,
	"SendMailHost"	TEXT,
	"Compact_maxRecords"	INTEGER NOT NULL DEFAULT 1000000,
	"Compact_lastDate"	TEXT,
	"Compact_everyXdays"	INTEGER NOT NULL DEFAULT 10,
	"Compact_removeOlderThanXmonths"	INTEGER NOT NULL DEFAULT 36,
	"Compact_compactOlderThanXmonths"	INTEGER NOT NULL DEFAULT 6,
	"ThisIsProdSiteUrl"	TEXT,
	"ThisIsTestSite"	INTEGER NOT NULL DEFAULT 0,
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
	"ForMembers"	INTEGER NOT NULL DEFAULT 1,
	"ForAnonymous"	INTEGER NOT NULL DEFAULT 0,
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
	"Notifications"	TEXT,
	"Imported"	INTEGER NOT NULL DEFAULT 0,
	"Inactivated"	INTEGER NOT NULL DEFAULT 0,
	"Phone"	TEXT,
	"Presentation"	TEXT,
	"PresentationLastUpdate"	TEXT NOT NULL DEFAULT current_timestamp,
	"InPresentationDirectory"	INTEGER NOT NULL DEFAULT 0,
	"Location"	TEXT,
	"LastSignIn"	TEXT,
	"LastSignOut"	TEXT,
	"Notepad"	TEXT,
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
INSERT INTO "Authorization" VALUES (9,'NavbarDesigner');
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
INSERT INTO "Languages" VALUES (33,'messages','Messages','Messages');
INSERT INTO "Languages" VALUES (34,'delete','Delete','Supprimer');
INSERT INTO "Languages" VALUES (35,'duplicate','Duplicate','Dupliquer');
INSERT INTO "Languages" VALUES (36,'sendEmail','Send email','Envoyer courriel');
INSERT INTO "Languages" VALUES (37,'news','News','News');
INSERT INTO "Languages" VALUES (38,'directory','Directory','Trombinoscope');
INSERT INTO "Languages" VALUES (39,'statistics','Statistics','Statistiques');
INSERT INTO "Languages" VALUES (40,'preferences','Preferences','Préférences');
INSERT INTO "Languages" VALUES (41,'groups','Groups','Groupes');
INSERT INTO "Languages" VALUES (42,'availabilities','Availabilities','Disponibilités');
INSERT INTO "Languages" VALUES (43,'account','Account','Compte');
INSERT INTO "Languages" VALUES (44,'Guest','Guest','Invité');
INSERT INTO "Languages" VALUES (45,'morning','Morning','Matin');
INSERT INTO "Languages" VALUES (46,'afternoon','Afternoon','Après-midi');
INSERT INTO "Languages" VALUES (47,'evening','Evening','Soir');
INSERT INTO "Languages" VALUES (48,'Message_UnknownUser','Unknown user (email)','Utilisateur inconnu (courriel)');
INSERT INTO "Languages" VALUES (49,'comboSeparatorHome','--- Home ---','--- Accueil ---');
INSERT INTO "Languages" VALUES (50,'comboSeparatorMessages','--- Messages ---','--- Messages ---');
INSERT INTO "Languages" VALUES (51,'comboSeparatorErrorPages','--- Error pages ---','--- Pages d''erreur ---');
INSERT INTO "Languages" VALUES (52,'comboSeparatorHelp','--- Help ---','--- Aides ---');
INSERT INTO "Languages" VALUES (53,'Help_Admin','Administratror help','Aide administrateur');
INSERT INTO "Languages" VALUES (54,'Help_designer','Designer help','Aide designer');
INSERT INTO "Languages" VALUES (55,'Help_eventManager','Event manager help','Aide gestionnaire d''événements');
INSERT INTO "Languages" VALUES (56,'Help_home','Home help','Aide accueil');
INSERT INTO "Languages" VALUES (57,'Help_personManager','People manager help','Aide gestionnaire de personnes');
INSERT INTO "Languages" VALUES (58,'Help_redactor','Redactor help','Aide rédateur');
INSERT INTO "Languages" VALUES (59,'Help_user','User help','Aide utilisateur');
INSERT INTO "Languages" VALUES (60,'Help_visitorInsights','Visitor insights help','Aide statistiques visiteurs');
INSERT INTO "Languages" VALUES (61,'Help_webmaster','Webmater help','Aide webmaster');
INSERT INTO "Languages" VALUES (62,'Home_header','Home header','En-tête d''accueil');
INSERT INTO "Languages" VALUES (63,'Home_footer','Home footer','Pied de page d''accueil');
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
</div>');
INSERT INTO "Languages" VALUES (72,'connections','Connections','Connexions');
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
                </div>');
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
                </div>');
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
                </div>');
INSERT INTO "Metadata" VALUES (1,'MyClub',6,0,NULL,NULL,NULL,NULL,NULL,1000000,NULL,10,36,6,NULL,0);
INSERT INTO "Person" VALUES (1,'webmaster@myclub.foo','e427c26faca947919b18b797bc143a35100e4de48c34b70b26202d3a7d8e51f7','my first name','my last name','my nick name or nothing',NULL,'0',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'2025-01-01',0,NULL,NULL,NULL,NULL);
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
