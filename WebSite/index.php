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



$userController = $container->get('app\controllers\UserController');
$flight->route('GET  /',                                  function()              use ($userController) { $userController->home(); });
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
$flight->route('GET  /admin',            function() use ($adminController) { $adminController->home(); });
$flight->route('GET  /admin/help',       function() use ($adminController) { $adminController->help(); });

$webmasterController = $container->get('app\controllers\WebmasterController');
$flight->route('GET  /webmaster',            function() use ($webmasterController) { $webmasterController->home(); });
$flight->route('GET  /arwards',              function() use ($webmasterController) { $webmasterController->arwards(); });
$flight->route('GET  /admin/webmaster/help', function() use ($webmasterController) { $webmasterController->help(); });

$logController = $container->get('app\controllers\LogController');
$flight->route('GET  /logs',     function() use ($logController) { $logController->index(); });
$flight->route('GET  /referers', function() use ($logController) { $logController->referers(); });

$groupController = $container->get('app\controllers\GroupController');
$flight->route('GET  /groups',            function()    use ($groupController) { $groupController->index(); });
$flight->route('GET  /groups/create',     function()    use ($groupController) { $groupController->create(); });
$flight->route('POST /groups/create',     function()    use ($groupController) { $groupController->create(); });
$flight->route('GET  /groups/edit/@id',   function($id) use ($groupController) { $groupController->edit($id); });
$flight->route('POST /groups/edit/@id',   function($id) use ($groupController) { $groupController->edit($id); });
$flight->route('POST /groups/delete/@id', function($id) use ($groupController) { $groupController->delete($id); });

$registrationController = $container->get('app\controllers\RegistrationController');
$flight->route('GET  /registration',                           function()                    use ($registrationController) { $registrationController->index(); });
$flight->route('GET  /registration/groups/@id',                function($id)                 use ($registrationController) { $registrationController->getGroups($id); });
$flight->route('POST /registration/add/@personId/@groupId',    function($personId, $groupId) use ($registrationController) { $registrationController->addToGroup($personId, $groupId); });
$flight->route('POST /registration/remove/@personId/@groupId', function($personId, $groupId) use ($registrationController) { $registrationController->removeFromGroup($personId, $groupId); });

$personController = $container->get('app\controllers\PersonController');
$flight->route('GET  /personManager',      function()    use ($personController) { $personController->home(); });
$flight->route('GET  /personManager/help', function()    use ($personController) { $personController->help(); });
$flight->route('GET  /persons',            function()    use ($personController) { $personController->index(); });
$flight->route('GET  /persons/create',     function()    use ($personController) { $personController->create(); });
$flight->route('GET  /persons/edit/@id',   function($id) use ($personController) { $personController->edit($id); });
$flight->route('POST /persons/edit/@id',   function($id) use ($personController) { $personController->edit($id); });
$flight->route('POST /persons/delete/@id', function($id) use ($personController) { $personController->delete($id); });

$eventController = $container->get('app\controllers\EventController');
$flight->route('GET  /eventManager',      function()    use ($eventController) { $eventController->home(); });
$flight->route('GET  /eventManager/help', function()    use ($eventController) { $eventController->help(); });
$flight->route('GET  /events',            function()    use ($eventController) { $eventController->index(); });

$importController = $container->get('app\controllers\ImportController');
$flight->route('GET  /import',         function() use ($importController) { $importController->showImportForm(); });
$flight->route('POST /import',         function() use ($importController) { $importController->processImport(); });
$flight->route('POST /import/headers', function() use ($importController) { $importController->getHeadersFromCSV(); });

$emailController = $container->get('app\controllers\EmailController');
$flight->route('GET  /emails',          function() use ($emailController) { $emailController->fetchEmails(); });
$flight->route('POST /emails',          function() use ($emailController) { $emailController->fetchEmails(); });
$flight->route('GET  /copyToClipBoard', function() use ($emailController) { $emailController->copyToClipBoard(); });

$applicationHelper = $container->get('app\helpers\Application');
$flight->route('/help',         function() use ($applicationHelper) { $applicationHelper->help(); });
$flight->route('/legal/notice', function() use ($applicationHelper) { $applicationHelper->legalNotice(); });
$flight->route('/*',            function() use ($applicationHelper) { $applicationHelper->error404(); });


$flight->map('error', function  (Throwable $ex) use ($userController, $applicationHelper){
    $userController->log(500, 'Internal error: ' . $ex->getMessage() .' in file ' . $ex->getFile() . ' at line' . $ex->getLine());
    $applicationHelper->error500($ex->getMessage(), $ex->getFile(), $ex->getLine());
});

$flight->after('start', function() use ($userController) { $userController->log(Flight::getData('code'), Flight::getData('message')); });
$flight->start();

Debugger::$email = $personController->getWebmasterEmail();

?>
