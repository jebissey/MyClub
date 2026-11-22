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

-- Remove obsolete WebmasterController help texts (the credentials actions moved to CredentialsController).
DELETE FROM Languages
WHERE Name IN (
    'designer.routes.help.WebmasterController.sendEmailCredentialsEdit',
    'designer.routes.help.WebmasterController.sendEmailCredentialsSave',
    'designer.routes.help.WebmasterController.turnstileCredentialsEdit',
    'designer.routes.help.WebmasterController.turnstileCredentialsSave',
    'designer.routes.help.WebmasterController.helloassoCredentialsEdit',
    'designer.routes.help.WebmasterController.helloassoCredentialsSave'
);

-- Remove the help texts of routes that no longer exist
DELETE FROM Languages
WHERE Name IN (
    'designer.routes.help.MediaController.isShared',
    'designer.routes.help.WebmasterApi.getVapidPublicKey',
    'designer.routes.help.WebmasterApi.notifySubscribe'
);

-- Help texts of routes that had none (Designer help pages, Exercise, Solfege).
WITH
tpl(t) AS (
    SELECT '<div class="container mt-4" style="max-width: 720px;">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
      <i class="bi bi-info-circle fs-4"></i>
      <span class="fw-semibold">{TITLE}</span>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info text-dark">{TYPE}</span>
        <span class="badge bg-secondary">{AUTH}</span>
        <span class="badge bg-success">{KIND}</span>
      </div>
      <h6 class="text-muted text-uppercase small mb-2">Description</h6>
      <p class="mb-0">{DESC}</p>
    </div>
  </div>
</div>'
),
auth(Code, en, fr, pl) AS (VALUES
    ('USER',     'depends on the connected user',           'dépend de l''utilisateur connecté', 'zależy od zalogowanego użytkownika'),
    ('ENDPOINT', 'depends on the endpoint (business role)', 'selon l''endpoint (rôle métier)',   'zależy od endpointu (rola biznesowa)'),
    ('ANYBODY',  'none (isAnybody)',                        'aucune (isAnybody)',                'brak (isAnybody)')
),
kind(Code, en, fr, pl) AS (VALUES
    ('R', 'read-only', 'lecture seule', 'tylko odczyt'),
    ('U', 'update',    'mise à jour',   'aktualizacja')
),
spec(Name, Type, Auth, Kind, Desc_en, Desc_fr) AS (VALUES
    ('designer.routes.help.DesignerController.routesHelp',    'HTML', 'ANYBODY',  'R',
        'Help page of the routes list.',          'Page d''aide de la liste des routes.'),
    ('designer.routes.help.DesignerController.showRouteHelp', 'HTML', 'ANYBODY',  'R',
        'Displays the help text of one route.',   'Affiche le texte d''aide d''une route.'),
    ('designer.routes.help.ExerciseController.create',        'HTML', 'USER',     'R',
        'Exercise creation page.',                'Page de création d''un exercice.'),
    ('designer.routes.help.ExerciseController.edit',          'HTML', 'USER',     'R',
        'Exercise edit page.',                    'Page de modification d''un exercice.'),
    ('designer.routes.help.ExerciseController.save',          'HTML', 'USER',     'U',
        'Saves an exercise.',                     'Enregistre un exercice.'),
    ('designer.routes.help.ExerciseController.play',         'HTML', 'USER',     'R',
        'Page to play an exercise.',              'Page pour jouer un exercice.'),
    ('designer.routes.help.ExerciseController.index',         'HTML', 'USER',     'R',
        'List of exercises.',                     'Liste des exercices.'),
    ('designer.routes.help.ExerciseApi.get',                  'JSON', 'ENDPOINT', 'R',
        'Returns an exercise.',                   'Retourne un exercice.'),
    ('designer.routes.help.ExerciseApi.save',                 'JSON', 'ENDPOINT', 'U',
        'Saves an exercise.',                     'Enregistre un exercice.'),
    ('designer.routes.help.ExerciseApi.delete',               'JSON', 'ENDPOINT', 'U',
        'Deletes an exercise.',                   'Supprime un exercice.'),
    ('designer.routes.help.SolfegeController.learn',          'HTML', 'USER',     'R',
        'Solfège learning page.',                 'Page d''apprentissage du solfège.'),
    ('designer.routes.help.SolfegeController.saveScore',      'HTML', 'USER',     'U',
        'Saves the score of a solfège game.',     'Enregistre le score d''un jeu de solfège.')
)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL)
SELECT
    s.Name,
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(t.t, '{TITLE}', 'Route help'),       '{TYPE}', s.Type), '{AUTH}', a.en),
        '{KIND}', k.en), '{DESC}', s.Desc_en),
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(t.t, '{TITLE}', 'Aide de la route'), '{TYPE}', s.Type), '{AUTH}', a.fr),
        '{KIND}', k.fr), '{DESC}', s.Desc_fr),
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(t.t, '{TITLE}', 'Pomoc trasy'),      '{TYPE}', s.Type), '{AUTH}', a.pl),
        '{KIND}', k.pl), '{DESC}', s.Desc_en)
FROM spec s
JOIN auth a ON a.Code = s.Auth
JOIN kind k ON k.Code = s.Kind
CROSS JOIN tpl t;


INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('Help_NextEvents',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>'),
('Help_EventsGuest',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>'),
('Help_WeekEvents',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>'),
('Help_EventsCrossTab',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>',
 '<div class="container mt-4" style="max-width: 720px;"><p>TODO</p></div>');

 -- Help keys follow the camelCase names of the renamed controller methods
UPDATE OR REPLACE Languages
SET Name = 'designer.routes.help.EventListController.eventsGuestHelp'
WHERE Name = 'designer.routes.help.EventListController.events_guest_help';

UPDATE OR REPLACE Languages
SET Name = 'designer.routes.help.EventManagerController.eventsCrossTabHelp'
WHERE Name = 'designer.routes.help.EventManagerController.events_crossTab_help';

SQL);

        return 104;
    }
}
