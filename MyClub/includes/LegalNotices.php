<?php
require_once 'beforeHeader.php';

require_once __DIR__ .  '/../lib/Database/Tables/SiteData.php';
$legalNotices = (new SiteData())->getByName('LegalNotices');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mentions legales</title>
</head>
<body>

<?php
echo $legalNotices['Value'];

require_once 'footer.php';
?>