<?php
session_start();
$pdo = new PDO("sqlite:data/MyClub.sqlite") or die("cannot open the database");
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php
$stmt = $pdo->query('SELECT Value FROM SiteData WHERE name ="Title"');
$row = $stmt->fetch();
echo '<title>' . $row['Value'] .'</title>';
?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/material_blue.css">
  </head>
  <body>

    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4>
            <div class="container-fluid">
                <a href="Page.php?n=1">
                    <img src="agenda.png" alt="Site logo"/>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                  <ul class="navbar-nav">
<?php
$stmt = $pdo->query('SELECT Name, Id FROM Page ORDER BY Position');
while ($row = $stmt->fetch())
{
    print '<li class="nav-item">';
    $href = 'Page.php?n=' .$row['Id']; 
    print '<a class="nav-link ' . (($currentPage == $href) ? 'active' : '') . '" href="' . $href . '"><h3>' . $row['Name'] . '</h3></a>';
    print "</li>\n";
}
?>
                  </ul>
                </div>
            </div>
        </nav>
    </header>
    