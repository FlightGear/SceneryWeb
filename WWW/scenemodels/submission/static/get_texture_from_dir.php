<?php

// Inserting libs
require_once ('../../inc/functions.inc.php');

// The goal of this small file is to display the texture of a pending model request in the fgs_position_requests table.
// There is no other (known ;-) possibility to include this in the rest of the static submission script.

header("Content-type: image/jpg");
$dir = opendir("/tmp/submission");

    while ($file = readdir($dir)) {
        if (ShowFileExtension($file) == "png") {
            $texture = "/tmp/submission/".$file;
            $content = file_get_contents($texture);
            var_dump($content);
        }
    }
?>
