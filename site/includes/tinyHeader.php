<?php
require_once 'beforeHeader.php';
require_once __DIR__ .  '/../lib/Database/Tables/SiteData.php';

$title = (new SiteData())->getByName('Title');
$currentPage = basename($_SERVER['REQUEST_URI']);
?>

<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php echo '<title>' . $title['Value'] .'</title>'; ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            .sortable {
                cursor: pointer;
            }
            .sortable:hover {
                background-color: rgba(0, 0, 0, 0.2);
            }
            .sort-icon {
                display: inline-block;
                width: 1em;
                margin-left: 0.5em;
            }
        </style>
    </head>
    <body>