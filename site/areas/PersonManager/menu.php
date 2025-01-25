<?php
$areaCurrentPage = "Person Manager";
$areaPath = "..";
require_once "../breadcrumb.php";
?>
<div class="container">
    <h1>Zone Person Manager</h1>
    <nav class="navbar navbar-expand-sm navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'I') ? 'active' : ''; ?>" href="menu.php?a=I"><h5>Import</h5></a>';
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'G') ? 'active' : ''; ?>" href="menu.php?a=G"><h5>Groups</h5></a>';
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</div>

<?php
$action = $_GET['a'] ?? '';

echo '<div class="container mt-4">';
if($action == 'I'){

}
if($action == 'G'){

}