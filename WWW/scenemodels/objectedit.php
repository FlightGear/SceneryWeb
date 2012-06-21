<?php include 'header.php';?>
<h1 align="center">FlightGear Scenery Model Directory</h1>
<?php

if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id'])))
{	$id=$_REQUEST['id'];
	$result=pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
	$object=pg_fetch_assoc($result);
};
?>
<input type="hidden" name="id" value=<?php if (isset($id)) print $id;?>/>
<table border="1" align="center">
<tr>
	<td>Latitude</td>
	<td><?php if (isset($object["ob_lat"])) print $object["ob_lat"]; ?></td>
<tr>
<tr>
	<td>Longitude</td>
	<td><?php if (isset($object["ob_lon"])) print $object["ob_lon"]; ?></td>
</tr>
<tr>
	<td>Country</td>
	<td>
		<?php
	        	$result=pg_query("select * from fgs_countries;");
	                while ($row = pg_fetch_assoc($result))
	                {
	                	if ($object["ob_country"]==$row["co_code"]) print $row["co_name"];
	                }
	        ?>
	</td>
</tr>
<tr>
	<td>Ground Elevation</td>
	<td><?php if (isset($object["ob_gndelev"])) print $object["ob_gndelev"]; ?></td>
</tr>
<tr>
	<td>Elevation Offset</td>
	<td><?php if (isset($object["ob_elevoffset"])) print $object["ob_elevoffset"]; ?></td>
</tr>
<tr>
	<td>Heading</td>
	<td><?php if (isset($object["ob_heading"])) print $object["ob_heading"]; ?></td>
</tr>
<tr>
	<td>Group</td>
	<td>
		<select name="group">
	        <?php
	        	$result=pg_query("select * from fgs_groups;");
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
	        	$result=pg_query("select mo_id,mo_path from fgs_models;");
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
<?php
	$id=$_REQUEST['id'];
	$result=pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
	while ($row = pg_fetch_assoc($result))
	{
?>
<iframe src="http://mapserver.flightgear.org/map/?lon=<?php echo $row["ob_lon"]; ?>&lat=<?php echo $row["ob_lat"]; ?>&zoom=14&layers=000B0000TFFFTFFFTFTFTFFF" width="300" height="225" scrolling="auto" marginwidth="2" marginheight="2" frameborder="0">
</iframe>
<img src="modelthumb.php?id=<?php echo $object["ob_model"]; ?>"/>
<?php
        }
?>
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
<?php include 'footer.php';?>
