<?php
session_start();

$pdo = new PDO("sqlite:../data/MyClub.sqlite") or die("cannot open the database");
if($_SESSION['user']){
    $user = $_SESSION['user'];
} else {
    include('SignInForm.php');
}
?>