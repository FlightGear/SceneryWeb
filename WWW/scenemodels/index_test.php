<?php
    // Inserting libs
    require_once ('inc/functions.inc.php');
?>
<html>
<head>
<?php
$nbimages = 4;
$nomimages = array();
for ($i = 1; $i <= count_models(); $i++) {
    $nomimages[$i]="image".$i.".jpg";
}
srand((double)microtime()*1000000);
$affimage = rand(1,$nbimages);
print("<img src=".$nomimages[$affimage]." border=0 width=150 height=112 alt=\"Image aléatoire\">");
?>
</body>
</html>
