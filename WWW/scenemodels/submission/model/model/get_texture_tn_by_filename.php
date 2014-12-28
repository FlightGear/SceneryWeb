<?php
$mo_sig = $_GET["mo_sig"];
$dir_array = preg_split("/\//", $_GET['name']);
$filenameText = $dir_array[count($dir_array)-1];

// Inserting libs
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

$textureContent = $request->getNewModel()->getModelFiles()->getTexture($filenameText);

$img = imagecreatefromstring($textureContent);
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
    echo $textureContent;
}
?>