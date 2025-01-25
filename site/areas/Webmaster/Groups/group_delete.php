<?php
require_once __DIR__ . '/../../../includes/tinyHeader.php';

require_once __DIR__ . '/../../../lib/Database/Tables/Group.php';
require_once __DIR__ . '/../../../lib/Database/Tables/GroupAuthorization.php';

$id = $_GET['id'] ?? null;

if ($id) {
    (new GroupAuthorization())->removes($id);
    (new Group())->removeById($id);
}

require_once __DIR__ . '/../../../includes/tinyFooter.php';

header('Location: ../menu.php?l=G');
exit;
?>