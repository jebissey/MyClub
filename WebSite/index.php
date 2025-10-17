<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once 'vendor/autoload.php';

use Tracy\Debugger;

use app\config\Routes;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ErrorManager;
use app\helpers\WebApp;
use app\models\LogDataHelper;
use app\modules\Webmaster\MaintenanceController;

$logDir = __DIR__ . '/var/tracy/log';
if (!is_dir($logDir)) mkdir($logDir, 0777, true);
if ($_SERVER['SERVER_NAME'] === 'localhost')
    Debugger::enable(Debugger::Development, $logDir);
else Debugger::enable(Debugger::Production, $logDir);

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$application = Application::init();
$flight = $application->getFlight();
// Add a custom URL parser to fix issue with URL with encoded email address
$flight->map('pass', function ($str) {
    return $str;
});

$connectedUser = $application->getConnectedUser();
$errorManager = new ErrorManager($application);
$maintenanceController = new MaintenanceController($application, $errorManager);
$flight->before('start', function () use ($maintenanceController, $connectedUser) {
    if (!isset($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(32));
    $connectedUser->get();
    $maintenanceController->checkIfSiteIsUnderMaintenance();
});
$flight->map('setData', function ($key, $value) {
    Flight::set($key, $value);
});
$flight->map('getData', function ($key) {
    return Flight::get($key);
});

$webapp = new WebApp();
new Routes($application, $flight)->add($errorManager);

$logDataHelper = new LogDataHelper($application);
$flight->map('error', function (Throwable $ex) use ($logDataHelper, $errorManager) {
    $logDataHelper->add((string)ApplicationError::Error->value, 'Internal error: ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line' . $ex->getLine());
    $errorManager->raise(ApplicationError::Error, 'Error ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line ' . $ex->getLine());
});
$flight->after('start', function () use ($logDataHelper, $flight) {
    $logDataHelper->add((string)$flight->getData('code') ?? '', $flight->getData('message') ?? '');
});

$flight->start();
