<?php
    $filename = $_GET['name'];
    $mo_sig = $_GET["mo_sig"];
    
    header("Content-type: image/png");
    include "inc_getfile.php";
?>
