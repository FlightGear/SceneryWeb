<?php
$mo_sig = $_GET["mo_sig"];
$dir_array = preg_split("/\//", $_GET['name']);
$filenameText = $dir_array[count($dir_array)-1];

// Inserting libs
require_once '../../../inc/functions.inc.php';
require_once '../../../autoload.php';
$requestDaoRO = DAOFactory::getInstance()->getrequestDaoRO();

if (!isset($filenameText) || !FormChecker::isFilename($filenameText)
        || !FormChecker::isSig($mo_sig)) {
    exit;
}

try {
    $request = $requestDaoRO->getRequest($mo_sig);
} catch (RequestNotFoundException $e) {
    exit;
}

$modelfiles = $request->getNewModel()->getModelFiles();

// Prepare the tmp directory

// Managing possible concurrent accesses on the maintainer side.
$target_path = open_tgz($modelfiles->getPackage());

// Looking for the file in the tmp directory
$dir = opendir($target_path);

while ($filename = readdir($dir)) {
    // If we know the extension
    if (show_file_extension($filename) == "png" && $filename == $filenameText) {
        $filepath = $target_path."/".$filename;
        break;
    }
}

$img = imagecreatefrompng( $filepath );
$width = imagesx( $img );
$height = imagesy( $img );

header('Content-Type: image/png');

if ($width>256) {
    // calculate thumbnail size
    $new_width = 256;
    $new_height = floor( $height * ( $new_width / $width ) );

    // create a new temporary image
    $tmp_img = imagecreatetruecolor( $new_width, $new_height );

    // copy and resize old image into new image 
    imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
     
    // Display the PNG directly to the browser
    imagepng($tmp_img);
    
    imagedestroy($tmp_img);
}
else {
    readfile($filepath);
}

// Ok, now we can delete the stuff we used - at least I think so ;-)
// This should be done at the end of the script
close_tgz($target_path);
?>