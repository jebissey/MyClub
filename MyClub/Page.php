<?php 
$currentPage = basename($_SERVER['REQUEST_URI']);
include 'includes/header.php';
echo "<main>\n";

$id=$_GET['n'];
if($id != null) {
  $page = (new Page())->getById($id);
  echo $page['Content'] ."\n";
}

echo "</main>\n";
include 'includes/footer.php';
?>
