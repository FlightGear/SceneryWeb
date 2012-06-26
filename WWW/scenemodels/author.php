<?php 
if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id'])))
{       
    $id=$_REQUEST['id'];
}

?>

<?php include 'inc/header.php';?>
<h1>Scenery Author Details</h1>
<form>
<table border="1">
<?php

if (isset($id))
{       
	$result=pg_query("select * from fgs_authors where au_id=$id;");
	$author=pg_fetch_assoc($result);
};

print "<tr><td>Name</td><td>".$author["au_name"]."</td></tr>\n".
	"<tr><td>EMail</td><td>*DISABLED*</td></tr>".
	"<tr><td colspan=\"2\">".$author["au_notes"]."</td></tr>\n";

?>
</table>
<p>
<table border="1">
<?php
$result=pg_query("select mo_id,mo_name,mo_modified,mo_path from fgs_models where mo_author=$id order by mo_modified desc,mo_name;");
while ($row = pg_fetch_assoc($result))
{
	print "<tr><td width=\"160\"><a href=\"modeledit.php?id=".$row["mo_id"]."\"><img src=\"modelthumb.php?id=".$row["mo_id"]."\" width=\"160\" alt=\"\"/></a>".
		"</td><td><p><b>Name:</b> <a href=\"modeledit.php?id=".$row["mo_id"]."\">".$row["mo_name"]."</a></p>".
		"<p><b>Path:</b> <a href=\"objects.php?model=".$row["mo_id"]."\">".$row["mo_path"]."</a></p>".
		"<p><b>Last Updated: </b>".$row["mo_modified"]."</p>".
		"</td></tr>\n";
}

?>
</table>
</form>
<?php include 'inc/footer.php';?>
