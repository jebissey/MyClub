<?php

require_once __DIR__ . '/lib/Backup.php';

$backup = new Backup();

if ($backup->save()) {
    echo "Backup created successfully -> ";
    echo $backup->getLastBackupFolder();
} else {
    echo "Backup failed";
}

?>
