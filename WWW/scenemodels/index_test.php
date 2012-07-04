<?php
    // Inserting libs
    require_once ('inc/functions.inc.php');
?>
<html>
<head>
</head>
<body>
<center>
<?php
$nbimages = 4;
$nomimages = array();
for ($i = 1; $i <= count_models(); $i++) {
    $nomimages[$i]="image".$i.".jpg";
}
srand((double)microtime()*1000000);
$affimage = rand(1,$nbimages);
print("<img src=\"modelthumb.php?id=".$affimage."\"" width="100" height="75".$nomimages[$affimage]." border=0 width=150 height=112 alt=\"Random picture\">");
?>
</center>
</body>
</html>
