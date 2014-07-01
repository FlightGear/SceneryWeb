<?php
// The goal of this small file is to give the possibility to download a model insertion request
// as a tar.gz file, containing model, textures, XML file.
// There is no other (known ;-) possibility to include this in the rest of the model submission script.

// Inserting libs
require_once '../../inc/form_checks.php';
require_once "../../classes/DAOFactory.php";
$requestDaoRO = DAOFactory::getInstance()->getrequestDaoRO();

if (is_sig($_GET["mo_sig"])) {
    try {
        $request = $requestDaoRO->getRequest($_GET["mo_sig"]);
        $modelfiles = $request->getNewModel()->getModelFiles();
        
        header("Content-type: application/x-gtar");
        header("Content-Disposition: inline; filename=newModel.tgz");
        echo $modelfiles->getPackage();
        
    } catch (RequestNotFoundException $e) {
    }
}

?>