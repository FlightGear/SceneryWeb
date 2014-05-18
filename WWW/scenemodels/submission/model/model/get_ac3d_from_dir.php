<?php
    $extension = "ac";
    $mo_sig = $_GET["mo_sig"];
    
    header("Content-type: application/octet-stream");
    require "inc_getfile.php";
?>