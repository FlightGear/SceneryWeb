<?php
header("Content-type: image/jpg");
require_once 'autoload.php';

$id = $_REQUEST['id'];

if (FormChecker::isModelId($id)) {
    $modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();
    $thumbnail = $modelDaoRO->getThumbnail($id);
    
    header("Content-Disposition: inline; filename=".$id.".jpg");
    
    if ($thumbnail != "") {
        echo $thumbnail;
    } else {
        readfile("http://scenery.flightgear.org/img/nothumb.jpg");
    }
}

?>
