
<?php

require_once __DIR__ . '/lib/Backup.php';
$backup = new Backup();
if ($backup->save()) {
    echo "Backup created successfully -> ";
    echo $backup->getLastBackupFolder();
} else {
    echo "Backup failed";
}



require_once __DIR__ . '/lib/PasswordManager.php';
$password = "admin_";
$signedPassword = PasswordManager::signPassword($password);
echo "<p>password = $password</p>";
echo "<p>signedPassword = $signedPassword</p>";

$userInputPassword = "admin_";
$isValid = PasswordManager::verifyPassword($userInputPassword, $signedPassword);

if ($isValid) {
    echo "Mot de passe correct !";
} else {
    echo "Mot de passe incorrect !";
}
?>



