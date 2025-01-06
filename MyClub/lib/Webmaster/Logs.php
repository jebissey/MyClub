<?php
require_once '../../includes/beforeHeader.php';
if (preg_match('/[?&]l=([^&]+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $currentPage = $matches[1];
} else $currentPage = '';
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
<nav class="navbar navbar-expand-sm navbar-dark bg-dark mb-4>
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'V') ? 'active' : ''; ?>" href="Logs.php?l=V"><h5>Visitors</h5></a>';
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'E') ? 'active' : ''; ?>" href="Logs.php?l=E"><h5>Errors</h5></a>';
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'D') ? 'active' : ''; ?>" href="Logs.php?l=D"><h5>Debug</h5></a>';
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
$logToDisplay = $_GET['l'] ?? '';

echo '<div class="container mt-4">';
if($logToDisplay == 'E'){
    require_once '../Error/ErrorDisplay.php';
    $viewer = new ErrorDisplay($_GET['page'] ?? 1);
    echo $viewer->render([], []);
}
if($logToDisplay == 'V'){
    require_once 'Visitor/LogDisplay.php';
    $filters = [
        'os' => 'Filter OS',
        'browser' => 'Filter Browser',
        'type' => 'Filter Client Type',
        'uri' => 'Filter URI',
        'email' => 'Filter Email'
    ];
    $additionalGets = ['l' => 'V'];
    $viewer = new LogDisplay($_GET['page'] ?? 1);
    echo $viewer->render($filters, $additionalGets);
}
echo '</div>';
require_once __DIR__ . '/../../includes/footer.php';
?>