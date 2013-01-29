<html>
    <head>
        <title>OSGeo User Map</title>
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

        var lon = <?php print $_REQUEST["lon"]; ?>;
        var lat = <?php print $_REQUEST["lat"]; ?>;
        var zoom = <?php print $_REQUEST["zoom"]; ?>;
        var name = <?php
            if (isset($_REQUEST["name"])) {
                print $_REQUEST["name"];
            }
            else {
                print "\"unknown\"";
            }
        ?>;
        var place = <?php
            if (isset($_REQUEST["place"])) {
                print $_REQUEST["place"];
            }
            else {
                print "\"unknown\"";
            }
        ?>;
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
                    new OpenLayers.Control.Navigation()
                ],
            };
            OpenLayers.IMAGE_RELOAD_ATTEMPTS = 1;
            map = new OpenLayers.Map('map', options);

            var markers = new OpenLayers.Layer.Markers( "Markers" );

            map.addLayers([mapnik, markers]);

            var size = new OpenLayers.Size(16,16);
            var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
            var icon = new OpenLayers.Icon('http://wiki.osgeo.org/images/5/57/Usermap_placemark_icon.png', size, offset);

            var lonLatMarker = new OpenLayers.LonLat(lon, lat).transform(projLonLat, projMercator);;
            var feature = new OpenLayers.Feature(markers, lonLatMarker);
            feature.closeBox = true;
            feature.popupClass =  OpenLayers.Class(OpenLayers.Popup.FramedCloud, {
                'autoSize': true,
                'maxSize': new OpenLayers.Size(300,200)
            });
            popupContentHTML = ("Name: " + name + "<br/>Location: " + place + "<br/>Coordinates: " + lon + ", " + lat);
            feature.data.popupContentHTML = popupContentHTML.replace(/ä/, "&auml;").replace(/ö/, "&ouml;").replace(/ü/, "&uuml;").replace(/ß/, "&szlig;");
            feature.data.overflow = "auto";

            var marker = new OpenLayers.Marker(lonLatMarker, icon);
            marker.feature = feature;
            

            markerClick = function (evt) {
                if (this.popup == null) {
                    this.popup = this.createPopup(this.closeBox);
                    map.addPopup(this.popup);
                    this.popup.show();
                } else {
                    this.popup.toggle();
                }
                currentPopup = this.popup;
                OpenLayers.Event.stop(evt);
            };
            marker.events.register("mousedown", feature, markerClick);

            markers.addMarker(marker);

            if (!map.getCenter()) {
                var ll = new OpenLayers.LonLat(lon, lat), zoom;
                ll.transform(projLonLat, projMercator);
                map.setCenter(ll);
            }
        }
        </script>
    </head>

    <body style='margin: 0px;' onload="init()" bgcolor=#FFFFFF>
        <div style=" width:100%; height:100%;" id="map"></div>
    </body>

</html>
