<?php

// Inserting libs
require_once ('../../inc/functions.inc.php');

// The goal of this small file is to display the texture of a pending model request in the fgs_position_requests table.
// There is no other (known ;-) possibility to include this in the rest of the static submission script.

// Retrieving directory from parameter sent in URL.
$target_path = base64_decode($_GET["p"]);
echo $target_path;
//header("Content-type: image/png");
$dir = opendir($target_path);

    while ($file = readdir($dir)) {
        if (ShowFileExtension($file) == "png") {
            $fichier = $target_path.$file;
            $texture = imagecreatefrompng($fichier);
            imagepng($texture);
            imagedestroy($texture);
        }
    }
?>
