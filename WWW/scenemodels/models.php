<?php 
        $link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass);
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
<form>
<table border=1 width=100%>
<?php

if (isset($_REQUEST['offset']) && (preg_match('/^[0-9]+$/u',$_GET['offset'])))
{       $offset=$_REQUEST['offset'];
}
else
{       $offset=0;
};
?>
<tr class=bottom><td colspan=9 align=center><a href="models.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="models.php?offset=<?php echo $offset+10;?>">Next</a></td></tr>
<?php
$result=pg_query("select mo_id,mo_name,mo_path,mo_notes,mo_author,au_name,mo_modified,mo_shared,CHAR_LENGTH(mo_modelfile) as mo_modelsize,mg_name,mg_id from fgs_models,fgs_authors,fgs_modelgroups where mo_author=au_id and mo_shared=mg_id order by mo_modified desc limit 10 offset $offset;");
while ($row = pg_fetch_assoc($result))
{
	print "<tr><td width=320><a href=\"modeledit.php?id=".$row["mo_id"]."\"><img src=\"modelthumb.php?id=".$row["mo_id"]."\"></a>".
			"</td><td><p><b>Name:</b> ".$row["mo_name"].
			"<p><b>Path:</b> ".$row["mo_path"].
			"<p><b>Notes:</b> ".$row["mo_notes"].
			"<p><b>Author: </b><a href=\"author.php?id=".$row["mo_author"]."\">".$row["au_name"]."</a>".
			"<P><b>Last Updated: </b>".$row["mo_modified"];
	print	"<p><b>Type: </b><a href=\"modelbrowser.php?shared=".$row["mg_id"]."\">".$row["mg_name"]."</a></b>\n";
	if ($row["mo_modelsize"]>0)
	{	print	"<P><b>Model: </b>Available in database";
		if ($row["mo_shared"]==0)
		{	
			$modelid=$row["mo_id"];
			$chunks=pg_query("SELECT ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_model=$modelid");
			while ($chunk = pg_fetch_assoc($chunks))
			{
				$lat=floor($chunk["ob_lat"]/10)*10;
				$lon=floor($chunk["ob_lon"]/10)*10;
				
				if ($lon < 0) 
				{
					$lon=sprintf("w%03d", 0-$lon);
				} 
				else 
				{
					$lon=sprintf("e%03d", $lon);
				}
				if ($lat < 0)
				{
					$lat=sprintf("s%02d", 0-$lat);
				}
				else
				{
					$lat=sprintf("n%02d", $lat);
				}
				print " (<a href=\"download/".$lon.$lat.".tgz\">".$lon.$lat."</a>) ";
				print "<a href=\"javascript:popmap(".$chunk["ob_lat"].",".$chunk["ob_lon"].",13)\">Map</a>\n";
			};
		};
	}
	else
	{	print	"<P><b>Model: </b>Not present in database\n";
	};
	print		"<P align=right><a href=\"modeledit.php?id=".$row["mo_id"]."\">Edit</a>".
			"</td></tr>\n";
}

?>
<tr class=bottom><td colspan=9 align=center><a href="models.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="models.php?offset=<?php echo $offset+10;?>">Next</a></td></tr>
</table>
</form>
</body>
</html>
