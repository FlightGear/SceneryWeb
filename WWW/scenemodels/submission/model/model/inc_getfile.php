<?php
/**
 * This file is used to retrieve a file from tgz models
 *
 * To use this script, first define;
 * $mo_sig: containing the value to define the directory
 * $extension : file extension OR $filename (if there can be more than 2 with same extension for instance)
 *
**/

// Inserting libs
require_once '../../../autoload.php';
$requestDaoRO = DAOFactory::getInstance()->getrequestDaoRO();

$mo_sig = $_GET["mo_sig"];
$type = $_REQUEST['type'];

if ((isset($filename) && !preg_match($regex['filename'], $filename))
        || !FormChecker::isSig($mo_sig)) {
    exit;
}

try {
    $request = $requestDaoRO->getRequest($mo_sig);
} catch (RequestNotFoundException $e) {
    exit;
}

$modelfiles = $request->getNewModel()->getModelFiles();

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
        $dir_array = preg_split("/\//", $_GET['name']);
        $filename = $dir_array[count($dir_array)-1];
        
        header("Content-type: image/png");
        echo $modelfiles->getTexture($filename);
        break;
}

?>