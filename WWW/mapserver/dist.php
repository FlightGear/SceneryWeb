<?php
  date_default_timezone_set('CET');
  $icao = $_GET["icao1"];
  include 'include/icaocheck.php';
  $icao1 = $icao;
  $icao = $_GET["icao2"];
  include 'include/icaocheck.php';
  $icao2 = $icao;
# Connect
  $dbhost = "geoscope.optiputer.net";
  $dbname = "landcover";
  $dbuser = "webuser";
  $connection = pg_connect ("host=$dbhost dbname=$dbname user=$dbuser");
# Distanz abholen
  $dbquery = pg_query ("
    SELECT (ST_Distance_Spheroid(
      (SELECT wkb_geometry FROM apt_airfield WHERE icao ILIKE '$icao1'),
      (SELECT wkb_geometry FROM apt_airfield WHERE icao ILIKE '$icao2'),
      'SPHEROID[\"WGS84\",6378137.000,298.257223563]'
    )/1000)::decimal(9,3) AS Km;
  ");
# Ergebnis abholen
  $array = pg_fetch_array ($dbquery, 0, PGSQL_NUM);
  $distance_km = $array[0];
  # "International Nautical Mile" of 1929: 1,85201
  # DIN 1301-1:                            1,852    (exact !)
  # Aviation:                              1,85185  !?!?!?
  # Convert to NM and round afterwards ....
  $distance_nm = $distance_km / 1.85201;
  $distance_km = round ($distance_km,1);
  $distance_nm = round ($distance_nm,1);
  print ("<p><h3>Never use this for real life navigation, the data might be wrong !!!</h3</p>");
  print ("<p>The distance between $icao1 and $icao2, according to WGS84, is <b>$distance_km</b> km alias <b>$distance_nm</b> nm.</p>");
  include 'include/tablehead.php';

  print ("      <form method=\"get\" action=\"/dist.php\">");
  print ("      <tr>");
  print ("        <td>  ICAO:</td><td> <input type=\"text\" size=\"4\" maxlength=\"4\" name=\"icao1\"></td>");
  print ("        <td>  ICAO:</td><td> <input type=\"text\" size=\"4\" maxlength=\"4\" name=\"icao2\"></td>");
#  print ("        <td>  <input type=\"submit\" name=\"senden\" value=\"Distance\">  between two airfields</td>");
  print ("        <td>  <input type=\"submit\" value=\"Distance\">  between two airfields</td>");
  print ("      </tr>");
  print ("      </form>");

  print ("    </table></p>");
  print ("<p><a href=\"/\">Back</a> to the intro page.</p>");
?>
