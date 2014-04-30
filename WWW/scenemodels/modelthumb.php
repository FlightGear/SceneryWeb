<?php
header("Content-type: image/jpg");
require 'inc/form_checks.php';
require 'classes/DAOFactory.php';

$id = $_REQUEST['id'];

if (is_model_id($id)) {
    $modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
    $modelMetadata = $modelDaoRO->getModelMetadata($id);
    
    header("Content-Disposition: inline; filename=".$id.".jpg");
    
    if (strlen($modelMetadata->getThumbnail())>1024) {
        echo base64_decode($modelMetadata->getThumbnail());
    } else {
        readfile("http://scenery.flightgear.org/img/nothumb.jpg");
    }
}

?>
