<?php
require_once __DIR__ . '/../lib/Client.php';
require_once __DIR__ . '/../lib/Database/Tables/Log.php';

$email = filter_var($_SESSION['user'] ?? '', FILTER_VALIDATE_EMAIL);
$client = new Client();
(new Log())->set($client->getIp(), $client->getOs(), $client->getBrowser(), $client->getScreenResolution(), $client->getType(), $client->getUri(), $client->getToken(), $email);
?>