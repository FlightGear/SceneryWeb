<HTML>
    <HEAD>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <meta name="robots" content="index, nofollow" />

        <style type="text/css">
            #map {
                width: 100%;
                height: 100%;
            }
            body {
                font-family: "Lucida Grande", Verdana, Geneva, Lucida, Arial, Helvetica, sans-serif;
                font-size: 0.7em;
            }
            div.olControlAttribution, div.olControlScaleLine {
                bottom: 40px;
            }
        </style>

        <script type="text/javascript" src="/ol/OpenLayers.js"></script>
        <script type="text/javascript" src="http://www.openstreetmap.org/openlayers/OpenStreetMap.js"></script>

        <script type="text/javascript">

        var lon = <?php print $_REQUEST["lon"]; ?>;
        var lat = <?php print $_REQUEST["lat"]; ?>;
        var zoom = <?php print $_REQUEST["zoom"]; ?>;
        var map;

        function init(){
            var options = {
                projection: new OpenLayers.Projection("EPSG:900913"),
                displayProjection: new OpenLayers.Projection("EPSG:4326"),
                units: "m",
                controls: [],
                maxResolution: 156543.0339,
                maxExtent: new OpenLayers.Bounds(-20037508.34, -20037508.34, 20037508.34, 20037508.34)
            };
            OpenLayers.IMAGE_RELOAD_ATTEMPTS = 1;
            map = new OpenLayers.Map('map', options);

            var mapnik = new OpenLayers.Layer.OSM.Mapnik( "OSM Mapnik (ODbL)",
                {'attribution': "&copy; <a href='http://www.openstreetmap.org/copyright'>OpenStreetMap</a> contributors"}
            );
            var markers = new OpenLayers.Layer.Markers( "Markers" );

            map.addLayers([mapnik, markers]);

            var size = new OpenLayers.Size(16,16);
            var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
            var icon = new OpenLayers.Icon('http://wiki.osgeo.org/images/5/57/Usermap_placemark_icon.png', size, offset);
            markers.addMarker(new OpenLayers.Marker(new OpenLayers.LonLat(lon, lat)
                .transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913"))
                ,icon
            ));

            map.addControl(new OpenLayers.Control.PanZoom());
            map.addControl(new OpenLayers.Control.Navigation());
            map.addControl(new OpenLayers.Control.Attribution());
            map.addControl(new OpenLayers.Control.Permalink('permalink'));
            var ll = new OpenLayers.LonLat(lon, lat), zoom;
            ll.transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913"));
            map.setCenter(ll);

        }
        //-->
        </script>
    </HEAD>

    <BODY style='margin: 0px;' onload="init()" bgcolor=#FFFFFF>
        <div style=" width:100%; height:100%;" id="map"></div>
    </BODY>

</HTML>
