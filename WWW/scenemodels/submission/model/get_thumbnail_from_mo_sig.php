<?php

// The goal of this small file is to display the thumnail of a pending model request in the fgs_position_requests table.
// There is no other (known ;-) possibility to include this in the rest of the static submission script.

header("Content-type: image/jpg");
require_once '../../autoload.php';
$requestDaoRO = \dao\DAOFactory::getInstance()->getrequestDaoRO();

if (FormChecker::isSig($_GET["mo_sig"])) {
    $request = $requestDaoRO->getRequest($_GET["mo_sig"]);
    echo $request->getNewModel()->getThumbnail();
}
?>
