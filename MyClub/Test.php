
<?php
/*
require_once 'lib/Error/CustomException.php';
require_once 'lib/Error/ErrorHandler.php';
$errorHandler = new ErrorHandler();

require_once __DIR__ . '/lib/Backup.php';
$backup = new Backup();

if ($backup->save()) {
    echo "Backup created successfully -> ";
    echo $backup->getLastBackupFolder();
} else {
    throw new CustomException("Backup failed");
}


require_once 'lib/Error/ErrorLogViewer.php';
$viewer = new ErrorLogViewer();
echo $viewer->render();

//require_once 'lib/Visitor/Filter.php';
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand mb-0 h1">System Logs</span>
        </div>
    </nav>

    <div class="container mt-4">
        <?php
        require_once 'lib/Error/ErrorLogViewer.php';
        $viewer = new ErrorLogViewer($_GET['page'] ?? 1);
        echo $viewer->render();
        ?>
    </div>

    <div class="container mt-4">
        <?php
        require_once 'lib/Visitor/LogDisplay.php';
        $viewer = new LogDisplay($_GET['page'] ?? 1);
        echo $viewer->render();
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

