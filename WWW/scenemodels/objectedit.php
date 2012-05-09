<?php 
$link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
?>

<html>
<link rel="stylesheet" href="style.css" type="text/css">
<head>
</head>
<body>
<?php include 'header.php';?>
<script type="text/javascript">
function popmap(lat,lon) {
        popup = window.open("/maps?zoom=12&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
	popup.focus();
}
</script>
<h1 align=center>FlightGear Scenery Model Directory</h1>
<?php

if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id'])))
{	$id=$_REQUEST['id'];
	$result=pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
	$object=pg_fetch_assoc($result);
};
?>
<form method=post action="update/object.php">
<input type=hidden name=id value=<?php if (isset($id)) print $id;?>>
<table border=1 align=center>
<tr>
	<td>Latitude</td>
	<td><input type=text size=12 name=lat<?php if (isset($object["ob_lat"])) print " value=".$object["ob_lat"]; ?>></td>
<tr>
<tr>
	<td>Longitude</td>
	<td><input type=text size=12 name=lon<?php if (isset($object["ob_lon"])) print " value=".$object["ob_lon"]; ?>></td>
</tr>
<tr>
	<td>Country</td>
	<td>
		<select name="country">
	        <?php
	        	$result=pg_query("select * from fgs_countries;");
	                while ($row = pg_fetch_assoc($result))
	                {
	                	print "<option value=\"".$row["co_code"]."\"";
	                	if ($object["ob_country"]==$row["co_code"]) print " selected";
	                	print ">".$row["co_name"]."\n";
	                }
	        ?>
	        </select>
	</td>
</tr>
<tr>
	<td>Ground Elevation</td>
	<td><input type=text size=6 name=gndelev<?php if (isset($object["ob_gndelev"])) print " value=".$object["ob_gndelev"]; ?>></td>
</tr>
<tr>
	<td>Elevation Offset</td>
	<td><input type=text size=6 name=elevoffset<?php if (isset($object["ob_elevoffset"])) print " value=".$object["ob_elevoffset"]; ?>></td>
</tr>
<tr>
	<td>Heading</td>
	<td><input type=text size=3 name=heading<?php if (isset($object["ob_heading"])) print " value=".$object["ob_heading"]; ?>></td>
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
		<select name="model">
	        <?php
	        	$result=pg_query("select mo_id,mo_path from fgs_models;");
	                while ($row = pg_fetch_assoc($result))
	                {
	                	print "<option value=\"".$row["mo_id"]."\"";
	                	if ($object["ob_model"]==$row["mo_id"]) print " selected";
	                	print ">".$row["mo_path"]."\n";
	                }
	        ?>
	        </select>
	</td>
</tr>
<tr>
	<td>Description</td>
	<td><input type=text size=40 name=text<?php if (isset($object["ob_text"])) print " value=\"".$object["ob_text"]."\""; ?></td>
</tr>
<tr><td>
<?php
	$id=$_REQUEST['id'];
	$result=pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
	while ($row = pg_fetch_assoc($result))
	{
	        print "<a href=\"javascript:popmap(".$row["ob_lat"].",".$row["ob_lon"].")\">Map</a></td>\n";
        }
?>
	<td align=center>
	<input type=submit name=submit value=add>&nbsp;
	<input type=submit name=submit value=update>&nbsp;
	<input type=submit name=submit value=delete>
</td></tr>
</table>
</form>

</body>
</html>
