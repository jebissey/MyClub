<?php
require_once __DIR__. '/../includes/tinyHeader.php';
?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="menu.php">Administration</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="Webmaster/menu.php">Webmaster</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="PersonManager/menu.php">Person Manager</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="EventManager/menu.php">Event Manager</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Redactor/menu.php">Redactor</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <h1>Zone d'administration</h1>
    </div>
<?php
require_once __DIR__ . '/../includes/tinyFooter.php';
?>






