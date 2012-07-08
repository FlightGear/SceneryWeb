<?php
  date_default_timezone_set('CET');
  $icao = $_POST[icao];
  include 'include/icaocheck.php';
  
  $dbhost = "geoscope.optiputer.net";
  $dbname = "landcover";
  $dbuser = "webuser";
  $connection = pg_connect ("host=$dbhost dbname=$dbname user=$dbuser");
  $dbquery = pg_query ("SELECT ST_X(wkb_geometry), ST_Y(wkb_geometry) FROM apt_airfield WHERE icao ILIKE '$icao';");
  $array = pg_fetch_array ($dbquery, 0, PGSQL_NUM);

  header("Location: http://mapserver.flightgear.org/map/?lon=$array[0]&lat=$array[1]&zoom=12");
?>
