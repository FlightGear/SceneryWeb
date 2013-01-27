<html>
    <head>

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

        <?php include("../map/addons.php"); ?>

        <script type="text/javascript">
        <!--
        <?php
            include_once("geoipcity.inc");
            $gi = geoip_open("/home/fgscenery/GeoIP/GeoLiteCity.dat",GEOIP_STANDARD);
            $girecord = geoip_record_by_addr($gi,$_SERVER['REMOTE_ADDR']);
            geoip_close($gi);
        ?>

//        var lon = <?php print $_REQUEST["lon"]; ?>;
//        var lat = <?php print $_REQUEST["lat"]; ?>;
        var lon = <?php print $girecord->longitude; ?>;
        var lat = <?php print $girecord->latitude; ?>;
        var zoom = <?php print $_REQUEST["zoom"]; ?>;
        var map;

        function init() {
            var options = {
                projection: new OpenLayers.Projection("EPSG:900913"),
                displayProjection: new OpenLayers.Projection("EPSG:4326"),
                units: "m",
                controls: [],
                maxResolution: 156543.0339,
                maxExtent: new OpenLayers.Bounds(-20037508.34, -20037508.34, 20037508.34, 20037508.34)
            };
            map = new OpenLayers.Map('map', options);

            tarmac.setVisibility(false);
            sceneobject.setVisibility(false);
            map.addLayers([customscene, v0cover, icubed, tarmac, osmlines, airfield, sceneobject]);

            map.addControl(new OpenLayers.Control.PanZoom());
            map.addControl(new OpenLayers.Control.Attribution());
            map.addControl(new OpenLayers.Control.Permalink('permalink'));
            map.addControl(new OpenLayers.Control.MouseDefaults());
            var ll = new OpenLayers.LonLat(lon, lat), zoom;
            ll.transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913"));
            map.setCenter(ll);
        }
        //-->
        </script>
    </head>

    <body style='margin: 0px;' onload="init()" bgcolor="#FFFFFF">
        <div style=" width:100%; heigth:100%;" id="map"></div>
    </body>

</html>
