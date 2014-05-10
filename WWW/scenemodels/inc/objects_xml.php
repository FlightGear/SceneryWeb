<?php

// Inserting libs
require_once '../classes/DAOFactory.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();

header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n";

// This script is used in the positions.php file in order to retrieve objects
// of a specific family, using Ajax.

// To prevent from SQL injections attempts.
$mg_id = pg_escape_string($_GET['mg_id']);

// Connecting to the database. Doing no error checking, because it would not
// show off properly at this position in HTML.

if ($mg_id != "") {
    // Querying when the family is updated.
    $modelMDs = $modelDaoRO->getModelMetadatasByGroup($mg_id, 0, "ALL");

    // Showing the results.
    echo "<models>";
    foreach($modelMDs as $modelMD) {
        echo "<model><id>".$modelMD->getId()."</id><name>".htmlspecialchars($modelMD->getName())."</name></model>";
    }
    echo "</models>";
}
?>