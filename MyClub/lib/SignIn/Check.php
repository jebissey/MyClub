<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

echo '<p>' . var_dump($email) . '<p>'; 

    require_once  __DIR__ . '/../Database/Tables/Person.php';
    $person = new Person();
    $personFound = $person->getByEmail($email);

    if($personFound){
        if($personFound['Password'] === password_hash($password, PASSWORD_DEFAULT)){
            $_SESSION['user']=$personFound['Email'];
            header('Location:../Person.php');
            exit();
        }
    } 
} 
header('Location:../../Page.php?n=1');
?>
