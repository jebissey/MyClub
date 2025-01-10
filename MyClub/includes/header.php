<?php
require_once 'beforeHeader.php';
require_once __DIR__ . '/../lib/Database/Tables/Page.php';
require_once __DIR__ . '/../lib/Database/Tables/Person.php';
require_once __DIR__ . '/../lib/Database/Tables/SiteData.php';
?>

<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php

$title = (new SiteData())->getByName('Title');
echo '<title>' . $title['Value'] .'</title>';
$currentPage = basename($_SERVER['REQUEST_URI']);
?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/material_blue.css">
    <style>
        /* Custom styles to ensure footer stays at bottom */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1 0 auto;
        }

        footer {
            flex-shrink: 0;
        }
    </style>
  </head>
  <body>
    <header>
        <nav class="navbar navbar-expand-sm navbar-dark bg-dark mb-4">
            <div class="container-fluid">
                <a href="Page.php?n=1">
                    <img src="images/agenda.png" alt="Site logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
<?php

$pages = (new Page())->getOrdered('Position');
foreach ($pages as $p)
{
    print '<li class="nav-item">';
    $href = $p['File']; 
    print '<a class="nav-link ' . (($currentPage == $href) ? 'active' : '') . '" href="' . $href . '"><h5>' . $p['Name'] . '</h5></a>';
    print "</li>\n";
}
?>
                    </ul>
                    <div class="d-lg-flex col-lg-3 justify-content-lg-end">
                        <a href="Person.php?si=1">
    <?php

    if(isset($_SESSION['user'])){
        $userEmail = $_SESSION['user'];
        $person = new Person();
        $personFound = $person->getByEmail($userEmail);
        if(empty($personFound['Avatar'])){
            $avatar = '../images/emojiPensif.png';
        } else {
            $avatar = $personFound['Avatar'];
        }
        echo '<img id="userAvatar" src="images/'. $avatar . '" alt="User avatar"/>';
        $signOut = '<a href ="lib/SignIn/SignOut.php"><img src="images/SignOut.png" alt="Sign out"></a>';
    } else {
        echo '<img src="images/anonymat.png" alt="User avatar">';
        $signOut = '';
    }
    ?>
                        </a>
    <?php echo $signOut;?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    