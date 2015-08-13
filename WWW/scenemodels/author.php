<?php
require_once 'autoload.php';

$modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();
$authorDaoRO = \dao\DAOFactory::getInstance()->getAuthorDaoRO();

require 'view/header.php';

if (FormChecker::isAuthorId($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
    $author=$authorDaoRO->getAuthor($id);

    echo "<h1>Scenery models by ".$author->getName()."</h1>";
    if ($author->getDescription() !== NULL && strlen($author->getDescription())>0) {
        echo "<p>".$author->getDescription()."</p>";
    }
?>
<table>
<?php

    $modelMetadatas = $modelDaoRO->getModelMetadatasByAuthor($id);
    
    foreach ($modelMetadatas as $modelMetadata) {
        echo "<tr><td style=\"width: 160px\"><a href=\"app.php?c=Models&a=view&id=".$modelMetadata->getId()."\"><img src=\"app.php?c=Models&amp;a=thumbnail&amp;id=".$modelMetadata->getId()."\" width=\"160\" alt=\"\"/></a>".
            "</td><td><p><b>Name:</b> <a href=\"app.php?c=Models&a=view&id=".$modelMetadata->getId()."\">".$modelMetadata->getName()."</a></p>".
            "<p><b>Path:</b> <a href=\"objects.php?model=".$modelMetadata->getId()."\">".$modelMetadata->getFilename()."</a></p>".
            "<p><b>Last Updated: </b>".$modelMetadata->getLastUpdated()->format("Y-m-d (H:i)")."</p>".
            "</td></tr>";
    }
?>
</table>
<?php
}
require 'view/footer.php';

?>
