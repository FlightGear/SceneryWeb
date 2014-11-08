<?php
require 'inc/form_checks.php';
require_once 'autoload.php';

$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$authorDaoRO = DAOFactory::getInstance()->getAuthorDaoRO();

require 'inc/header.php';

if (is_author_id($_REQUEST['id'])) {
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
        echo "<tr><td style=\"width: 160px\"><a href=\"modelview.php?id=".$modelMetadata->getId()."\"><img src=\"modelthumb.php?id=".$modelMetadata->getId()."\" width=\"160\" alt=\"\"/></a>".
            "</td><td><p><b>Name:</b> <a href=\"modelview.php?id=".$modelMetadata->getId()."\">".$modelMetadata->getName()."</a></p>".
            "<p><b>Path:</b> <a href=\"objects.php?model=".$modelMetadata->getId()."\">".$modelMetadata->getFilename()."</a></p>".
            "<p><b>Last Updated: </b>".$modelMetadata->getLastUpdated()->format("Y-m-d (H:i)")."</p>".
            "</td></tr>\n";
    }
?>
</table>
<?php
}
require 'inc/footer.php';

?>
