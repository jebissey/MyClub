<?php 
require 'includes/header.php';
echo "<main>\n";

if (isset($_GET['n'])) {
  $id = trim($_GET['n']);
  if (!is_numeric($id)) {
    $id=1;
  }
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
