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
    'app\helpers\Application' => function (Container $container) {
        return new \app\helpers\Application(
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

// Add debugging middleware
//$flight->before('start', function() use ($flight) {
//    file_put_contents("php://stderr", 
//        "Debug Info:\n" .
//        "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n" .
//        "Flight URL: " . $flight->request()->url . "\n"
//    );
//});



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
$flight->route('GET  /user/account',                      function()              use ($userController) { $userController->account(); });
$flight->route('POST /user/account',                      function()              use ($userController) { $userController->account(); });

$groupController = $container->get('app\controllers\GroupController');
$flight->route('GET  /groups',            function()    use ($groupController) { $groupController->index(); });
$flight->route('GET  /groups/create',     function()    use ($groupController) { $groupController->create(); });
$flight->route('POST /groups/create',     function()    use ($groupController) { $groupController->create(); });
$flight->route('GET  /groups/edit/@id',   function($id) use ($groupController) { $groupController->edit($id); });
$flight->route('POST /groups/edit/@id',   function($id) use ($groupController) { $groupController->edit($id); });
$flight->route('POST /groups/delete/@id', function($id) use ($groupController) { $groupController->delete($id); });

$personController = $container->get('app\controllers\PersonController');
$flight->route('GET  /persons',            function()    use ($personController) { $personController->index(); });
$flight->route('GET  /persons/create',     function()    use ($personController) { $personController->create(); });
$flight->route('POST /persons/create',     function()    use ($personController) { $personController->create(); });
$flight->route('GET  /persons/edit/@id',   function($id) use ($personController) { $personController->edit($id); });
$flight->route('POST /persons/edit/@id',   function($id) use ($personController) { $personController->edit($id); });
$flight->route('POST /persons/delete/@id', function($id) use ($personController) { $personController->delete($id); });


$applicationHelper = $container->get('app\helpers\Application');
$flight->route('/*', function() use ($applicationHelper) { $applicationHelper->error404(); });


$flight->after('start', function() use ($userController) { $userController->log(Flight::getData('code'), Flight::getData('message')); });
$flight->start();

Debugger::$email = $personController->getWebmasterEmail();

?>
