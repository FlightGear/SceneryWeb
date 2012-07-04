<HTML>
  <HEAD>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAVLFq91rDGGNi1LlKdN1PxBR0Q4haDqJCswRe1MDQbYGWGgDI3xTCcUDGymGT0ezb2XnDp9Yx3wF9Kw"></script>
    <script src="http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=euzuro-openlayers"></script>
<!--    <link rel="stylesheet" href="http://www.openlayers.org/dev/theme/default/style.css" type="text/css" />
    <link rel="stylesheet" href="http://www.openlayers.org/dev/examples/style.css" type="text/css" /> -->

    <style type="text/css">
      #map {
        width: 100%;
        height: 100%;
      }
    </style>

<!--    <script src="http://www.openlayers.org/dev/OpenLayers.js"></script> -->
    <script src="/ol/OpenLayers.js"></script>
<!--    <script src="../openlayers-ys/OpenLayers.js"></script> -->
    <script src="http://www.openstreetmap.org/openlayers/OpenStreetMap.js"></script>

    <script src='http://mapserver.flightgear.org/map/maplayers.js' type='text/javascript'></script>

    <script type="text/javascript">
<?php
    include_once("geoipcity.inc");
    $gi = geoip_open("/home/fgscenery/GeoIP/GeoLiteCity.dat",GEOIP_STANDARD);
    $girecord = geoip_record_by_addr($gi,$_SERVER['REMOTE_ADDR']);
    geoip_close($gi);
?>

//      var lon = <?php print $_REQUEST["lon"]; ?>;
//      var lat = <?php print $_REQUEST["lat"]; ?>;
      var lon = <?php print $girecord->longitude; ?>;
      var lat = <?php print $girecord->latitude; ?>;
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
        map = new OpenLayers.Map('map', options);

	tarmac.setVisibility(false);
	sceneobject.setVisibility(false);
	map.addLayers([customscene, v0cover, icubed, tarmac, osmlines, airport850, sceneobject]);

	map.addControl(new OpenLayers.Control.PanZoom());
	map.addControl(new OpenLayers.Control.Permalink('permalink'));
	map.addControl(new OpenLayers.Control.MouseDefaults());
	var ll = new OpenLayers.LonLat(lon, lat), zoom;
	ll.transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913"));
	map.setCenter(ll);
      }

    </script>
  </HEAD>

  <BODY style='margin: 0px;' onload="init()" bgcolor=#FFFFFF>
    <div style=" width:100%; heigth:100%;" id="map"></div>
  </BODY>

</HTML>
