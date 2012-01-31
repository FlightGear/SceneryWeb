<?php 
	$link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
?>

<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
<?php include 'header.php';?>
<h1 align=center>FlightGear Scenery Authors Directory</h1>
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
<tr class=bottom><td colspan=9 align=center><a href="authors.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="authors.php?offset=<?php echo $offset+10;?>">Next</a></td></tr>
<?php
$result=pg_query("select * from fgs_authors order by au_name limit 10 offset $offset;");
while ($row = pg_fetch_assoc($result))
{
	print "<tr><td width=25%><b>Name: <a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</a></b>".
			"<p><b>EMail:</b> *disabled*".
			"</td><td>".$row["au_notes"].
			"</td></tr>\n";
}

?>
<tr class=bottom><td colspan=9 align=center><a href="authors.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="authors.php?offset=<?php echo $offset+10;?>">Next</a></td></tr>
</table>
</form>
</body>
</html>
