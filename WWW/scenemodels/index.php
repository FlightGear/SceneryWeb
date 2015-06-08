<?php
require_once 'autoload.php';
$modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = \dao\DAOFactory::getInstance()->getObjectDaoRO();

if (isset($_REQUEST['offset']) && preg_match(FormChecker::$regex['pageoffset'], $_REQUEST['offset'])) {
    $offset = $_REQUEST['offset'];
} else {
    $offset = 0;
}

require 'inc/header.php';
?>

    <h1>FlightGear Scenery Website</h1>

    <p>Welcome to the <a href="http://www.flightgear.org">FlightGear</a> scenery website!</p>
    <p>This website is used to share common tools and data for all FlightGear scenery related items. It also features webforms to help gathering 3D models and objects positions all around the world. You can here contribute to FlightGear scenery by adding objects in your favorite place. Please don't hesitate, your help is welcomed!</p>
  
    <table class="left">
        <tr><th colspan="2">Recently updated objects</th></tr>
<?php
        $objects = $objectDaoRO->getObjects(5, 0);
        
        foreach ($objects as $object) {
            echo "<tr>" .
                    "<td><a href=\"objectview.php?id=".$object->getId()."\">".$object->getDescription()."</a><br/>" .
                    $object->getLastUpdated()->format("Y-m-d (H:i)")."</td>" .
                    "<td style=\"width: 100px; padding: 0px;\">".
                    "<a href=\"/objectview.php?id=". $object->getId() . "\">" .
                    "    <img title=\"". $object->getDescription()."\"" .
                    "    src=\"modelthumb.php?id=". $object->getModelId() . "\" width=\"100\" height=\"75\" style=\"vertical-align: middle\"" .
                    "    alt=\"\" />" .
                    "</a>" .
                    "</td>" .
                 "</tr>";
        }
?>
        <tr class="bottom">
            <td colspan="2" align="center">
                <a href="objects.php">More recently updated objects</a>
            </td>
        </tr>
    </table>
    <table class="right">
        <tr><th colspan="2">Recently updated models</th></tr>
<?php
        $models = $modelDaoRO->getModelMetadatas(0, 5);

        foreach ($models as $model) {
            echo "<tr>" .
                    "<td><a href=\"modelview.php?id=".$model->getId()."\">".$model->getName()."</a><br/>" .
                    $model->getLastUpdated()->format("Y-m-d (H:i)"). "</td>" .
                    "<td style=\"width: 100px; padding: 0px;\">".
                    "<a href=\"/modelview.php?id=". $model->getId() ."\">" .
                    "    <img title=\"". $model->getName().' ['.$model->getFilename().']'."\"" .
                    "    src=\"modelthumb.php?id=". $model->getId() ."\" width=\"100\" height=\"75\" style=\"vertical-align: middle\"" .
                    "    alt=\"\" />" .
                    "</a>" .
                    "</td>" .
                "</tr>";
        }
?>
        <tr class="bottom">
            <td colspan="2" align="center">
                <a href="models.php">More recently updated models</a>
            </td>
        </tr>
    </table>
    <div class="clear"></div>
  
<?php require 'inc/footer.php';?>
