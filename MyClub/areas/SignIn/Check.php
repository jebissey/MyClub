<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../lib/PasswordManager.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
    $password = $_POST['password'] ?? '';

    $personFound = (new Person())->getByEmail($email);
    if($personFound){
        if(PasswordManager::verifyPassword($password, $personFound['Password'])){
            $_SESSION['user'] = $personFound['Email'];
            header('Location:../../Person.php?p=' . $personFound['Id']);
            exit();
        } else {
            require __DIR__ . '/../../modals/wrongPassword.php';
        }
    } else {
        require __DIR__ . '/../../modals/userUnknown.php';
    }
}
require_once __DIR__ . '/../../includes/footer.php';
?>
