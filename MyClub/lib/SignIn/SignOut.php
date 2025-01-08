<?php

require_once __DIR__ . '/../../includes/beforeHeader.php';
require_once __DIR__ . '/../../includes/footer.php';

unset($_SESSION['user']);
header('Location:../../Person.php?p=-1');
exit();