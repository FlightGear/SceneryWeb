<?php

// Inserting libs
require_once 'inc/functions.inc.php';
require_once 'inc/form_checks.php';
require_once 'classes/DAOFactory.php';
$objectDAO = DAOFactory::getInstance()->getObjectDaoRO();

require 'inc/header.php';

if (is_object_id($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
    $object = $objectDAO->getObject($id);
?>
<h1>
<?php
if ($object->getDescription() != null) {
    print $object->getDescription();
} else {
    print "FlightGear Scenery Model Directory";
}
?>
</h1>

<input type="hidden" name="id" value="<?php echo (isset($id))?$id:""; ?>" />

<table>
    <tr>
        <td style="width: 320px" rowspan="9"><img src="modelthumb.php?id=<?php echo $object->getModelId(); ?>" alt="Thumbnail"/></td>
        <td style="width: 320px">Unique ID</td>
        <td><?php echo $id; ?></td>
    </tr>
    <tr>
        <td>Latitude</td>
        <td><?php echo $object->getLatitude(); ?></td>
    </tr>
    <tr>
        <td>Longitude</td>
        <td><?php echo $object->getLongitude(); ?></td>
    </tr>
    <tr>
        <td>Country</td>
        <td><?php
            $country = $object->getCountry();
            if ($country->getCode() != "zz" && ""!=$country->getCode()) {
                echo ("<a href=\"objects.php?country=".$country->getCode()."\">".$country->getName()."</a>");
            } else {
                echo $country->getName();
            }
        ?></td>
    </tr>
    <tr>
        <td>Ground elevation</td>
        <td><?php echo $object->getGroundElevation(); ?> m</td>
    </tr>
    <tr>
        <td>Elevation offset</td>
        <td><?php echo $object->getElevationOffset(); ?> m</td>
    </tr>
    <tr>
        <td>Heading</td>
        <td><?php echo heading_true_to_stg($object->getOrientation())."&deg; (STG) - ".$object->getOrientation()."&deg; (true)"; ?></td>
    </tr>
    <tr>
        <td>Group</td>
        <td><?php echo ("<a href=\"objects.php?group=".$object->getGroupId()."\">".$objectDAO->getObjectsGroup($object->getGroupId())->getName()."</a>"); ?></td>
    </tr>
    <tr>
        <td>Model</td>
        <td>
<?php
            $modelMetadata= DAOFactory::getInstance()->getModelDaoRO()->getModelMetadata($object->getModelId());
            print "<a href=\"http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$object->getModelId()."\">".$modelMetadata->getFilename()."</a>";
?>
        </td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            <a href="submission/object/check_update.php?update_choice=<?php echo $id;?>">Update this object</a>
<?php
    // If the object is static, let not user fix it with a shared script...
    if (!$modelMetadata->getModelsGroup()->isStatic()) {
?>
            &nbsp;| <a href="submission/object/check_delete_shared.php?delete_choice=<?php echo $id;?>">Delete this object</a>
<?php
    }
?>
        </td>
    </tr>
    <tr>
        <td align="center" colspan="3">
            <div id="map" style="resize: vertical; overflow: auto;">
                <a onclick="showMap()">Show location on map</a>
            </div>
        </td>
    </tr>
</table>

<script type="text/javascript">
function showMap() {
    var objectViewer = document.createElement("object");
    objectViewer.width = "100%";
    objectViewer.height = "99%";
    objectViewer.data = "http://mapserver.flightgear.org/popmap/?lon=<?php echo $object->getLongitude(); ?>&lat=<?php echo $object->getLatitude(); ?>&zoom=14&layers=B0TFTTTTT";
    objectViewer.type = "text/html";
    var map = document.getElementById("map");
    map.innerHTML = "";
    map.style.height = "500px";
    map.appendChild(objectViewer);
}
</script>

<?php
}

require 'inc/footer.php';
?>
