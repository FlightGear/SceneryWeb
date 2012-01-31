<?php 
$link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass);
                
if (isset($_REQUEST['offset']) && (preg_match('/^[0-9]+$/u',$_GET['offset'])))
{	$offset=$_REQUEST['offset'];
}
else
{	$offset=0;
};

$filter="";

if (isset($_REQUEST['model']) && (preg_match('/^[0-9]+$/u',$_GET['model'])) && $_REQUEST['model']>0)
{	$model=$_REQUEST['model'];
	$filter.=" and ob_model=".$_REQUEST['model'];
}
else 
{	$model="";
};

if (isset($_REQUEST['group']) && (preg_match('/^[0-9]+$/u',$_GET['group'])) && $_REQUEST['group']>0)
{	$group=$_REQUEST['group'];
	$filter.=" and ob_group=".$_REQUEST['group'];
}
else
{	$group="";
};

if (isset($_REQUEST['elevation']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['elevation'])))
{	$min=$_REQUEST['elevation']-25;
	$max=$_REQUEST['elevation']+25;
	$elevation=$_REQUEST['elevation'];
	$filter.=" and ob_gndelev>".$min." and ob_gndelev<".$max;
}
else
{	$elevation="";
};

if (isset($_REQUEST['elevoffset']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['elevoffset'])))
{	$min=$_REQUEST['elevoffset']-25;
	$max=$_REQUEST['elevoffset']+25;
	$elevoffset=$_REQUEST['elevoffset'];
	$filter.=" and ob_gndelev>".$min." and ob_gndelev<".$max;
}
else
{	$elevoffset="";
};

if (isset($_REQUEST['heading']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['heading'])))
{	$min=$_REQUEST['heading']-5;
	$max=$_REQUEST['heading']+5;
	$heading=$_REQUEST['heading'];
	$filter.=" AND ob_heading>".$min." AND ob_heading<".$max;
}
else
{	$heading="";
};

if (isset($_REQUEST['lat']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['lat'])))
{	$lat=$_REQUEST['lat'];
	$filter.=" AND ST_Y(wkb_geometry) LIKE ".$_REQUEST['lat']."";
}
else
{	$lat="";
};

if (isset($_REQUEST['lon']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['lon'])))
{	$lon=$_REQUEST['lon'];
	$filter.=" AND ST_X(wkb_geometry) LIKE ".$_REQUEST['lon']."";
}
else
{	$lon="";
};

if (isset($_REQUEST['country']) && (preg_match('/^[a-z][a-z]$/u',$_GET['country'])))
{	$country=$_REQUEST['country'];
	$filter.=" and ob_country='".$_REQUEST['country']."'";
}
else
{	$country="";
};

if (isset($_REQUEST['description']) && (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u',$_GET['description'])))
{	$description=$_REQUEST['description'];
	$filter.=" and (ob_text like '%".$_REQUEST['description']."\" or ob_text like \"".$_REQUEST['description']."%' or ob_text like '%".$_REQUEST['description']."%')";
}
else
{	$description="";
};
?>

<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
<?php include 'header.php';?>
<script type="text/javascript">
function popobject(id) {
	popup = window.open("/submit/editnewobject.php?object="+id, "obj"+id, "height=250,width=640,scrollbars=no,resizable=yes");
	popup.focus();
}
function popmap(lat,lon) {
        popup = window.open("/maps?zoom=12&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
	popup.focus();
}
</script>

<form action="objects.php" method="get">
<table border=1 align=center>
<tr valign=bottom>
	<th>Lat</th>
	<th>Lon</th>
	<th>Ground<br>Elevation (m)</th>
	<th>Elevation<br>Offset</th>
	<th>Heading</th>
	<th>Description</th>
	<th>Model</th>
	<th>Group</th>
	<th>Country</th>
</tr>
<tr valign=bottom>
	<th><input type=text name=lat size=12<?php print " value=".$lat ?>></th>
	<th><input type=text name=lon size=12<?php print " value=".$lon ?>></th>
	<th><input type=text name=elevation size=6<?php print " value=".$elevation ?>></th>
	<th><input type=text name=elevoffset size=6<?php print " value=".$elevoffset ?>></th>
	<th><input type=text name=heading size=3<?php print " value=".$heading ?>></th>
	<th><input type=text name=description size=12<?php print " value=".$description ?>></th>
	<th>
		<select name="model">
		<option value="0">
<?php
	$result=pg_query("select mo_id,mo_path from fgs_models order by mo_path;");
	while ($row = pg_fetch_assoc($result))
	{	
		$models[$row["mo_id"]]=$row["mo_path"];
		print "<option value=\"".$row["mo_id"]."\"";
		if ($row["mo_id"]==$model) print " selected";
		print ">".$row["mo_path"]."\n";
	}
?>
		</select>
	</th>
	<th>
		<select name="group">
		<option value="0">
<?php
	$result=pg_query("select gp_id,gp_name from fgs_groups;");
	while ($row = pg_fetch_assoc($result))
	{
		$groups[$row["gp_id"]]=$row["gp_name"];
		print "<option value=\"".$row["gp_id"]."\"";
		if ($row["gp_id"]==$group) print " selected";
		print ">".$row["gp_name"]."\n";
	}
?>
		</select>
	</th>
	<th>
		<select name="country">
		<option value="">
<?php
	$result=pg_query("select co_code,co_name from fgs_countries;");
	while ($row = pg_fetch_assoc($result))
	{
		$countries{$row["co_code"]}=$row["co_name"];
		print "<option value=\"".$row["co_code"]."\"";
		if ($row["co_code"]==$country) print " selected";
		print ">".$row["co_name"]."\n";
	}
?>
		</select>
	</th>
	<th>
		<input type=submit name=filter value=Filter>
	</th>
</tr>
<tr class=bottom><td colspan=11 align=center>
<?php
	$prev=$offset-20;
	$next=$offset+20;
	print "<a href=\"objects.php?offset=".$prev.
	"&lat=".$lat.
	"&lon=".$lon.
	"&elevation=".$elevation.
	"&elevoffset=".$elevoffset.
	"&description=".$description.
	"&heading=".$heading.
	"&model=".$model.
	"&group=".$group.
	"&country=".$country.
	"&filter=Filter".
	"\">Prev</a>   ";
	print "<a href=\"objects.php?offset=".$next.
	"&lat=".$lat.
	"&lon=".$lon.
	"&elevation=".$elevation.
	"&elevoffset=".$elevoffset.
	"&heading=".$heading.
	"&description=".$description.
	"&model=".$model.
	"&group=".$group.
	"&country=".$country.
	"&filter=Filter".
	"\">Next</a>";
?>
</td></tr>
<?php

$query="SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id IS NOT NULL ".$filter." LIMIT 20 OFFSET ".$offset.";";

$result=pg_query($query);
while ($row = pg_fetch_assoc($result))
{	
	print "<tr class=object><td>".$row["ob_lat"]."</td><td>"
			.$row["ob_lon"]."</td><td>"
			.$row["ob_gndelev"]."</td><td>"
			.$row["ob_elevoffset"]."</td><td>"
			.$row["ob_heading"]."</td><td>"
			.$row["ob_text"]."</td><td>"
			.$models[$row["ob_model"]]."</td><td>"
			.$groups[$row["ob_group"]]."</td><td>"
			.$countries[$row["ob_country"]]."</td><td>"
			."<a href=\"objectedit.php?id=".$row["ob_id"]."\">Edit</a> "
			."<a href=\"javascript:popmap(".$row["ob_lat"].",".$row["ob_lon"].")\">Map</a></tr>\n";
}

?>
<tr class=bottom><td colspan=11 align=center>
<?php
	$prev=$offset-20;
	$next=$offset+20;
	print "<a href=\"objects.php?offset=".$prev.
	"&lat=".$lat.
	"&lon=".$lon.
	"&elevation=".$elevation.
	"&elevoffset=".$elevoffset.
	"&description=".$description.
	"&heading=".$heading.
	"&model=".$model.
	"&group=".$group.
	"&country=".$country.
	"&filter=Filter".
	"\">Prev</a>   ";
	print "<a href=\"objects.php?offset=".$next.
	"&lat=".$lat.
	"&lon=".$lon.
	"&elevation=".$elevation.
	"&elevoffset=".$elevoffset.
	"&heading=".$heading.
	"&description=".$description.
	"&model=".$model.
	"&group=".$group.
	"&country=".$country.
	"&filter=Filter".
	"\">Next</a>";
?>
</td></tr>
</table>
</form>
</body>
</html>
