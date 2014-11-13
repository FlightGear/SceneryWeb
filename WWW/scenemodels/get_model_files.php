<?php
require_once 'autoload.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();

$id = $_REQUEST['id'];
$type = $_REQUEST['type'];

if (!FormChecker::isModelId($id)) {
    exit;
}
    
$modelfiles = $modelDaoRO->getModelFiles($id);

switch ($type) {
    case "pack":
        header("Content-type: application/x-gtar");
        header("Content-Disposition: inline; filename=".$id.".tgz");
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