<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V103ToV104Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'

-- Move /routes help texts to their new controllers.
-- Help texts must follow the controller methods after a split.

-- EventController -> EventDetailController
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL)
SELECT
    REPLACE(Name, 'EventController', 'EventDetailController'),
    REPLACE(en_US, 'EventController', 'EventDetailController'),
    REPLACE(fr_FR, 'EventController', 'EventDetailController'),
    REPLACE(pl_PL, 'EventController', 'EventDetailController')
FROM Languages
WHERE Name IN (
    'designer.routes.help.EventController.show',
    'designer.routes.help.EventController.showEventChat'
);

-- EventController -> EventListController
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL)
SELECT
    REPLACE(Name, 'EventController', 'EventListController'),
    REPLACE(en_US, 'EventController', 'EventListController'),
    REPLACE(fr_FR, 'EventController', 'EventListController'),
    REPLACE(pl_PL, 'EventController', 'EventListController')
FROM Languages
WHERE Name IN (
    'designer.routes.help.EventController.nextEvents',
    'designer.routes.help.EventController.nextEventsHelp',
    'designer.routes.help.EventController.weekEvents',
    'designer.routes.help.EventController.weekEventsHelp',
    'designer.routes.help.EventController.events_guest_help'
);

-- EventController -> EventManagerController
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL)
SELECT
    REPLACE(Name, 'EventController', 'EventManagerController'),
    REPLACE(en_US, 'EventController', 'EventManagerController'),
    REPLACE(fr_FR, 'EventController', 'EventManagerController'),
    REPLACE(pl_PL, 'EventController', 'EventManagerController')
FROM Languages
WHERE Name IN (
    'designer.routes.help.EventController.home',
    'designer.routes.help.EventController.help',
    'designer.routes.help.EventController.showEventCrosstab',
    'designer.routes.help.EventController.events_crossTab_help'
);

-- EventController -> EventRegistrationController
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL)
SELECT
    REPLACE(Name, 'EventController', 'EventRegistrationController'),
    REPLACE(en_US, 'EventController', 'EventRegistrationController'),
    REPLACE(fr_FR, 'EventController', 'EventRegistrationController'),
    REPLACE(pl_PL, 'EventController', 'EventRegistrationController')
FROM Languages
WHERE Name IN (
    'designer.routes.help.EventController.registerSet',
    'designer.routes.help.EventController.registerUnset'
);

-- WebmasterController -> CredentialsController
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL)
SELECT
    REPLACE(Name, 'WebmasterController', 'CredentialsController'),
    REPLACE(en_US, 'WebmasterController', 'CredentialsController'),
    REPLACE(fr_FR, 'WebmasterController', 'CredentialsController'),
    REPLACE(pl_PL, 'WebmasterController', 'CredentialsController')
FROM Languages
WHERE Name IN (
    'designer.routes.help.WebmasterController.sendEmailCredentialsEdit',
    'designer.routes.help.WebmasterController.sendEmailCredentialsSave',
    'designer.routes.help.WebmasterController.turnstileCredentialsEdit',
    'designer.routes.help.WebmasterController.turnstileCredentialsSave',
    'designer.routes.help.WebmasterController.helloassoCredentialsEdit',
    'designer.routes.help.WebmasterController.helloassoCredentialsSave'
);

-- Remove obsolete EventController help texts.
DELETE FROM Languages
WHERE Name LIKE 'designer.routes.help.EventController.%';

SQL);

        return 104;
    }
}
