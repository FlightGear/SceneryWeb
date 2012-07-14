<?php

// Inserting libs
require_once ('../../../../inc/functions.inc.php');

// The goal of this small file is to display the ac3d file of a pending model request in temporary directory.
// There is no other (known ;-) possibility to include this in the rest of the static submission script so
// it is displayed by the AC3D WebGL plugin.
// Retrieving directory from parameter sent in URL. mo_sig is just a variable named not to say directory...
$target_path = base64_decode(rawurldecode($_GET["mo_sig"]));

header("Content-type: application/octet-stream");
$dir = opendir($target_path);
    while ($file = readdir($dir)) {
        if (ShowFileExtension($file) == "ac") {
                $fichier = $target_path."/".$file;
                readfile $fichier;
        }
    }
?>
