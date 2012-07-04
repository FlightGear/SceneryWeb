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
srand((double)microtime()*1000000);
$affimage = rand(1, 2477);
echo result: $affimage;
echo "<img src=\"modelthumb.php?id=";
echo $affimage;
echo "\" width=\"320\" height=\"240\"";
echo " border=0 alt=\"Random picture\">";
?>
</center>
</body>
</html>
