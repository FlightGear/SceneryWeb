<?php
/**
 * This script creates an xml file containing the country code according
 *
 */


// Inserting libs
require_once 'functions.inc.php';

$long=pg_escape_string($_GET['lg']);
$lat=pg_escape_string($_GET['lt']);

header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n".
     "<country>".
     compute_country_code_from_position($long, $lat).
     "</country>";

?>
