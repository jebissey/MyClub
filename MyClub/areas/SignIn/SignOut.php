<?php

require_once __DIR__ . '/../../includes/tinyHeader.php';
require_once __DIR__ . '/../../includes/tinyFooter.php';

unset($_SESSION['user']);
header('Location:../../Person.php?p=-1');
exit();