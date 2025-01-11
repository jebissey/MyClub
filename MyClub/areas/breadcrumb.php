<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($areaCurrentPage) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once "menu.php"; ?>
    
    <div class="container mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../../Page.php">Accueil</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $areaPath ?>/menu.php">Administration</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= htmlspecialchars($areaCurrentPage) ?>
                </li>
            </ol>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>