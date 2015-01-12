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

        var lon = <?php print $_REQUEST["lon"]; ?>;
        var lat = <?php print $_REQUEST["lat"]; ?>;
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
                    new OpenLayers.Control.Attribution(),
                    new OpenLayers.Control.Permalink('permalink')
                ]
            };
            OpenLayers.IMAGE_RELOAD_ATTEMPTS = 1;
            map = new OpenLayers.Map('map', options);

            map.addLayers([customscene, osmlines, airfield]);

            var ll = new OpenLayers.LonLat(lon, lat), zoom;
            ll.transform(projLonLat, projMercator);
            map.setCenter(ll);
        }
        </script>
    </head>

    <body style='margin: 0px;' onload="init()" bgcolor="#FFFFFF">
        <div style=" width:100%; heigth:100%;" id="map"></div>
    </body>
</html>
