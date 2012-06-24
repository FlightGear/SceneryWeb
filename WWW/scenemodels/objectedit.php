<?php include 'inc/header.php';

// Inserting libs

require_once('inc/functions.inc.php');

?>
<h1 align="center">FlightGear Scenery Model Directory</h1>
<?php

if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id'])))
{   
    $id = $_REQUEST['id'];
    $result = pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
    $object = pg_fetch_assoc($result);
};
?>
<input type="hidden" name="id" value=<?php if (isset($id)) print $id;?>/>
<table border="1" align="center">
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
    <td><?php $country = get_country_name_from_country_code($object["ob_country"]); echo $country; ?>
    </td>
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
    <td>Heading</td>
    <td><?php $heading = get_object_true_orientation_from_id($id); echo $heading; ?></td>
</tr>
<tr>
    <td>Group</td>
    <td>
        <select name="group">
            <?php
                $result = pg_query("select * from fgs_groups;");
                while ($row = pg_fetch_assoc($result))
                {
                    print "<option value=\"".$row["gp_id"]."\"";
                    if ($object["ob_group"]==$row["gp_id"]) print " selected";
                    print ">".$row["gp_name"]."\n";
                }
            ?>
            </select>
    </td>
</tr>
<tr>
    <td>Model</td>
    <td>
        <?php
            $result = pg_query("select mo_id,mo_path from fgs_models;");
            while ($row = pg_fetch_assoc($result))
            {
                // print "<option value=\"".$row["mo_id"]."\"";
                if ($object["ob_model"]==$row["mo_id"]) print $row["mo_path"];
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
<iframe src="http://mapserver.flightgear.org/map/?lon=<?php echo $object["ob_lon"]; ?>&lat=<?php echo $object["ob_lat"]; ?>&zoom=14&layers=000B0000TFFFTFFFTFTFTFFF" width="320" height="240" scrolling="auto" marginwidth="2" marginheight="2" frameborder="0">
</iframe>
&nbsp;
<img src="modelthumb.php?id=<?php echo $object["ob_model"]; ?>"/>
</center>
    </td>
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
</table>
<?php include 'inc/footer.php';?>
