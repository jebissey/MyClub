<?php

require_once 'lib/Error/CustomException.php';
require_once 'lib/Error/ErrorHandler.php';
$errorHandler = new ErrorHandler(true);

require_once __DIR__ . '/lib/Backup.php';
$backup = new Backup();

if ($backup->save()) {
    echo "Backup created successfully -> ";
    echo $backup->getLastBackupFolder();
} else {
    throw new CustomException("Backup failed");
}

throw new CustomException("Test", ['user_id' => 123, 'toto_id' => 321]);

?>
