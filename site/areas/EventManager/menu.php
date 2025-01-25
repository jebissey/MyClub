<?php
require_once __DIR__. '/../../includes/tinyHeader.php';

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
                    <a class="nav-link <?php echo ($currentPage == 'R') ? 'active' : ''; ?>" href="menu.php?l=R"><h5>My events</h5></a>';
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'C') ? 'active' : ''; ?>" href="menu.php?l=C"><h5>Create new event</h5></a>';
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
$action = $_GET['l'] ?? '';

echo '<div class="container mt-4">';
if($action == 'R'){

    echo 'all my events here';
}
if($action == 'C'){
    echo 'create a new event here';
}
echo '</div>';
require_once __DIR__ . '/../../includes/tinyFooter.php';
?>