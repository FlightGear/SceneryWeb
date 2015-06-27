<?php
/**
 * This file is used to retrieve a file from tgz models
 *
 * To use this script, first define;
 * mo_sig: containing the value to define the directory
 * type : file type
 * name (optional) : filename
**/

function displayThumbnail($textureContent) {
    $img = imagecreatefromstring($textureContent);
    
    $width = imagesx( $img );
    $height = imagesy( $img );
    
    if ($width>256) {
        // calculate thumbnail size
        $newWidth = 256;
        $newHeight = floor( $height * $newWidth / $width );

        // create a new temporary image
        $tmpImg = imagecreatetruecolor( $newWidth, $newHeight );

        // copy and resize old image into new image 
        imagecopyresized( $tmpImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height );

        // Display the PNG directly to the browser
        imagepng($tmpImg);
        imagedestroy($tmpImg);
    }
    else {
        echo $textureContent;
    }
}

// Inserting libs
require_once '../../../autoload.php';
$requestDaoRO = \dao\DAOFactory::getInstance()->getrequestDaoRO();

$mo_sig = $_GET["mo_sig"];
$type = $_REQUEST['type'];

if (!FormChecker::isSig($mo_sig)) {
    exit;
}

if (!empty($_GET['name'])) {
    $dir_array = preg_split("/\//", $_GET['name']);
    $filename = $dir_array[count($dir_array)-1];
    
    if (!FormChecker::isFilename($filename)) {
        exit;
    }
}

try {
    $request = $requestDaoRO->getRequest($mo_sig);
    
    if (isset($_GET['old']) && get_class($request)=="model\RequestModelUpdate") {
        $modelfiles = $request->getOldModel()->getModelFiles();
    } else {
        $modelfiles = $request->getNewModel()->getModelFiles();
    }
} catch (\dao\RequestNotFoundException $e) {
    exit;
}

switch ($type) {
    case "pack":
        header("Content-type: application/x-gtar");
        header("Content-Disposition: inline; filename=newModel.tgz");
        echo $modelfiles->getPackage();
        break;
    case "ac":
        header("Content-type: application/octet-stream");
        echo $modelfiles->getACFile();
        break;
    case "texture":
        header("Content-type: image/png");
        echo $modelfiles->getTexture($filename);
        break;
    case "thumbtexture":
        header('Content-Type: image/png');
        displayThumbnail($modelfiles->getTexture($filename));
        break;
    default:
        break;
}

?>