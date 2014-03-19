<?php
  date_default_timezone_set('CET');
  $icao = $_POST[icao];
  include 'include/icaocheck.php';
  
  $dbhost = "localhost";
  $dbname = "landcover";
  $dbuser = "webuser";
  $connection = pg_connect ("host=$dbhost dbname=$dbname user=$dbuser");
  $dbquery = pg_query ("SELECT ST_X(wkb_geometry), ST_Y(wkb_geometry) FROM apt_airfield WHERE icao ILIKE '$icao';");
  $array = pg_fetch_array ($dbquery, 0, PGSQL_NUM);

  $lowerleftx = $array[0] - 0.5;
  $lowerlefty = $array[1] - 0.5;
  $upperrightx = $array[0] + 0.5;
  $upperrighty = $array[1] + 0.5;
  $location = "$lowerleftx+$lowerlefty+$upperrightx+$upperrighty";
  include 'include/defaultlayers.php';
  include 'include/URL.php';
  header("Location: $URL");
?>
