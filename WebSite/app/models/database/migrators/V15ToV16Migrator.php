<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\helpers\Application;
use app\interfaces\DatabaseMigratorInterface;
use app\models\LanguagesDataHelper;

class V15ToV16Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec('
            CREATE TABLE "MenuItem" (
                "Id"           INTEGER,
                "What"         TEXT NOT NULL CHECK("What" IN (\'navbar\', \'sidebar\')),
                "Type"         TEXT NOT NULL CHECK("Type" IN (\'heading\', \'link\', \'divider\', \'submenu\')),
                "Label"        TEXT,
                "Icon"         TEXT,
                "Url"          TEXT,
                "ParentId"     INTEGER,
                "Position"     INTEGER NOT NULL DEFAULT 1,
                "IdGroup"      INTEGER          DEFAULT NULL,
                "ForMembers"   INTEGER NOT NULL DEFAULT 0,
                "ForContacts"  INTEGER NOT NULL DEFAULT 0,
                "ForAnonymous" INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY("Id"),
                FOREIGN KEY("ParentId") REFERENCES "MenuItem"("Id") ON DELETE CASCADE,
                FOREIGN KEY("IdGroup")  REFERENCES "Group"("Id")
            )
        ');
        $pdo->exec('
            INSERT INTO "MenuItem" ("What", "Type", "Label", "Url", "Position", "IdGroup", "ForMembers", "ForAnonymous", "ForContacts")
            SELECT
                \'navbar\'      AS "What",
                \'link\'        AS "Type",
                "Name"        AS "Label",
                REPLACE("Route", \'/navbar/\', \'/menu/\') AS "Url",
                "Position",
                "IdGroup",
                "ForMembers",
                "ForAnonymous",
                0             AS "ForContacts"
            FROM "Page"
        ');
        $pdo->exec('DROP TABLE "Page"');

        $languagesHelper = new LanguagesDataHelper(Application::init());
        $stmt = $pdo->prepare("SELECT Id FROM Languages WHERE Name = :name");
        $stmt->execute([':name' => 'Designer']);
        $designerId = (int)$stmt->fetchColumn();

        $englishContent = '<div class="alert alert-info mt-2">
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
        </div>';
        $frenchContent = '<div class="alert alert-info mt-2">
            <h5 class="alert-heading">Administration du design</h5>
            <p>Cette zone permet de configurer les éléments visuels et structurels de l’application.</p>
            <p class="mb-0">🎨 Seuls les outils de conception auxquels vous avez accès sont affichés ci-dessous.</p>
        </div>
        <div class="designer-links mt-3 mb-3">
            <ul class="nav nav-pills gap-3">
                {if $isEventDesigner}
                <li class="nav-item"><a class="nav-link" href="/eventTypes">🗓️ Les types d\'événements et leurs attributs</a></li>
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
        </div>';
        $languagesHelper->updateTranslation($designerId, 'en_US', $englishContent);
        $languagesHelper->updateTranslation($designerId, 'fr_FR', $frenchContent);

        $pdo->exec("UPDATE 'Authorization' SET Name = 'MenuDesigner' WHERE Id = 9");
        $pdo->exec("INSERT INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES ('article.error.email_failed', 'Failed to send email to subscribers', 'Échec de l''envoi du courriel aux abonnés', 'Nie można wysłać emaila do subskrybentów')");

        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES

('navbar.designer.event_types',
 'Event types and their attributes',
 'Les types d''événements et leurs attributs',
 'Typy wydarzeń i ich atrybuty'),

('navbar.designer.needs',
 'Needs associated with events',
 'Les besoins associés aux événements',
 'Wymagania związane z wydarzeniami'),

('navbar.designer.settings',
 'Customization',
 'Personnalisation',
 'Personalizacja'),

('navbar.designer.designs',
 'Designs',
 'Les designs',
 'Projekty'),

('navbar.designer.kanban',
 'Kanban board',
 'Kanban',
 'Tablica Kanban'),

('navbar.designer.menu',
 'Navigation menus',
 'Les menus de navigation',
 'Menu nawigacyjne'),

('navbar.designer.translator',
 'Translations',
 'Les traductions',
 'Tłumaczenia'),

 ('navbar.admin.event_manager',
 'Event manager',
 'Animateur',
 'Koordynator wydarzeń'),

('navbar.admin.designer',
 'Designer',
 'Designer',
 'Projektant'),

('navbar.admin.redactor',
 'Redactor',
 'Rédacteur',
 'Redaktor'),

('navbar.admin.person_manager',
 'Secretary',
 'Secrétaire',
 'Sekretarz'),

('navbar.admin.visitor_insights',
 'Observer',
 'Observateur',
 'Obserwator'),

('navbar.admin.webmaster',
 'Webmaster',
 'Webmaster',
 'Webmaster'),

 ('navbar.event_manager.week_events',
 'Weekly calendar',
 'Calendrier hebdomadaire',
 'Kalendarz tygodniowy'),

('navbar.event_manager.next_events',
 'Upcoming events',
 'Les prochains événements',
 'Nadchodzące wydarzenia'),

('navbar.event_manager.guest_invitation',
 'Send an invitation',
 'Envoyer une « invitation »',
 'Wyślij zaproszenie'),

('navbar.event_manager.emails',
 'Email extraction',
 'Extraction des emails',
 'Eksport adresów e-mail'),

('navbar.event_manager.crosstab',
 'Cross-tabulation table',
 'Tableau croisé dynamique',
 'Tabela przestawna'),

 ('navbar.person_manager.persons',
 'Members',
 'Membres',
 'Członkowie'),

('navbar.person_manager.groups',
 'Groups',
 'Groupes',
 'Grupy'),

('navbar.person_manager.registration',
 'Registrations',
 'Inscriptions',
 'Rejestracje'),

('navbar.person_manager.import',
 'Import',
 'Importer',
 'Import'),

 ('navbar.redactor.articles',
 'Articles',
 'Articles',
 'Artykuły'),

('navbar.redactor.media',
 'Media',
 'Médias',
 'Media'),

('navbar.redactor.top_articles',
 'Top 50 articles',
 'Top 50 articles',
 'Top 50 artykułów'),

('navbar.redactor.crosstab',
 'Cross-tabulation table',
 'Tableau croisé dynamique',
 'Tabela przestawna'),

 ('navbar.visitor_insights.referents',
 'Referring sites',
 'Sites référents',
 'Strony odsyłające'),

('navbar.visitor_insights.top_pages',
 'Top pages by period',
 'Top pages par période',
 'Najpopularniejsze strony w okresie'),

('navbar.visitor_insights.crosstab',
 'Cross-tabulation table',
 'Tableau croisé dynamique',
 'Tabela przestawna'),

('navbar.visitor_insights.logs',
 'Visit details',
 'Détails des visites',
 'Szczegóły wizyt'),

('navbar.visitor_insights.visitors',
 'Visitors',
 'Visiteurs',
 'Odwiedzający'),

('navbar.visitor_insights.analytics',
 'Visit summary',
 'Synthèse des visites',
 'Podsumowanie wizyt'),

('navbar.visitor_insights.last_visits',
 'Latest visits',
 'Dernières visites',
 'Ostatnie wizyty'),

('navbar.visitor_insights.members_alerts',
 'Alerts requested by members',
 'Alertes demandées par les membres',
 'Alerty żądane przez członków'),

 ('navbar.webmaster.dbbrowser',
 'Database browser',
 'Navigateur de base de données',
 'Przeglądarka bazy danych'),

('navbar.webmaster.groups',
 'Groups',
 'Groupes',
 'Grupy'),

('navbar.webmaster.registration',
 'Registrations',
 'Inscriptions',
 'Rejestracje'),

('navbar.webmaster.notifications',
 'Notifications',
 'Notifications',
 'Powiadomienia'),

('navbar.webmaster.send_emails',
 'Emails',
 'Courriels',
 'Wiadomości e-mail'),

('navbar.webmaster.maintenance',
 'Maintenance',
 'Maintenance',
 'Konserwacja'),

('navbar.webmaster.installations',
 'Installations',
 'Installations',
 'Instalacje'),

 ('emailCredentials.title',
 'Account to use for sending emails',
 'Compte à utiliser pour envoyer des courriels',
 'Konto do użycia do wysyłania wiadomości e-mail'),

('emailCredentials.email',
 'Email',
 'Email',
 'Email'),

('emailCredentials.password',
 'Password',
 'Mot de passe',
 'Hasło'),

('emailCredentials.host',
 'Host',
 'Hôte',
 'Host'),

('emailCredentials.invalid_email',
 'Please enter a valid email',
 'Veuillez entrer un email valide',
 'Proszę wprowadzić prawidłowy email');
SQL;
        $pdo->exec($sql);

        return 16;
    }
}
