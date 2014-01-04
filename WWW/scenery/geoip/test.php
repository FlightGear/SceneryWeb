<?php
    $co = apache_getenv("GEOIP_COUNTRY_CODE");
    $lon = apache_getenv("GEOIP_LONGITUDE");
    $lat = apache_getenv("GEOIP_LATITUDE");
echo "
<html>
    <head>
        <title>PHP</title>
    </head>
    <body>
        $co, $lon, $lat
    </body>
</html>
";
?>
