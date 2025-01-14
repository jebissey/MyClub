<?php
require_once __DIR__. '/../../includes/tinyHeader.php';
require_once __DIR__. '/ErrorDisplay.php';

if (preg_match('/[?&]l=([^&]+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $currentPage = $matches[1];
} else $currentPage = '';

$areaCurrentPage = "Webmaster";
$areaPath = "..";
require_once "../breadcrumb.php";
?>

<nav class="navbar navbar-expand-sm navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'V') ? 'active' : ''; ?>" href="menu.php?l=V"><h5>Visitors</h5></a>';
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'E') ? 'active' : ''; ?>" href="menu.php?l=E"><h5>Errors</h5></a>';
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'D') ? 'active' : ''; ?>" href="menu.php?l=D"><h5>Debug</h5></a>';
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'A') ? 'active' : ''; ?>" href="menu.php?l=A"><h5>Arwards</h5></a>';
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
$logToDisplay = $_GET['l'] ?? '';

echo '<div class="container mt-4">';
if($logToDisplay == 'E'){
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
if($logToDisplay == 'D'){
    require_once 'DebugDisplay.php';
    $viewer = new DebugDisplay($_GET['page'] ?? 1);
    echo $viewer->render([], []);
}
if($logToDisplay == 'A'){
    require_once 'Awards.php';
}
echo '</div>';
require_once __DIR__ . '/../../includes/tinyFooter.php';
?>