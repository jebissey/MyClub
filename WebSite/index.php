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
use app\helpers\LogMessage;
use app\helpers\WebApp;
use app\models\LogDataWriterHelper;
use app\modules\Webmaster\MaintenanceController;

$startTime = microtime(true);

$logDir = __DIR__ . '/var/tracy/log';
if (!is_dir($logDir)) mkdir($logDir, 0777, true);
$local_access = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1');
if ($local_access)
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

$logWriterDataHelper = new LogDataWriterHelper($application);
$flight->map('error', function (Throwable $ex) use ($logWriterDataHelper, $errorManager, $startTime) {
    $appFrames = array_filter(
        $ex->getTrace(),
        fn($frame) => isset($frame['file']) && !str_contains($frame['file'], '/vendor/')
    );

    $traceLines = array_map(function ($frame) {
        $file = $frame['file'] ?? '[internal]';
        $line = $frame['line'] ?? '?';
        $call = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
        return "  {$file}:{$line} → {$call}()";
    }, array_values($appFrames));

    $traceStr = implode("\n", $traceLines) ?: '  (aucune frame applicative trouvée)';

    $message = sprintf(
        "Internal error: %s\nin file %s at line %d\nMyClub stack:\n%s",
        $ex->getMessage(),
        $ex->getFile(),
        $ex->getLine(),
        $traceStr
    );

    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $logWriterDataHelper->add((string)ApplicationError::Error->value, $message, $duration);

    Flight::set('_error_already_logged', true);

    $errorManager->raise(
        ApplicationError::Error,
        'Error ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line ' . $ex->getLine()
    );
});

$flight->after('start', function () use ($logWriterDataHelper, $flight, $startTime) {
    if ($flight->getData('_error_already_logged')) return;

    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $logMessage = LogMessage::getInstance(null);
    $logWriterDataHelper->add(
        $logMessage->getCode() ?? (string)$flight->getData('code') ?? '',
        $logMessage->getCode() !== null
            ? $logMessage->getMessage()
            : ($flight->getData('message') ?? ''),
        $duration
    );
});

$flight->start();
