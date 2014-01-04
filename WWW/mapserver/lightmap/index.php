<html>
    <head>
        <title>Title Map</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <meta name="robots" content="index, nofollow" />

        <style type="text/css">
            #map {
                width: 100%;
                height: 100%;
            }
            body {
                font-family: "Lucida Grande", Verdana, Geneva, Lucida, Arial, Helvetica, sans-serif;
                font-size: 0.8em;
            }
            .olControlAttribution, .olControlScaleLine {
                bottom: 40px;
            }
        </style>

        <?php include("../map/basics.php"); ?>

        <script type="text/javascript">
        <!--
//        <?php
//            include_once("geoipcity.inc");
//            $gi = geoip_open("/home/fgscenery/GeoIP/GeoLiteCity.dat",GEOIP_STANDARD);
//            $girecord = geoip_record_by_addr($gi,$_SERVER['REMOTE_ADDR']);
//            geoip_close($gi);
//        ?>

//        var lon = <?php print $_REQUEST["lon"]; ?>;
//        var lat = <?php print $_REQUEST["lat"]; ?>;
//        var lon = <?php print $girecord->longitude; ?>;
//        var lat = <?php print $girecord->latitude; ?>;
        var lon = <?php print apache_note("GEOIP_LONGITUDE"); ?>;
        var lat = <?php print apache_note("GEOIP_LATITUDE"); ?>;
        var zoom = <?php print $_REQUEST["zoom"]; ?>;
        var map;

        projLonLat   = new OpenLayers.Projection("EPSG:4326");    // WGS84
        projMercator = new OpenLayers.Projection("EPSG:900913");  // Google Spherical Mercator

        function init() {
            var options = {
                projection: projMercator,
                displayProjection: projLonLat,
                units: "m",
                maxResolution: 156543.0339,
                maxExtent: new OpenLayers.Bounds(-20037508.34, -20037508.34, 20037508.34, 20037508.34),
                controls:[
                    new OpenLayers.Control.PanZoom(),
                    new OpenLayers.Control.Attribution(),
                    new OpenLayers.Control.Permalink('permalink'),
                    new OpenLayers.Control.MouseDefaults()
                ],
            };
            OpenLayers.IMAGE_RELOAD_ATTEMPTS = 1;
            map = new OpenLayers.Map('map', options);

            tarmac.setVisibility(false);
            sceneobject.setVisibility(false);
            map.addLayers([customscene, v0cover, icubed, tarmac, osmlines, airfield, sceneobject]);

//            if (!map.getCenter()) {
                var ll = new OpenLayers.LonLat(lon, lat), zoom;
                ll.transform(projLonLat, projMercator);
                map.setCenter(ll);
//            }
        }
        </script>
    </head>

    <body style='margin: 0px;' onload="init()" bgcolor="#FFFFFF">
        <div style=" width:100%; heigth:100%;" id="map"></div>
    </body>

</html>
