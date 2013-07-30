<?php
    $mo_sig = $_GET["mo_sig"];
    
    $dir_array = preg_split("/\//", $_GET['name']);
    $filename = $dir_array[count($dir_array)-1];
    $type_contribute = "insert_model";
    
    header("Content-type: image/png");
    require "inc_getfile.php";
?>
