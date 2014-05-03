<?php

// Inserting libs
require_once 'functions.inc.php';

header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n";

// This script is used in the positions.php file in order to retrieve objects
// of a specific family, using Ajax.

// To prevent from SQL injections attempts.
$mo_id = pg_escape_string($_GET['mo_id']);

// Connecting to the database. Doing no error checking, because it would not
// show off properly at this position in HTML.

if ($mo_id != "") {
    $headerlink = connect_sphere_r();

    // Querying when the family is updated.

    $query = "SELECT mo_name, mo_notes, mo_author " .
             "FROM fgs_models WHERE mo_id = ".$mo_id.";";
    $result = pg_query($headerlink,$query);

    // Showing the results.

    echo "<objects>\n";
    while($row = pg_fetch_assoc($result)) {
        $name   = $row["mo_name"];
        $notes  = $row["mo_notes"];
        $author = $row["mo_author"];
        if ($notes == "") {
            $notes = "-";
        }
        echo "<object>\n<name>$name</name>\n<notes>$notes</notes>\n<author>$author</author>\n</object>\n";
    }
    echo "</objects>\n";

    // Closing the connection.
    pg_close($headerlink);
}
?>
