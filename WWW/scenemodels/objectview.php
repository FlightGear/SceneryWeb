<?php
    include 'inc/header.php';

    // Inserting libs
    require_once('inc/functions.inc.php');

    if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u', $_GET['id']))) {
        $id = $_REQUEST['id'];
        $result = pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
        $object = pg_fetch_assoc($result);
    }
?>
<h1>
<?php
    if (isset($object["ob_text"])) print $object["ob_text"];
    else print "FlightGear Scenery Model Directory";
?>
</h1>

<input type="hidden" name="id" value="<?php if (isset($id)) print $id; ?>" />

<table>
    <tr>
        <td style="width: 320px" rowspan="9"><img src="modelthumb.php?id=<?php echo $object["ob_model"]; ?>" alt="Thumbnail"/></td>
        <td style="width: 320px">Unique ID</td>
        <td><?php echo $id; ?></td>
    </tr>
    <tr>
        <td>Latitude</td>
        <td><?php $latitude = get_object_latitude_from_id($id); echo $latitude; ?></td>
    </tr>
    <tr>
        <td>Longitude</td>
        <td><?php $longitude = get_object_longitude_from_id($id); echo $longitude; ?></td>
    </tr>
    <tr>
        <td>Country</td>
        <td><?php
            $country = get_country_name_from_country_code($object["ob_country"]);
            if ($object["ob_country"] != "zz" and !empty($object["ob_country"])) echo ("<a href=\"objects.php?country=".$object["ob_country"]."\">".$country."</a>");
            else echo $country;
        ?></td>
    </tr>
    <tr>
        <td>Ground elevation</td>
        <td><?php $elevation = get_object_elevation_from_id($id); echo $elevation; ?></td>
    </tr>
    <tr>
        <td>Elevation offset</td>
        <td><?php $offset = get_object_offset_from_id($id); echo $offset; ?></td>
    </tr>
    <tr>
        <td>Heading</td>
        <td><?php $heading = heading_true_to_stg(get_object_true_orientation_from_id($id)); echo $heading." (STG) - ".get_object_true_orientation_from_id($id)." (true)"; ?></td>
    </tr>
    <tr>
        <td>Group</td>
        <td><?php $group = get_group_name_from_id($object["ob_group"]); echo ("<a href=\"objects.php?group=".$object["ob_group"]."\">".$group."</a>"); ?></td>
    </tr>
    <tr>
        <td>Model</td>
        <td>
<?php
            $result = pg_query("SELECT mo_id, mo_path FROM fgs_models WHERE mo_id = '$object[ob_model]';");
            $row = pg_fetch_assoc($result);
            print "<a href=\"http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$object["ob_model"]."\">".$row["mo_path"]."</a>";
?>
        </td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            <a href="submission/shared/check_update_shared.php?update_choice=<?php echo $id;?>">Update this object</a>
<?php
    // If the object is static, let not user fix it with a shared script...
    if (is_shared_or_static($id) == 'shared') {
?>
            &nbsp;| <a href="submission/shared/check_delete_shared.php?delete_choice=<?php echo $id;?>">Delete this object</a>
<?php
    }
?>
        </td>
    </tr>
    <tr>
        <td align="center" colspan="3" id ="mapTd">
            <a onclick="showMap()">Show location on map.</a>
        </td>
    </tr>
</table>

<script type="text/javascript">
function showMap() {
    var objectViewer = document.createElement("object");
    objectViewer.width = "100%";
    objectViewer.height = "500px";
    objectViewer.data = "http://mapserver.flightgear.org/popmap/?lon=<?php echo $longitude; ?>&lat=<?php echo $latitude; ?>&zoom=14";
    objectViewer.type = "text/html";
    var webglTd = document.getElementById("mapTd");
    webglTd.innerHTML = "";
    webglTd.appendChild(objectViewer);
}
</script>

<?php include 'inc/footer.php';?>
