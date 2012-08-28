<?php
    $dir_array = preg_split("/\//", $_GET['name']);
    $filename = $dir_array[count($dir_array)-1];
    
    header("Content-type: image/png");
    include "inc_getfile.php";
?>
