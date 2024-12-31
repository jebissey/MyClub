<?php
session_start();

if($_SESSION['user']){
    $user = $_SESSION['user'];


} else {
    require('SignIn/Form.php');
}
?>