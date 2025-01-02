<?php

require_once 'lib/Error/CustomException.php';
require_once 'lib/Error/ErrorHandler.php';
$errorHandler = new ErrorHandler();

require_once __DIR__ . '/lib/Backup.php';
$backup = new Backup();

if ($backup->save()) {
    echo "Backup created successfully -> ";
    echo $backup->getLastBackupFolder();
} else {
    throw new CustomException("Backup failed");
}


require_once 'lib/Error/ErrorLogViewer.php';
$viewer = new ErrorLogViewer();
$viewer->render();

//throw new CustomException("Test error", ['user_id' => 123, 'toto_id' => 321]);
?>
