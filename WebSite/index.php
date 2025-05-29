<?php
require_once 'vendor/autoload.php';

use flight\Engine;
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

/* #region web */
use app\helpers\Application;
$applicationHelper = new Application($container->get(PDO::class), $container->get(Engine::class));
$flight->route('/help',         function() use ($applicationHelper) { $applicationHelper->help(); });
$flight->route('/legal/notice', function() use ($applicationHelper) { $applicationHelper->legalNotice(); });

use app\controllers\AlertController;
$alertController = new AlertController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /alerts',      function() use ($alertController) {$alertController->showAlerts();});
$flight->route('POST /alerts/save', function() use ($alertController) {$alertController->updateAlert();});

use app\controllers\ArticleController;
$articleController = new ArticleController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /articles',            function()    use ($articleController) { $articleController->index(); });
$flight->route('GET  /articles/create',     function()    use ($articleController) { $articleController->create(); });
$flight->route('GET  /articles/crosstab',   function()    use ($articleController) { $articleController->showArticleCrosstab();});
$flight->route('GET  /articles/delete/@id', function($id) use ($articleController) { $articleController->delete($id); });
$flight->route('GET  /articles/@id',        function($id) use ($articleController) { $articleController->show($id); });
$flight->route('POST /articles/@id',        function($id) use ($articleController) { $articleController->update($id); });
$flight->route('GET  /publish/article/@id', function($id) use ($articleController) { $articleController->publish($id); });
$flight->route('POST /publish/article/@id', function($id) use ($articleController) { $articleController->publish($id); });
$flight->route('GET  /redactor',            function()    use ($articleController) { $articleController->home(); });

use app\controllers\DbBrowserController;
$dbBrowserController = new DbBrowserController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /dbbrowser',                   function()            use ($dbBrowserController) { $dbBrowserController->index(); });
$flight->route('GET  /dbbrowser/@table',            function($table)      use ($dbBrowserController) { $dbBrowserController->showTable($table); });
$flight->route('GET  /dbbrowser/@table/create',     function($table)      use ($dbBrowserController) { $dbBrowserController->showCreateForm($table); });
$flight->route('POST /dbbrowser/@table/create',     function($table)      use ($dbBrowserController) { $dbBrowserController->createRecord($table); });
$flight->route('GET  /dbbrowser/@table/edit/@id',   function($table, $id) use ($dbBrowserController) { $dbBrowserController->showEditForm($table, $id); });
$flight->route('POST /dbbrowser/@table/edit/@id',   function($table, $id) use ($dbBrowserController) { $dbBrowserController->updateRecord($table, $id); });
$flight->route('POST /dbbrowser/@table/delete/@id', function($table, $id) use ($dbBrowserController) { $dbBrowserController->deleteRecord($table, $id); });

use app\controllers\DesignController;
$designController = new DesignController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /designs',          function() use ($designController) { $designController->index(); });
$flight->route('GET  /designs/create',   function() use ($designController) { $designController->create(); });
$flight->route('POST /designs/save',     function() use ($designController) { $designController->save(); });

use app\controllers\EmailController;
$emailController = new EmailController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /emails',             function()    use ($emailController) { $emailController->fetchEmails(); });
$flight->route('POST /emails',             function()    use ($emailController) { $emailController->fetchEmails(); });
$flight->route('GET  /emails/article/@id', function($id) use ($emailController) { $emailController->fetchEmailsForArticle($id); });

use app\controllers\EventController;
$eventController = new EventController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET /eventManager',          function()    use ($eventController) { $eventController->home(); });
$flight->route('GET /eventManager/help',     function()    use ($eventController) { $eventController->help(); });
$flight->route('GET /nextEvents',            function()    use ($eventController) { $eventController->nextEvents(); });
$flight->route('GET /events/@id',            function($id) use ($eventController) { $eventController->show($id); });
$flight->route('GET /event/location',        function()    use ($eventController) { $eventController->location(); });
$flight->route('GET /events/@id/register',   function($id) use ($eventController) { $eventController->register($id, true); });
$flight->route('GET /events/@id/unregister', function($id) use ($eventController) { $eventController->register($id, false); });
$flight->route('GET /needs',                 function()    use ($eventController) { $eventController->needs();});
$flight->route('GET /event/chat/@id',        function($id) use ($eventController) { $eventController->showEventChat($id); });

use app\controllers\EventTypeController;
$eventTypeController = new EventTypeController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /eventTypes',                function()    use ($eventTypeController) { $eventTypeController->index(); });
$flight->route('GET  /eventTypes/create',         function()    use ($eventTypeController) { $eventTypeController->create(); });
$flight->route('GET  /eventTypes/edit/@id',       function($id) use ($eventTypeController) { $eventTypeController->edit($id); });
$flight->route('POST /eventTypes/edit/@id',       function($id) use ($eventTypeController) { $eventTypeController->edit($id); });
$flight->route('GET  /eventTypes/delete/@id',     function($id) use ($eventTypeController) { $eventTypeController->delete($id); });

use app\controllers\FFAController;
$ffaController = new FFAController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET /ffa/search', function() use ($ffaController) {$ffaController->searchMember();});

use app\controllers\GroupController;
$groupController = new GroupController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /groups',            function()    use ($groupController) { $groupController->index(); });
$flight->route('GET  /groups/create',     function()    use ($groupController) { $groupController->create(); });
$flight->route('POST /groups/create',     function()    use ($groupController) { $groupController->create(); });
$flight->route('GET  /groups/edit/@id',   function($id) use ($groupController) { $groupController->edit($id); });
$flight->route('POST /groups/edit/@id',   function($id) use ($groupController) { $groupController->edit($id); });
$flight->route('POST /groups/delete/@id', function($id) use ($groupController) { $groupController->delete($id); });

use app\controllers\ImportController;
$importController = new ImportController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /import',         function() use ($importController) { $importController->showImportForm(); });
$flight->route('POST /import',         function() use ($importController) { $importController->processImport(); });
$flight->route('POST /import/headers', function() use ($importController) { $importController->getHeadersFromCSV(); });

use app\controllers\LogController;
$logController = new LogController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET /logs',                         function() use ($logController) { $logController->index(); });
$flight->route('GET /referers',                     function() use ($logController) { $logController->referers(); });
$flight->route('GET /visitors/graf',                function() use ($logController) { $logController->visitorsGraf(); });
$flight->route('GET /analytics',                    function() use ($logController) { $logController->analytics(); });
$flight->route('GET /topPages',                     function() use ($logController) { $logController->topPagesByPeriod(); });
$flight->route('GET /topArticles',                  function() use ($logController) { $logController->topArticlesByPeriod(); });
$flight->route('GET /crossTab',                     function() use ($logController) { $logController->crossTab(); });

use app\controllers\MediaController;
$mediaController = new MediaController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET /data/media/@year/@month/@filename', function($year, $month, $filename) use ($mediaController) { $mediaController->viewFile($year,$month,$filename); });
$flight->route('GET /media/upload',                      function()                         use ($mediaController) { $mediaController->showUploadForm(); });
$flight->route('GET /media/list',                        function()                         use ($mediaController) { $mediaController->listFiles(); });
$flight->route('GET /media/gpxViewer',                   function()                         use ($mediaController) { $mediaController->gpxViewer(); });

use app\controllers\NavBarController;
$navBarController = new NavBarController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /navBar',                  function()    use ($navBarController) { $navBarController->index(); });
$flight->route('GET  /navBar/show/article/@id', function($id) use ($navBarController) { $navBarController->showArticle($id); });
$flight->route('GET  /navBar/show/arwards',     function()    use ($navBarController) { $navBarController->showArwards(); });

use app\controllers\PersonController;
$personController = new PersonController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /directory',          function()    use ($personController) { $personController->showDirectory(); });
$flight->route('GET  /members/map',        function()    use ($personController) { $personController->showMap(); });
$flight->route('GET  /personManager',      function()    use ($personController) { $personController->home(); });
$flight->route('GET  /personManager/help', function()    use ($personController) { $personController->help(); });
$flight->route('GET  /persons',            function()    use ($personController) { $personController->index(); });
$flight->route('GET  /persons/create',     function()    use ($personController) { $personController->create(); });
$flight->route('GET  /persons/edit/@id',   function($id) use ($personController) { $personController->edit($id); });
$flight->route('POST /persons/edit/@id',   function($id) use ($personController) { $personController->edit($id); });
$flight->route('GET  /persons/delete/@id', function($id) use ($personController) { $personController->delete($id); });
$flight->route('GET  /presentation/edit',  function()    use ($personController) { $personController->editPresentation(); });
$flight->route('POST /presentation/edit',  function()    use ($personController) { $personController->savePresentation(); });
$flight->route('GET  /presentation/@id',   function($id) use ($personController) { $personController->showPresentation($id); });

use app\controllers\RegistrationController;
$registrationController = new RegistrationController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /registration',            function()    use ($registrationController) { $registrationController->index(); });
$flight->route('GET  /registration/groups/@id', function($id) use ($registrationController) { $registrationController->getPersonGroups($id); });

use app\controllers\SurveyController;
$surveyController = new SurveyController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /surveys/add/@id',       function($id) use ($surveyController) { $surveyController->add($id); });
$flight->route('POST /surveys/create',        function()    use ($surveyController) { $surveyController->createOrUpdate(); });
$flight->route('GET  /surveys/results/@id',   function($id) use ($surveyController) { $surveyController->viewResults($id); });

use app\controllers\UserController;
$userController = new UserController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /',                                  function()              use ($userController, $articleController) { $userController->home($articleController); });
$flight->route('GET  /user',                              function()              use ($userController)                     { $userController->user(); });
$flight->route('GET  /user/account',                      function()              use ($userController)                     { $userController->account(); });
$flight->route('POST /user/account',                      function()              use ($userController)                     { $userController->account(); });
$flight->route('GET  /user/availabilities',               function()              use ($userController)                     { $userController->availabilities(); });
$flight->route('POST /user/availabilities',               function()              use ($userController)                     { $userController->availabilities(); });
$flight->route('GET  /user/forgotPassword/@encodedEmail', function($encodedEmail) use ($userController)                     { $userController->forgotPassword($encodedEmail); });
$flight->route('GET  /user/groups',                       function()              use ($userController)                     { $userController->groups(); });
$flight->route('POST /user/groups',                       function()              use ($userController)                     { $userController->groups(); });
$flight->route('GET  /user/help',                         function()              use ($userController)                     { $userController->help(); });
$flight->route('GET  /user/preferences',                  function()              use ($userController)                     { $userController->preferences(); });
$flight->route('POST /user/preferences',                  function()              use ($userController)                     { $userController->preferences(); });
$flight->route('GET  /user/setPassword/@token',           function($token)        use ($userController)                     { $userController->setPassword($token); });
$flight->route('POST /user/setPassword/@token',           function($token)        use ($userController)                     { $userController->setPassword($token); });
$flight->route('GET  /user/sign/in',                      function()              use ($userController)                     { $userController->signIn(); });
$flight->route('POST /user/sign/in',                      function()              use ($userController)                     { $userController->signIn(); });
$flight->route('GET  /user/sign/out',                     function()              use ($userController)                     { $userController->signOut(); });
$flight->route('GET  /user/statistics',                   function()              use ($userController)                     { $userController->showStatistics();});

use app\controllers\WebmasterController;
$webmasterController = new WebmasterController($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /admin',                function() use ($webmasterController) { $webmasterController->homeAdmin(); });
$flight->route('GET  /admin/help',           function() use ($webmasterController) { $webmasterController->helpAdmin(); });
$flight->route('GET  /admin/webmaster/help', function() use ($webmasterController) { $webmasterController->helpWebmaster(); });
$flight->route('GET  /arwards',              function() use ($webmasterController) { $webmasterController->arwards(); });
$flight->route('POST /arwards',              function() use ($webmasterController) { $webmasterController->arwards(); });
$flight->route('GET  /rss.xml',              function() use ($webmasterController) { $webmasterController->rssGenerator(); });
$flight->route('GET  /sitemap.xml',          function() use ($webmasterController) { $webmasterController->sitemapGenerator(); });
$flight->route('GET  /webmaster',            function() use ($webmasterController) { $webmasterController->homeWebmaster(); });
/* #endregion */

/* #region api */
use app\api\ArticleApi;
$articleApi = new ArticleApi($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /api/author/@articleId', function($articleId) use ($articleApi) { $articleApi->getAuthor($articleId); });
$flight->route('POST /api/designs/vote', function() use ($articleApi) { $articleApi->designVote(); });
$flight->route('POST /api/media/delete/@year/@month/@filename', function($year, $month, $filename) use ($articleApi) { $articleApi->deleteFile($year,$month,$filename); });
$flight->route('POST /api/media/upload',                        function()                         use ($articleApi) { $articleApi->uploadFile(); });
$flight->route('POST /api/surveys/reply',     function()    use ($articleApi) { $articleApi->saveSurveyReply(); });
$flight->route('GET  /api/surveys/reply/@id', function($id) use ($articleApi) { $articleApi->showSurveyReplyForm($id); });

use app\api\CarouselApi;
$carouselApi = new carouselApi($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET  /api/carousel/@articleId', function($articleId) use ($carouselApi) { $carouselApi->getItems($articleId); });
$flight->route('POST /api/carousel/save',       function()           use ($carouselApi) { $carouselApi->saveItem(); });
$flight->route('POST /api/carousel/delete/@id', function($id)        use ($carouselApi) { $carouselApi->deleteItem($id); });

use app\api\EventApi;
$eventApi = new EventApi($container->get(PDO::class), $container->get(Engine::class));
$flight->route('POST   /api/attributes/create',            function()    use ($eventApi) { $eventApi->createAttribute(); });
$flight->route('DELETE /api/attributes/delete/@id',        function($id) use ($eventApi) { $eventApi->deleteAttribute($id); });
$flight->route('GET    /api/attributes/list',              function()    use ($eventApi) { $eventApi->getAttributes(); });
$flight->route('POST   /api/attributes/update',            function()    use ($eventApi) { $eventApi->updateAttribute(); });
$flight->route('GET    /api/attributes-by-event-type/@id', function($id) use ($eventApi) { $eventApi->getAttributesByEventType($id); });
$flight->route('DELETE /api/event/delete/@id',    function($id) use ($eventApi) { $eventApi->deleteEvent($id); });
$flight->route('POST   /api/event/duplicate/@id', function($id) use ($eventApi) { $eventApi->duplicateEvent($id); });
$flight->route('POST   /api/event/save',          function()    use ($eventApi) { $eventApi->saveEvent(); });
$flight->route('GET    /api/event/@id',           function($id) use ($eventApi) { $eventApi->getEvent($id); });
$flight->route('GET    /api/event-needs/@id',  function($id) use ($eventApi) { $eventApi->getEventNeeds($id); });
$flight->route('DELETE /api/needs/delete/@id',        function($id) use ($eventApi) { $eventApi->deleteNeed($id);});
$flight->route('POST   /api/needs/save',              function()    use ($eventApi) { $eventApi->saveNeed(); });
$flight->route('DELETE /api/needs/type/delete/@id',   function($id) use ($eventApi) { $eventApi->deleteNeedType($id);});
$flight->route('POST   /api/needs/type/save',         function()    use ($eventApi) { $eventApi->saveNeedType(); });
$flight->route('GET    /api/needs-by-event-type/@id', function($id) use ($eventApi) { $eventApi->getNeedsByEventType($id); });
$flight->route('POST /api/message/add',    function() use ($eventApi) {$eventApi->addMessage();});
$flight->route('POST /api/message/update', function() use ($eventApi) {$eventApi->updateMessage();});
$flight->route('POST /api/message/delete', function() use ($eventApi) {$eventApi->deleteMessage();});

use app\api\WebmasterApi;
$webmasterApi = new WebmasterApi($container->get(PDO::class), $container->get(Engine::class));
$flight->route('GET    /api/analytics/visitorsByDate',               function()                    use ($webmasterApi) { $webmasterApi->getVisitorsByDate(); });
$flight->route('GET    /api/lastVersion',                            function()                    use ($webmasterApi) { $webmasterApi->lastVersion(); });
$flight->route('DELETE /api/navBar/deleteItem/@id',                  function($id)                 use ($webmasterApi) { $webmasterApi->deleteNavbarItem($id); });
$flight->route('GET    /api/navBar/getItem/@id',                     function($id)                 use ($webmasterApi) { $webmasterApi->getNavbarItem($id); });
$flight->route('POST   /api/navBar/saveItem',                        function()                    use ($webmasterApi) { $webmasterApi->saveNavbarItem(); });
$flight->route('POST   /api/navBar/updatePositions',                 function()                    use ($webmasterApi) { $webmasterApi->updateNavbarPositions(); });
$flight->route('GET    /api/persons-by-group/@id',                   function($id)                 use ($webmasterApi) { $webmasterApi->getPersonsByGroup($id); });
$flight->route('POST   /api/registration/add/@personId/@groupId',    function($personId, $groupId) use ($webmasterApi) { $webmasterApi->addToGroup($personId, $groupId); });
$flight->route('POST   /api/registration/remove/@personId/@groupId', function($personId, $groupId) use ($webmasterApi) { $webmasterApi->removeFromGroup($personId, $groupId); });
/* #endregion */

$flight->route('/webCard', function() use ($applicationHelper) {
    $path = __DIR__ . '/app/images/businessCard.html';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        Flight::response()->header('Content-Type', 'text/html; charset=UTF-8');
        Flight::response()->write($content);
    } else {
        $applicationHelper->error404(); 
    }
});

$flight->route('/phpInfo', function() {
    header('Content-Type: text/html; charset=UTF-8');
    header('Connection: close');
    ob_start();
    phpinfo();
    $output = ob_get_contents();
    ob_end_clean();
    header('Content-Length: ' . strlen($output));
    echo $output;
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
});

$flight->route('/favicon.ico', function() use ($applicationHelper) {
    $path = __DIR__ . '/app/images/favicon.ico';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        Flight::response()->header('Content-Type', 'image/x-icon');
        Flight::response()->write($content);
    } else {
        $applicationHelper->error404(); 
    }
});

$flight->route('/*',            function() use ($applicationHelper) { $applicationHelper->error404(); });

$flight->map('error', function  (Throwable $ex) use ($userController, $applicationHelper){
    $userController->log(500, 'Internal error: ' . $ex->getMessage() .' in file ' . $ex->getFile() . ' at line' . $ex->getLine());
    $applicationHelper->error500($ex->getMessage(), $ex->getFile(), $ex->getLine());
});

$flight->after('start', function() use ($userController) { $userController->log(Flight::getData('code'), Flight::getData('message')); });
$flight->start();