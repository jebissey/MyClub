<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    $person = new Person();
    $personFound = $person->getByEmail($email);

    if($personFound){
        if($personFound['Password'] === password_hash($password, PASSWORD_DEFAULT)){
            header('Location:../Person.php');
            exit();
        }
    } 
} 
header('Location:../Page.php?n=1');
?>
