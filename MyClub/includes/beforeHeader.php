<?php
session_start();

if(isset($_SESSION['token'])) {
    $token = $_SESSION['token'];
} else {
    $token = bin2hex(openssl_random_pseudo_bytes(32));
}
$_SESSION['token'] = $token;

require_once  __DIR__. '/../lib/Error/ErrorHandler.php';
$errorHandler = new ErrorHandler();

?>