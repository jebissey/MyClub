<?php

declare(strict_types=1);

define('MYCLUB_ROOT', realpath(__DIR__ . '/../..'));
define('MYCLUB_WEBSITE_DIR', MYCLUB_ROOT . '/WebSite');
define('MYCLUB_DB_PATH', MYCLUB_WEBSITE_DIR . '/data/MyClub.sqlite');

spl_autoload_register(function (string $class): void {
    $prefix = 'test\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) require $file;
});
