<?php
header("Content-type: image/jpg");
require 'inc/form_checks.php';
require 'classes/DAOFactory.php';

$id = $_REQUEST['id'];

if (is_model_id($id)) {
    $modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
    $thumbnail = $modelDaoRO->getThumbnail($id);
    
    header("Content-Disposition: inline; filename=".$id.".jpg");
    
    if ($thumbnail != "") {
        echo $thumbnail;
    } else {
        readfile("http://scenery.flightgear.org/img/nothumb.jpg");
    }
}

?>
