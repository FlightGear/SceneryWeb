<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
<title>FlightGear Scenery Object Repository</title>
</head>
<body>
<?php include 'header.php';?>
<h1 align=center>FlightGear Scenery Object Statistics</h1>
<?php 
	$link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
	$result=pg_query("select count(mo_id) as count from fgs_models;");
	$row = pg_fetch_assoc($result);
	$models=$row["count"];
	$result=pg_query("select count(ob_id) as count from fgs_objects;");
	$row = pg_fetch_assoc($result);
	$objects=$row["count"];
	$result=pg_query("select count(si_id) as count from fgs_signs;");
	$row = pg_fetch_assoc($result);
	$signs=$row["count"];

	print "<p align=center>The database currently contains <a href=\"models.php\">$models models</a> placed in the scenery as <a href=\"objects.php\">$objects seperate objects</a>, plus $signs taxiway signs.\n";
?>
<p>
<table class=main>
<tr class=main>

<td>
<table>
<tr><th colspan=2>Objects By Country</th></tr>
<?php
	$result=pg_query("select count(ob_id) as count,co_name,co_code from fgs_objects,fgs_countries where ob_country=co_code group by co_code,co_name order by count desc limit 20;");
	while ($row = pg_fetch_assoc($result))
	{	print "<tr><td><a href=\"objects.php?country=".$row["co_code"]."\">".$row["co_name"]."</a></td><td>".$row["count"]."</td></tr>\n";
	};
?>
</table>
</td>

<td>
<table>
<tr><th colspan=2>Models By Author</th></tr>
<?php
	$result=pg_query("select count(mo_id) as count,au_name,au_id from fgs_models,fgs_authors where mo_author=au_id group by au_id,au_name order by count desc limit 20;");
	while ($row = pg_fetch_assoc($result))
	{	print "<tr><td><a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</td><td>".$row["count"]."</td></tr>\n";
	};
?>
</table>
</td>

</tr>
<tr class=main>

<td align=center>
<table>
<tr><th colspan=2>Recently Updated Objects</th></tr>
<?php
	$result=pg_query("select ob_id,ob_text,ob_modified from fgs_objects order by ob_modified desc limit 10;");
	while ($row = pg_fetch_assoc($result))
	{	print "<tr><td><a href=\"objectedit.php?id=".$row["ob_id"]."\">".$row["ob_text"]."</td><td>".$row["ob_modified"]."</td></tr>\n";
	};
?>
</table>
</td>

<td align=center>
<table>
<tr><th colspan=2>Recently Updated Models</th></tr>
<?php
	$result=pg_query("select mo_id,mo_name,mo_modified from fgs_models order by mo_modified desc limit 10;");
	while ($row = pg_fetch_assoc($result))
	{	print "<tr><td><a href=\"modeledit.php?id=".$row["mo_id"]."\">".$row["mo_name"]."</td><td>".$row["mo_modified"]."</td></tr>\n";
	};
?>
</table>
</td>

</tr>
</table>

</body>
</html>
