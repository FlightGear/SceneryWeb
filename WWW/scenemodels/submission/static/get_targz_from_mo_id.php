<?php

// Inserting libs
require_once ('../../inc/functions.inc.php');

// The goal of this small file is to display the texture of a pending model request in the fgs_position_requests table.
// There is no other (known ;-) possibility to include this in the rest of the static submission script.
// Retrieving directory from parameter sent in URL. mo_sig is just a variable named not to say directory...
$target_path = base64_decode(rawurldecode($_GET["mo_sig"]));

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

// Ok, now we can delete the stuff we used - at least I think so ;-)
// This should be done at the end of the script
unlink($target_path.'/submitted_files.tar.gz');  // Deletes compressed file
clearDir($target_path);                          // Deletes temporary submission directory
?>
