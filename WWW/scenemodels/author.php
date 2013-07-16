<?php
if (isset($_REQUEST['id']) && preg_match('/^[0-9]+$/u',$_REQUEST['id']))
{
    $id=$_REQUEST['id'];
}

require 'inc/header.php';

if (isset($id))
{
    $result=pg_query("SELECT au_id, au_name, au_notes FROM fgs_authors WHERE au_id=$id;");
    $author=pg_fetch_assoc($result);
}
echo "<h1>Scenery models by ".$author["au_name"]."</h1>";
if (!empty($author["au_notes"]))
    echo "<p>".$author["au_notes"]."</p>";
?>
<table>
<?php
$result=pg_query("SELECT mo_id,mo_name,mo_modified,mo_path FROM fgs_models WHERE mo_author=$id ORDER BY mo_modified desc,mo_name;");
while ($row = pg_fetch_assoc($result))
{
    echo "<tr><td style=\"width: 160px\"><a href=\"modelview.php?id=".$row["mo_id"]."\"><img src=\"modelthumb.php?id=".$row["mo_id"]."\" width=\"160\" alt=\"\"/></a>".
        "</td><td><p><b>Name:</b> <a href=\"modelview.php?id=".$row["mo_id"]."\">".$row["mo_name"]."</a></p>".
        "<p><b>Path:</b> <a href=\"objects.php?model=".$row["mo_id"]."\">".$row["mo_path"]."</a></p>".
        "<p><b>Last Updated: </b>".$row["mo_modified"]."</p>".
        "</td></tr>\n";
}
?>
</table>
<?php require 'inc/footer.php';?>
