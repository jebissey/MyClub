<?php
require_once 'vendor/autoload.php';

use flight\Engine;
use DI\Container;
use DI\ContainerBuilder;

use Tracy\Debugger;
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    Debugger::enable(Debugger::Development, __DIR__ . '/var/tracy/log');
} else {
    Debugger::enable(Debugger::Production, __DIR__ . '/var/tracy/log');
}


$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    PDO::class => function () {
        return \app\helpers\database\Database::getInstance()->getPDO();
    },
    Engine::class => function () {
        return new Engine();
    },
    'app\controllers\GroupController' => function (Container $container) {
        return new \app\controllers\GroupController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\PersonController' => function (Container $container) {
        return new \app\controllers\PersonController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\UserController' => function (Container $container) {
        return new \app\controllers\UserController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\WebmasterController' => function (Container $container) {
        return new \app\controllers\WebmasterController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\helpers\Application' => function (Container $container) {
        return new \app\helpers\Application(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\LogController' => function (Container $container) {
        return new \app\controllers\LogController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\RegistrationController' => function (Container $container) {
        return new \app\controllers\RegistrationController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\ImportController' => function (Container $container) {
        return new \app\controllers\ImportController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\EmailController' => function (Container $container) {
        return new \app\controllers\EmailController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\ApiController' => function (Container $container) {
        return new \app\controllers\ApiController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\DbBrowserController' => function (Container $container) {
        return new \app\controllers\DbBrowserController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\TableController' => function (Container $container) {
        return new \app\controllers\DbBrowserController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\ArticleController' => function (Container $container) {
        return new \app\controllers\ArticleController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\SurveyController' => function (Container $container) {
        return new \app\controllers\SurveyController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\MediaController' => function (Container $container) {
        return new \app\controllers\MediaController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    },
    'app\controllers\eventTypeController' => function (Container $container) {
        return new \app\controllers\EventTypeController(
            $container->get(PDO::class),
            $container->get(Engine::class)
        );
    }
]);
$container = $containerBuilder->build();

$flight = $container->get(Engine::class);


// Add a custom URL parser to fix issue with URL with encoded email address
$flight->map('pass', function($str) {
    return $str;
});


$flight->before('start', function () {
    session_start();
    
    if (!isset($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }

});

$flight->map('setData', function ($key, $value) {
    Flight::set($key, $value);
});
Flight::map('getData', function ($key) {
    return Flight::get($key);
});


$articleController = $container->get('app\controllers\ArticleController');
$flight->route('GET  /redactor',            function()    use ($articleController) { $articleController->home(); });
$flight->route('GET  /articles',            function()    use ($articleController) { $articleController->index(); });
$flight->route('GET  /articles/create',     function()    use ($articleController) { $articleController->create(); });
$flight->route('GET  /articles/delete/@id', function($id) use ($articleController) { $articleController->delete($id); });
$flight->route('GET  /articles/@id',        function($id) use ($articleController) { $articleController->show($id); });
$flight->route('POST /articles/@id',        function($id) use ($articleController) { $articleController->update($id); });
$flight->route('GET  /publish/article/@id', function($id) use ($articleController) { $articleController->publish($id); });
$flight->route('POST /publish/article/@id', function($id) use ($articleController) { $articleController->publish($id); });

$surveyController = $container->get('app\controllers\SurveyController');
$flight->route('GET  /surveys/add/@id',       function($id) use ($surveyController) { $surveyController->add($id); });
$flight->route('POST /surveys/create',        function()    use ($surveyController) { $surveyController->createOrUpdate(); });
$flight->route('POST /api/surveys/reply',     function()    use ($surveyController) { $surveyController->saveReply(); });
$flight->route('GET  /api/surveys/reply/@id', function($id) use ($surveyController) { $surveyController->showReplyForm($id); });
$flight->route('GET  /surveys/results/@id',   function($id) use ($surveyController) { $surveyController->viewResults($id); });

$userController = $container->get('app\controllers\UserController');
$flight->route('GET  /',                                  function()              use ($userController, $articleController) { $userController->home($articleController); });
$flight->route('GET  /user',                              function()              use ($userController) { $userController->user(); });
$flight->route('GET  /user/sign/in',                      function()              use ($userController) { $userController->signIn(); });
$flight->route('POST /user/sign/in',                      function()              use ($userController) { $userController->signIn(); });
$flight->route('GET  /user/sign/out',                     function()              use ($userController) { $userController->signOut(); });
$flight->route('GET  /user/forgotPassword/@encodedEmail', function($encodedEmail) use ($userController) { $userController->forgotPassword($encodedEmail); });
$flight->route('GET  /user/setPassword/@token',           function($token)        use ($userController) { $userController->setPassword($token); });
$flight->route('POST /user/setPassword/@token',           function($token)        use ($userController) { $userController->setPassword($token); });
$flight->route('GET  /user/account',                      function()              use ($userController) { $userController->account(); });
$flight->route('POST /user/account',                      function()              use ($userController) { $userController->account(); });
$flight->route('GET  /user/availabilities',               function()              use ($userController) { $userController->availabilities(); });
$flight->route('POST /user/availabilities',               function()              use ($userController) { $userController->availabilities(); });
$flight->route('GET  /user/preferences',                  function()              use ($userController) { $userController->preferences(); });
$flight->route('POST /user/preferences',                  function()              use ($userController) { $userController->preferences(); });
$flight->route('GET  /user/groups',                       function()              use ($userController) { $userController->groups(); });
$flight->route('POST /user/groups',                       function()              use ($userController) { $userController->groups(); });
$flight->route('GET  /user/help',                         function()              use ($userController) { $userController->help(); });

$adminController = $container->get('app\controllers\AdminController');
$flight->route('GET  /admin',           function() use ($adminController) { $adminController->home(); });
$flight->route('GET  /admin/help',      function() use ($adminController) { $adminController->help(); });
$flight->route('GET  /api/lastVersion', function() use ($adminController) { $adminController->lastVersion(); });

$webmasterController = $container->get('app\controllers\WebmasterController');
$flight->route('GET  /webmaster',            function() use ($webmasterController) { $webmasterController->home(); });
$flight->route('GET  /arwards',              function() use ($webmasterController) { $webmasterController->arwards(); });
$flight->route('POST /arwards',              function() use ($webmasterController) { $webmasterController->arwards(); });
$flight->route('GET  /admin/webmaster/help', function() use ($webmasterController) { $webmasterController->help(); });
$flight->route('GET  /rss.xml',              function() use ($webmasterController) { $webmasterController->rssGenerator(); });

$logController = $container->get('app\controllers\LogController');
$flight->route('GET  /logs',                         function() use ($logController) { $logController->index(); });
$flight->route('GET  /referers',                     function() use ($logController) { $logController->referers(); });
$flight->route('GET  /visitors/graf',                function() use ($logController) { $logController->visitorsGraf(); });
$flight->route('GET  /analytics',                    function() use ($logController) { $logController->analytics(); });
$flight->route('GET  /api/analytics/visitorsByDate', function() use ($logController) { $logController->getVisitorsByDate(); });
$flight->route('GET  /topPages',                     function() use ($logController) { $logController->topPagesByPeriod(); });
$flight->route('GET  /crossTab',                     function() use ($logController) { $logController->crossTab(); });


$groupController = $container->get('app\controllers\GroupController');
$flight->route('GET  /groups',            function()    use ($groupController) { $groupController->index(); });
$flight->route('GET  /groups/create',     function()    use ($groupController) { $groupController->create(); });
$flight->route('POST /groups/create',     function()    use ($groupController) { $groupController->create(); });
$flight->route('GET  /groups/edit/@id',   function($id) use ($groupController) { $groupController->edit($id); });
$flight->route('POST /groups/edit/@id',   function($id) use ($groupController) { $groupController->edit($id); });
$flight->route('POST /groups/delete/@id', function($id) use ($groupController) { $groupController->delete($id); });

$registrationController = $container->get('app\controllers\RegistrationController');
$flight->route('GET  /registration',                               function()                    use ($registrationController) { $registrationController->index(); });
$flight->route('GET  /registration/groups/@id',                    function($id)                 use ($registrationController) { $registrationController->getGroups($id); });
$flight->route('POST /api/registration/add/@personId/@groupId',    function($personId, $groupId) use ($registrationController) { $registrationController->addToGroup($personId, $groupId); });
$flight->route('POST /api/registration/remove/@personId/@groupId', function($personId, $groupId) use ($registrationController) { $registrationController->removeFromGroup($personId, $groupId); });

$personController = $container->get('app\controllers\PersonController');
$flight->route('GET  /personManager',      function()    use ($personController) { $personController->home(); });
$flight->route('GET  /personManager/help', function()    use ($personController) { $personController->help(); });
$flight->route('GET  /persons',            function()    use ($personController) { $personController->index(); });
$flight->route('GET  /persons/create',     function()    use ($personController) { $personController->create(); });
$flight->route('GET  /persons/edit/@id',   function($id) use ($personController) { $personController->edit($id); });
$flight->route('POST /persons/edit/@id',   function($id) use ($personController) { $personController->edit($id); });
$flight->route('GET  /persons/delete/@id', function($id) use ($personController) { $personController->delete($id); });

$eventController = $container->get('app\controllers\EventController');
$flight->route('GET  /nextEvents',              function()    use ($eventController) { $eventController->nextEvents(); });
$flight->route('GET  /events/@id',              function($id) use ($eventController) { $eventController->show($id); });
$flight->route('GET /events/@id/register',      function($id) use ($eventController) { $eventController->register($id, true); });
$flight->route('GET /events/@id/unregister',    function($id) use ($eventController) { $eventController->register($id, false); });

$flight->route('GET  /eventManager',            function()    use ($eventController) { $eventController->home(); });
$flight->route('GET  /eventManager/help',       function()    use ($eventController) { $eventController->help(); });
$flight->route('GET  /events',                  function()    use ($eventController) { $eventController->index(); });
$flight->route('GET  /api/event/count',         function()    use ($eventController) { $eventController->getEventCount(); });
$flight->route('GET  /event/detail',            function()    use ($eventController) { $eventController->getEventDetail(); });
$flight->route('GET  /api/event/register',      function()    use ($eventController) { $eventController->registerForEvent(); });
$flight->route('GET  /api/event/unregister',    function()    use ($eventController) { $eventController->unregisterFromEvent(); });
$flight->route('POST /api/event/create',        function()    use ($eventController) { $eventController->create(); });
$flight->route('GET  /api/events/week',         function()    use ($eventController) { $eventController->getEventsForWeek(); });
$flight->route('GET  /api/check-event-manager', function()    use ($eventController) { $eventController->checkEventManager(); });
$flight->route('POST /api/event/update',        function()    use ($eventController) { $eventController->update(); });

$importController = $container->get('app\controllers\ImportController');
$flight->route('GET  /import',         function() use ($importController) { $importController->showImportForm(); });
$flight->route('POST /import',         function() use ($importController) { $importController->processImport(); });
$flight->route('POST /import/headers', function() use ($importController) { $importController->getHeadersFromCSV(); });

$emailController = $container->get('app\controllers\EmailController');
$flight->route('GET  /emails',             function()    use ($emailController) { $emailController->fetchEmails(); });
$flight->route('POST /emails',             function()    use ($emailController) { $emailController->fetchEmails(); });
$flight->route('GET  /copyToClipBoard',    function()    use ($emailController) { $emailController->copyToClipBoard(); });
$flight->route('GET  /emails/article/@id', function($id) use ($emailController) { $emailController->fetchEmailsForArticle($id); });

$dbBrowserController = $container->get('app\controllers\DbBrowserController');
$flight->route('GET  /dbbrowser',                   function()            use ($dbBrowserController) { $dbBrowserController->index(); });
$flight->route('GET  /dbbrowser/tables',            function()            use ($dbBrowserController) { $dbBrowserController->getTables(); });
$flight->route('GET  /dbbrowser/@table',            function($table)      use ($dbBrowserController) { $dbBrowserController->showTable($table); });
$flight->route('GET  /dbbrowser/@table/create',     function($table)      use ($dbBrowserController) { $dbBrowserController->showCreateForm($table); });
$flight->route('POST /dbbrowser/@table/create',     function($table)      use ($dbBrowserController) { $dbBrowserController->createRecord($table); });
$flight->route('GET  /dbbrowser/@table/edit/@id',   function($table, $id) use ($dbBrowserController) { $dbBrowserController->showEditForm($table, $id); });
$flight->route('POST /dbbrowser/@table/edit/@id',   function($table, $id) use ($dbBrowserController) { $dbBrowserController->updateRecord($table, $id); });
$flight->route('POST /dbbrowser/@table/delete/@id', function($table, $id) use ($dbBrowserController) { $dbBrowserController->deleteRecord($table, $id); });

$navBarController = $container->get('app\controllers\NavBarController');
$flight->route('GET    /navBar',                     function()    use ($navBarController) { $navBarController->index(); });
$flight->route('POST   /navBar/update',              function()    use ($navBarController) { $navBarController->updatePositions(); });
$flight->route('POST   /api/navBar/saveItem',        function()    use ($navBarController) { $navBarController->saveItem(); });
$flight->route('GET    /api/navBar/getItem/@id',     function($id) use ($navBarController) { $navBarController->getItem($id); });
$flight->route('POST   /api/navBar/updatePositions', function()    use ($navBarController) { $navBarController->updatePositions(); });
$flight->route('DELETE /api/navBar/deleteItem/@id',  function($id) use ($navBarController) { $navBarController->deleteItem($id); });
$flight->route('GET    /navBar/show/article/@id',    function($id) use ($navBarController) { $navBarController->showArticle($id); });
$flight->route('GET    /navBar/show/arwards',        function()    use ($navBarController) { $navBarController->showArwards(); });
$flight->route('GET    /navBar/show/events',         function()    use ($navBarController) { $navBarController->showEvents(); });
$flight->route('GET    /navBar/show/nextEvents',     function()    use ($navBarController) { $navBarController->showNextEvents(); });

$mediaController = $container->get('app\controllers\MediaController');
$flight->route('GET  /data/media/@year/@month/@filename',       function($year, $month, $filename) use ($mediaController) { $mediaController->viewFile($year,$month,$filename); });
$flight->route('GET  /media/upload',                            function()                         use ($mediaController) { $mediaController->showUploadForm(); });
$flight->route('POST /api/media/upload',                        function()                         use ($mediaController) { $mediaController->uploadFile(); });
$flight->route('POST /api/media/delete/@year/@month/@filename', function($year, $month, $filename) use ($mediaController) { $mediaController->deleteFile($year,$month,$filename); });
$flight->route('GET  /media/list',                              function()                         use ($mediaController) { $mediaController->listFiles(); });
$flight->route('GET  /media/gpxViewer',                         function()                         use ($mediaController) { $mediaController->gpxViewer(); });

$eventTypeController = $container->get('app\controllers\EventTypeController');
$flight->route('GET    /eventTypes',                function()    use ($eventTypeController) { $eventTypeController->index(); });
$flight->route('GET    /eventTypes/create',         function()    use ($eventTypeController) { $eventTypeController->create(); });
$flight->route('GET    /eventTypes/edit/@id',       function($id) use ($eventTypeController) { $eventTypeController->edit($id); });
$flight->route('POST   /eventTypes/edit/@id',       function($id) use ($eventTypeController) { $eventTypeController->edit($id); });
$flight->route('GET    /eventTypes/delete/@id',     function($id) use ($eventTypeController) { $eventTypeController->delete($id); });
$flight->route('POST   /api/attributes/create',     function()    use ($eventTypeController) { $eventTypeController->createAttribute(); });
$flight->route('POST   /api/attributes/update',     function()    use ($eventTypeController) { $eventTypeController->updateAttribute(); });
$flight->route('DELETE /api/attributes/delete/@id', function($id) use ($eventTypeController) { $eventTypeController->deleteAttribute($id); });
$flight->route('GET    /api/attributes/list',       function()    use ($eventTypeController) { $eventTypeController->getAttributes(); });

$apiController = $container->get('app\controllers\ApiController');
$flight->route('GET  /api/persons-by-group/@id', function($id) use ($apiController) { $apiController->getPersonsByGroup($id); });

$applicationHelper = $container->get('app\helpers\Application');
$flight->route('/help',         function() use ($applicationHelper) { $applicationHelper->help(); });
$flight->route('/legal/notice', function() use ($applicationHelper) { $applicationHelper->legalNotice(); });

$flight->route('/favicon.ico', function() {
    $path = __DIR__ . '/app/images/favicon.ico';
    if (file_exists($path)) {
        header('Content-Type: image/x-icon');
        readfile($path);
        exit;
    }
});

$flight->route('/*',            function() use ($applicationHelper) { $applicationHelper->error404(); });


$flight->map('error', function  (Throwable $ex) use ($userController, $applicationHelper){
    $userController->log(500, 'Internal error: ' . $ex->getMessage() .' in file ' . $ex->getFile() . ' at line' . $ex->getLine());
    $applicationHelper->error500($ex->getMessage(), $ex->getFile(), $ex->getLine());
});

$flight->after('start', function() use ($userController) { $userController->log(Flight::getData('code'), Flight::getData('message')); });
$flight->start();

Debugger::$email = $personController->getWebmasterEmail();


