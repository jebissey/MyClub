<?php 
$currentPage = basename($_SERVER['REQUEST_URI']);
include 'includes/header.php';
echo "<main>\n";

$id=$_GET['n'];
if($id != null) {
  $stmt = $pdo->query('SELECT Content FROM Page WHERE Id = ' . $id .';');
  $row = $stmt->fetch();
  echo $row['Content'] ."\n";
}

echo "</main>\n";
include 'includes/footer.php';
?>
