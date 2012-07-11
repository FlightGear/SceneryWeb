<?php

// Inserting libs
require_once ('../../inc/functions.inc.php');

// The goal of this small file is to display the texture of a pending model request in the fgs_position_requests table.
// There is no other (known ;-) possibility to include this in the rest of the static submission script.

// Retrieving directory from parameter sent in URL.

// echo "p: ".$_GET["mo_sig"];
//$target_path = base64_decode(rawurldecode($_GET["mo_sig"]));
// echo "target  path: ".$target_path;
header("Content-type: image/png");
$dir = opendir($target_path);

    while ($file = readdir($dir)) {
        if (ShowFileExtension($file) == "png") {
            $fichier = $target_path."/".$file;
            $texture = imagecreatefrompng($fichier);
            imagepng($texture);
            imagedestroy($texture);
        }
    }
?>
