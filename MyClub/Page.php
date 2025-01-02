<?php 
$currentPage = basename($_SERVER['REQUEST_URI']);
require 'includes/header.php';
echo "<main>\n";

if (isset($_POST['n'])){
  $id=$_GET['n'];
} else {
  $id = 1;
}
$page = (new Page())->getById($id);
if($page !== false){
  echo $page['Content'] ."\n";
}

echo "</main>\n";
require 'includes/footer.php';
?>
