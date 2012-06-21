<?php

// Inserting libs

require_once('../../inc/functions.inc.php');

?>
<?php header('Content-Type: text/xml'); ?>
<?php echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n"; ?>
<?php
// This script is used in the positions.php file in order to retrieve objects of a specific family, using Ajax.
// To prevent from SQL injections attempts.

$mg_id=pg_escape_string($_GET['mg_id']);

// Connecting to the database. Doing no error checking, because it would not show off properly at this position in HTML.

if ($mg_id!='0')
{
$headerlink = connect_sphere_r();

// Querying when the family is updated.

$query = "select mo_id,mo_path,mo_name,mo_shared from fgs_models where mo_shared=".$mg_id." order by mo_path;";
$result = @pg_query($headerlink,$query);

// Showing the results.

echo "<objects>\n";
while($row = @pg_fetch_assoc($result))
{
    $id=$row["mo_id"];
    $name=preg_replace('/ /',"&nbsp;",$row["mo_path"]);
    echo "<object>\n<id>$id</id>\n<name>$name</name>\n</object>\n";
}
echo "</objects>\n";

// Closing the connection.

@pg_close($headerlink);
}
?>
