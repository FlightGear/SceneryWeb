<?php 
$link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
?>

<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
<script type="text/javascript">
function popmap(lat,lon,zoom) {
	popup = window.open("/maps?zoom="+zoom+"&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
        popup.focus();
}
</script>

</head>
<body>
<?php include 'header.php';?>
<h1 align=center>FlightGear Scenery Model Directory</h1>
<?php

if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id'])))
{	$id=$_REQUEST['id'];
	$result=pg_query("select * from fgs_models where mo_id=$id;");
	$model=pg_fetch_assoc($result);
};
?>
<form method=post action=update/model.php>
<input type=hidden name=id value=<?php if (isset($id)) print $id;?>>
<table border=1 align=center>
<tr>
	<td rowspan=8 width=320><img src="modelthumb.php?id=<?php if (isset($model["mo_id"])) print $model["mo_id"]; ?>"></td>
	<td>Name</td>
	<td><input type=text size=40 name=name<?php if (isset($model["mo_name"])) print " value=\"".$model["mo_name"]."\""; ?>></td>
<tr>
<tr>
	<td>Path</td>
	<td><input type=text size=40 name=path<?php if (isset($model["mo_path"])) print " value=\"".$model["mo_path"]."\""; ?>></td>
</tr>
<tr>
	<td>Type</td>
	<td>
        <select name="shared">
                <option value="0">
                
                <?php
                	$result=pg_query("select mg_id,mg_name from fgs_modelgroups;");
                        while ($row = pg_fetch_assoc($result))
                        {
                        	print "<option value=\"".$row["mg_id"]."\"";
                                if ($row["mg_id"]==$model["mo_shared"]) print " selected";
                                print ">".$row["mg_name"]."\n";
			}
		?>
                </select>
	</td>
</tr>
<tr>
	<td>Author</td>
	<td>
		<select name="author">
	        <?php
	        	$result=pg_query("select * from fgs_authors;");
	                while ($row = pg_fetch_assoc($result))
	                {
	                	print "<option value=\"".$row["au_id"]."\"";
	                	if ($model["mo_author"]==$row["au_id"]) print " selected";
	                	print ">".$row["au_name"]."\n";
	                }
	        ?>
	        </select>
	</td>
</tr>
<tr>
	<td>Last Updated</td>
	<td><input type=text size=15 name=updated<?php if (isset($model["mo_modified"])) print " value=\"".$model["mo_modified"]; ?>"> </td>
</tr>
<tr>
	<td>Model-ID</td>
	<td><?php print "$id"; ?></td>
</tr>
<tr>
	<td colspan=2 align=center>

<?php
$result=pg_query("select mo_shared from fgs_models where mo_id = $id;");
while ($row = pg_fetch_assoc($result))
{
	if ($row["mo_shared"]==0)
	{	
		$chunks=pg_query("SELECT ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_model=$id");
		while ($chunk = pg_fetch_assoc($chunks))
		{
			$lat=floor($chunk["ob_lat"]/10)*10;
			$lon=floor($chunk["ob_lon"]/10)*10;
			print "<a href=\"javascript:popmap(".$chunk["ob_lat"].",".$chunk["ob_lon"].",13)\">Map</a>&nbsp;-&nbsp";
		};
	};
}
?>



		<a href="modelfile.php<?php if (isset($id)) print "?id=".$id; ?>">Download Model</a>
		&nbsp;-&nbsp;
		<a href="update/thumbupload.php<?php if (isset($id)) print "?id=".$id; ?>">Upload Thumbnail</a>
		&nbsp;-&nbsp;
		<a href="update/modelupload.php<?php if (isset($id)) print "?id=".$id; ?>">Upload Model</a>

<?php
$result=pg_query("SELECT mo_author,au_name FROM fgs_models, fgs_authors WHERE mo_author=au_id AND mo_id = $id;");
while ($row = pg_fetch_assoc($result))
{
	print	"<br>List all of <a href=\"author.php?id=".$row["mo_author"]."\">".$row["au_name"]."</a>";
}
?>

	</td>
</tr>
<tr>
	<td colspan=3><textarea cols=80 rows=10 name=notes><?php if (isset($model["mo_notes"])) print $model["mo_notes"]; ?></textarea></td>
</tr>
<tr><td colspan=3 align=center><input type=submit name=submit value=add>&nbsp;<input type=submit name=submit value=update>&nbsp;<input type=submit name=submit value=delete></td></tr>
</table>
</form>

</body>
</html>
