<?php
include 'includes/header.php';

  $id=$_GET['n'];
  if($id != null) {
    $stmt = $pdo->query('SELECT Content FROM Page WHERE Id = ' . $id .';');
    $row = $stmt->fetch();
    echo $row['Content'];
    echo "\n id = " . $id;
  }
  else {
    $stmt = $pdo->query('SELECT * FROM Event WHERE StartTime > ' . $id .';');
    echo 'toto';
  }

include 'includes/footer.php';
?>
