<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V9ToV10Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {

        $pdo->exec(<<<'SQL'
INSERT INTO Languages (Name, en_US, fr_FR)
VALUES (
    'Admin',
    '<div class="alert alert-warning mt-2">
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
    </div>',
    '<div class="alert alert-warning mt-2">
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
    </div>'
),
(
    'Designer',
    '<div class="alert alert-info mt-2">
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

            {if $isNavbarDesigner}
            <li class="nav-item"><a class="nav-link" href="/navbar">📑 Navigation bars</a></li>
            {/if}

        </ul>
    </div>',
    '<div class="alert alert-info mt-2">
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

            {if $isNavbarDesigner}
            <li class="nav-item"><a class="nav-link" href="/navbar">📑 Les barres de navigations</a></li>
            {/if}
        </ul>
    </div>'
),
(
    'EventManager',
    '<div class="alert alert-info mt-2">
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
    </div>',
    '<div class="alert alert-info mt-2">
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
    </div>'
),
(
    'Redactor',
    '<div class="alert alert-info mt-2">
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
    </div>',
    '<div class="alert alert-info mt-2">
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
    </div>'
),
(
    'PersonManager',
    '<div class="alert alert-info mt-2">
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
    </div>',
    '<div class="alert alert-info mt-2">
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
    </div>'
),
(
    'VisitorInsights',
    '<div class="alert alert-info mt-2">
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
    </div>',
    '<div class="alert alert-info mt-2">
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
    </div>'
),
(
    'Webmaster',
    '<div class="alert alert-info mt-2">
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
    </div>',
    '<div class="alert alert-info mt-2">
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
    </div>'
);
SQL);

        return 10;
    }
}
