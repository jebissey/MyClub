<?php
require_once 'includes/tinyHeader.php';

require_once __DIR__ .  '/lib/Database/Tables/SiteData.php';
$legalNotices = (new SiteData())->getByName('LegalNotices');
echo $legalNotices['Value'];

require_once 'includes/tinyFooter.php';
?>