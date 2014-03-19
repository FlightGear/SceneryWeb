<?php
  $icao = $_GET["icao"];
  include 'include/icaocheck.php';

  $dbhost = "localhost";
  $dbname = "landcover";
  $dbuser = "webuser";
  $connection = pg_connect ("host=$dbhost dbname=$dbname user=$dbuser");
  $dbquery = pg_query ("SELECT ST_X(wkb_geometry), ST_Y(wkb_geometry) FROM apt_airfield WHERE icao ILIKE '$icao';");
  $array = pg_fetch_array ($dbquery, 0, PGSQL_NUM);
  $lon = $array[0];
  $lat = $array[1];
echo <<< EOP
  <html>
    <head>
      <title>LON/LAT-location of airfeld $icao</title>
    </head>
    <body>
    $lon/$lat
    </body>
  </html>
EOP;
?>
