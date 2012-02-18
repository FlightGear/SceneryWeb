<HTML>
  <HEAD>
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
    <script type="text/javascript">

<?php
    include_once("geoipcity.inc");
    $gi = geoip_open("/home/martin/GeoIP/GeoLiteCity.dat",GEOIP_STANDARD);
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

//	var customscene = new OpenLayers.Layer.WMS( "Landcover-DB CS Test",
//	  [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
//	    "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
//	  {layers: 'cs_lake,cs_intermittentlake,cs_rock,cs_dirt,cs_openmining,cs_airport,cs_asphalt,cs_industrial,cs_town,cs_suburban,cs_urban,cs_deciduousforest,cs_evergreenforest,cs_mixedforest,cs_sand,cs_floodland,cs_littoral,cs_lava,cs_golfcourse,cs_greenspace,cs_grassland,cs_scrub,cs_herbtundra,cs_glacier,cs_packice,cs_polarice,cs_marsh,cs_bog,cs_barrencover,cs_cropgrass,cs_mixedcrop,cs_drycrop,cs_irrcrop,cs_stream,cs_intermittentstream,cs_canal,cs_road,cs_freeway', format: 'image/png'},
//          { minZoomLevel: 10}
//	);

	var customscene = new OpenLayers.Layer.WMS( "Landcover-DB CS Test",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'customscene', format: 'image/png'}
	);

	var v0cover = new OpenLayers.Layer.WMS( "Landcover-DB VMap0-Vector",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'v0cover', format: 'image/png'}
	);

	var icubed = new OpenLayers.Layer.WMS( "TelaScience i-Cubed", 
	  [ "http://t1.hypercube.telascience.org/tiles?",
	    "http://t2.hypercube.telascience.org/tiles?",
	    "http://t3.hypercube.telascience.org/tiles?",
	    "http://t4.hypercube.telascience.org/tiles?" ],
	  {layers: 'landsat7-google', format: 'image/jpeg'} ,
	  {wrapDateLine: true}
	);

	var tarmac = new OpenLayers.Layer.WMS( "Airfield layouts (polygon)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'tarmac', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var osmlines = new OpenLayers.Layer.WMS( "OSM roads (line)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'osmlines', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var airport850 = new OpenLayers.Layer.WMS( "v8.50 Airfields (symbol)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'airport850', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var sceneobject = new OpenLayers.Layer.WMS( "Scenery Objects (point)",
	  [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
	  {layers: 'fgs_objects', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

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
