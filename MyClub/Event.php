<?php
require 'includes/header.php';
echo "<main>\n";

$id=$_GET['n'] ?? '';
/*
if($id) {
  $stmt = $pdo->query('SELECT Content FROM Page WHERE Id = ' . $id .';');
  $row = $stmt->fetch();
  echo $row['Content'];
  echo "\n id = " . $id;
}
else {
  $stmt = $pdo->query('SELECT * FROM Event WHERE StartTime > ' . $id .';');
  echo 'toto';
}
  */

echo "</main>\n";
echo '<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>';
echo '<script src="https://npmcdn.com/flatpickr/dist/l10n/fr.js"></script>';
echo '<script src="js/reservation.js"></script>';

require 'includes/footer.php';
?>
