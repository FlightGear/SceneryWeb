<?php

// Inserting libs
require_once '../classes/DAOFactory.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();

header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n";

// This script is used in the positions.php file in order to retrieve objects
// of a specific family, using Ajax.

// To prevent from SQL injections attempts.
$mo_id = pg_escape_string($_GET['mo_id']);

// Connecting to the database. Doing no error checking, because it would not
// show off properly at this position in HTML.

if ($mo_id != "") {
    // Querying when the model is selected
    $modelMD = $modelDaoRO->getModelMetadata($mo_id);

    // Showing the results.
    echo "<model><name>"
         .$modelMD->getName()
         ."</name><notes>"
         .$modelMD->getDescription()
         ."</notes><author>"
         .$modelMD->getAuthor()->getId()
         ."</author></model>";
}
?>
