<?php 
        $link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
?>

<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
      
<?php include 'header.php';?>
<h1 align=center>FlightGear Scenery Database Latest News</h1>
<form>
<table border=1 width=100%>
<?php

if (isset($_REQUEST['offset']) && (preg_match('/^[0-9]+$/u',$_GET['offset'])))
{       $offset=$_REQUEST['offset'];
}
else
{       $offset=0;
};

$result=pg_query("select *,date_trunc('seconds',ne_timestamp) as formdate from fgs_news,fgs_authors where au_id=ne_author order by ne_timestamp desc limit 10 offset $offset;");
while ($row = pg_fetch_assoc($result))
{
	print "<tr><th><b><i>Posted : ".$row["formdate"]."</i> by <a href=\"author.php?id=".
		$row["au_id"]."\">".$row["au_name"]."</a></b></th></tr>".
			"<tr><td>".$row["ne_text"].
			"</td></tr>\n";
}

?>
<tr class=bottom><td colspan=9 align=center><a href="news.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="news.php?offset=<?php echo $offset+10;?>">Next</a></td></tr>
</table>
</form>
</body>
</html>
