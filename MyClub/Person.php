<?php
session_start();

if($_SESSION['user']){
    $user = $_SESSION['user'];


} else {
    include('SignIn/SignInForm.php');
}
?>