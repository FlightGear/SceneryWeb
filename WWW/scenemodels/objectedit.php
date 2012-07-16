<?php
include 'inc/header.php';

// Inserting libs
require_once('inc/functions.inc.php');
?>
<h1>FlightGear Scenery Model Directory</h1>
<?php
if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u', $_GET['id']))) {
    $id = $_REQUEST['id'];
    $result = pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
    $object = pg_fetch_assoc($result);
}
?>
<input type="hidden" name="id" value=<?php if (isset($id)) print $id; ?> />
<table border="1" align="center">
<tr>
    <td>Unique ID</td>
    <td><?php echo $id; ?></td>
</tr>
<tr>
    <td>Latitude</td>
    <td><?php $latitude = get_object_latitude_from_id($id); echo $latitude; ?></td>
<tr>
<tr>
    <td>Longitude</td>
    <td><?php $longitude = get_object_longitude_from_id($id); echo $longitude; ?></td>
</tr>
<tr>
    <td>Country</td>
    <td><?php $country = get_country_name_from_country_code($object["ob_country"]); echo $country; ?></td>
</tr>
<tr>
    <td>Ground Elevation</td>
    <td><?php $elevation = get_object_elevation_from_id($id); echo $elevation; ?></td>
</tr>
<tr>
    <td>Elevation Offset</td>
    <td><?php $offset = get_object_offset_from_id($id); echo $offset; ?></td>
</tr>
<tr>
    <td>STG Heading (not true heading)</td>
    <td><?php $heading = heading_true_to_stg(get_object_true_orientation_from_id($id)); echo $heading; ?></td>
</tr>
<tr>
    <td>Group</td>
    <td><?php $group = get_group_name_from_id($object["ob_group"]); echo $group; ?></td>
</tr>
<tr>
    <td>Model</td>
    <td>
        <?php
            $result = pg_query("select mo_id, mo_path from fgs_models;");
            while ($row = pg_fetch_assoc($result)) {
                if ($object["ob_model"] == $row["mo_id"]) print "<a href=\"http://scenemodels.flightgear.org/modeledit.php?id=".$object["ob_model"]."\">".$row["mo_path"]."</a>";
            }
        ?>
    </td>
</tr>
<tr>
    <td>Description</td>
    <td><?php if (isset($object["ob_text"])) print $object["ob_text"]; ?></td>
</tr>
<tr><td>Geographical and model informations</td>
<td>
<center>
<iframe src="http://mapserver.flightgear.org/submap/?lon=<?php echo $longitude; ?>&lat=<?php echo $latitude; ?>&zoom=14" width="320" height="240" scrolling="auto" marginwidth="2" marginheight="2" frameborder="0">
</iframe>
&nbsp;
<img src="modelthumb.php?id=<?php echo $object["ob_model"]; ?>"/>
</center>
    </td>
</tr>
<?php
// If the object is static, let not user fix it with a shared script...
if (is_shared_or_static($id) == 'shared') {
?>
<tr>
<td colspan="2" align="center">
    <form name="update" method="post" action="submission/shared/check_update_shared.php">
    <?php echo "<input name=\"update_choice\" type=\"hidden\" maxlength=\"13\" value=\"".$id."\" />"; ?>
    <input type="submit" name="submit" value="Update this object"/>
    </form>
    <form name="delete" method="post" action="submission/shared/check_delete_shared.php">
    <?php echo "<input name=\"delete_choice\" type=\"hidden\" maxlength=\"13\" value=\"".$id."\" />"; ?>
    <input type="submit" name="submit" value="Delete this object"/>
    </form>
    </td>
</tr>
<?php
}
?>
</table>
<?php include 'inc/footer.php';?>
