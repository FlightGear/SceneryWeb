<HTML>
  <HEAD>
    <TITLE>FlightGear MapServer OpenLayers Demo</TITLE>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <meta name="robots" content="index, nofollow" />
    <meta name="keywords" content="VMap0, VMap1, GSHHS, PGS, SWBD, DAFIF, ICAO, PostgreSQL, PostGIS, Mapserver, OGC, FlightGear, OSGeo, TelaScience" />
    <meta name="description" content="TelaScience / OSGeo / FlightGear Landcover / land use database consisting of different datasets fom VMap0, VMap1, GSHHS, PGS, SWBD and NIMA DAFIF Airfield database." />

    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAVLFq91rDGGNi1LlKdN1PxBR0Q4haDqJCswRe1MDQbYGWGgDI3xTCcUDGymGT0ezb2XnDp9Yx3wF9Kw"></script>
    <script src="http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=euzuro-openlayers"></script>
<!--    <script src='http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1'></script> -->
<!--    <link rel="stylesheet" href="http://www.openlayers.org/dev/theme/default/style.css" type="text/css" />
    <link rel="stylesheet" href="http://www.openlayers.org/dev/examples/style.css" type="text/css" /> -->

    <style type="text/css">
      #map {
        width: 100%;
        height: 100%;
      }
    </style>

    <script src="http://www.openlayers.org/dev/OpenLayers.js"></script>
<!--    <script src="../OpenLayers-2.10/OpenLayers.js"></script> -->
<!--    <script src="../openlayers-ys/OpenLayers.js"></script> -->
    <script src="http://www.openstreetmap.org/openlayers/OpenStreetMap.js"></script>
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
        map = new OpenLayers.Map('map', options);

        var googlesat = new OpenLayers.Layer.Google( "Google Satellite (License!!)",
          {type: G_SATELLITE_MAP, 'sphericalMercator': true, numZoomLevels: 20}
        );

        var yahoosat = new OpenLayers.Layer.Yahoo( "Yahoo Satellite (License!?)",
          {type: YAHOO_MAP_SAT, 'sphericalMercator': true, numZoomLevels: 20}
        );

        var mrsmap = new OpenLayers.Layer.WMS( "MSR Maps Map Server",
          "http://msrmaps.com/ogccapabilities.ashx?",
          {layers: '', transparent: 'true'},
	  {isBaseLayer: true}
        );


//        vearthsat = new OpenLayers.Layer.VirtualEarth( "FIXME: VirtualEarth",
//          { minZoomLevel: 1, maxZoomLevel: 16, 'type': VEMapStyle.Aerial}
//        );

	var customscene = new OpenLayers.Layer.WMS( "Landcover-DB CS Test",
	  [ "http://1.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://3.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
	  {layers: 'cs_herbtundra,cs_heath,cs_barrencover,cs_dirt,cs_rainforest,cs_mixedforest,cs_evergreenforest,cs_deciduousforest,cs_olives,cs_orchard,cs_vineyard,cs_burnt,cs_sclerophyllous,cs_scrub,cs_greenspace,cs_grassland,cs_golfcourse,cs_agroforest,cs_cropgrass,cs_naturalcrop,cs_complexcrop,cs_mixedcrop,cs_rice,cs_irrcrop,cs_drycrop,cs_cemetery,cs_transport,cs_construction,cs_industrial,cs_dump,cs_openmining,cs_lava,cs_rock,cs_saltmarsh,cs_littoral,cs_saline,cs_sand,cs_marsh,cs_bog,cs_estuary,cs_lagoon,cs_floodland,cs_town,cs_suburban,cs_port,cs_urban,cs_polarice,cs_packice,cs_glacier,cs_watercourse,cs_intermittentlake,cs_lake,cs_asphalt,cs_airport', format: 'image/png'},
          {minZoomLevel: 10}
	);

//	var customscene = new OpenLayers.Layer.WMS( "Landcover-DB CS Test",
//	  [ "http://1.flightgear.telascience.org/tc?",
//	    "http://2.flightgear.telascience.org/tc?",
//	    "http://3.flightgear.telascience.org/tc?",
//	    "http://4.flightgear.telascience.org/tc?" ],
//	  {layers: 'customscene', format: 'image/png'}
//	);

	var csdefault = new OpenLayers.Layer.WMS( "Landcover-DB CS Voidfill",
	  [ "http://1.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://3.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
	  {layers: 'cs_default', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var v0cover = new OpenLayers.Layer.WMS( "Landcover-DB VMap0-Vector",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'v0cover', format: 'image/png'}
	);

	var corine = new OpenLayers.Layer.WMS( "Landcover-DB CLC2000v13",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'corine', format: 'image/png'}
	);

	var icubed = new OpenLayers.Layer.WMS( "TelaScience i-Cubed", 
	  [ "http://t1.hypercube.telascience.org/tiles?",
	    "http://t2.hypercube.telascience.org/tiles?",
	    "http://t3.hypercube.telascience.org/tiles?",
	    "http://t4.hypercube.telascience.org/tiles?" ],
	  {layers: 'landsat7-google', format: 'image/jpeg'} ,
	  {wrapDateLine: true}
	);

//	var icubed = new OpenLayers.Layer.WMS( "TelaScience i-Cubed", 
//	  [ "http://1.flightgear.telascience.org/tc?",
//	    "http://2.flightgear.telascience.org/tc?",
//	    "http://3.flightgear.telascience.org/tc?",
//	    "http://4.flightgear.telascience.org/tc?" ],
//	  {layers: 'icubed', format: 'image/jpeg'},
//	  {wrapDateLine: true}
//	);

        var mapnik = new OpenLayers.Layer.OSM.Mapnik( "OSM Mapnik (Attribution!)");

	var tarmac = new OpenLayers.Layer.WMS( "Airfield layouts (polygon)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'tarmac', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var tarmac850 = new OpenLayers.Layer.WMS( "v8.50 Airfield layouts (polygon)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'tarmac850', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

//	var airport850 = new OpenLayers.Layer.WMS( "v8.50 Airfields (symbol)",
//	  "http://map.fgx.ch:81/mapnik/fgxcache.py?",
//	  {layers: 'fgx_850_apt', transparent: 'true', format: 'image/png'},
//	  {isBaseLayer: false}
//	);

	var navaid850 = new OpenLayers.Layer.WMS( "v8.50 Navaids (symbol)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'navaid850', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var osmlines = new OpenLayers.Layer.WMS( "OSM line features",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'osmlines', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
//	  {gutter: 50}
	);

	var osmtarmac = new OpenLayers.Layer.WMS( "OSM Airfield layouts (line)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'osmtarmac', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var cslines = new OpenLayers.Layer.WMS( "VMap0/CS line features",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'cslines', format: 'image/png'},
	  {isBaseLayer: false}
	);

//	var cslines = new OpenLayers.Layer.WMS( "VMap0/CS line features",
//	  [ "http://1.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
//	    "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
//	    "http://3.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
//	    "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
//	  {layers: 'cs_freeway,cs_road,cs_railroad2,cs_railroad1,cs_canal,cs_intermittentstream,cs_stream', format: 'image/png'},
//	  {isBaseLayer: false}
//	);

	var noaroads = new OpenLayers.Layer.WMS( "StatsCan/TIGER roads (line)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'noaroads', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var airfield = new OpenLayers.Layer.WMS( "Airfields (point)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'airfield', format: 'image/png'},
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
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'sceneobject', transparent: 'true', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var gshhs = new OpenLayers.Layer.WMS( "GSHHS coastline (polygon)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'gshhs', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var swbd = new OpenLayers.Layer.WMS( "SWBD coastline (polygon)",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'swbd', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var fgbuckets = new OpenLayers.Layer.WMS( "FG Scenery buckets",
	  [ "http://1.flightgear.telascience.org/tc?",
	    "http://2.flightgear.telascience.org/tc?",
	    "http://3.flightgear.telascience.org/tc?",
	    "http://4.flightgear.telascience.org/tc?" ],
	  {layers: 'fgbuckets', format: 'image/png'},
	  {isBaseLayer: false}
	);

	var osmlinecover = new OpenLayers.Layer.WMS( "OSM line features (polygon, experimental)",
	  [ "http://1.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://3.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	    "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
	  {layers: 'osm_roadcover', format: 'image/png'},
	  {isBaseLayer: false}
	);
	
	tarmac.setVisibility(true);
	tarmac850.setVisibility(false);
	osmtarmac.setVisibility(false);
	cslines.setVisibility(true);
	osmlines.setVisibility(false);
	osmlinecover.setVisibility(false);
	noaroads.setVisibility(false);
	airfield.setVisibility(false);
	airport850.setVisibility(true);
	navaid850.setVisibility(false);
	sceneobject.setVisibility(false);
	gshhs.setVisibility(false);
	fgbuckets.setVisibility(false);
	csdefault.setVisibility(false);

	map.addLayers([customscene, v0cover, yahoosat, googlesat, mapnik, corine, tarmac, tarmac850, osmtarmac, cslines, osmlines, osmlinecover, noaroads, airfield, airport850, navaid850, sceneobject, gshhs, fgbuckets, csdefault]);

	map.addControl(new OpenLayers.Control.LayerSwitcher());
	map.addControl(new OpenLayers.Control.PanZoom());
	map.addControl(new OpenLayers.Control.Permalink('permalink'));
	map.addControl(new OpenLayers.Control.MouseToolbar());
	map.addControl(new OpenLayers.Control.MousePosition({'numDigits': 7}));
	var ll = new OpenLayers.LonLat(lon, lat), zoom;
	ll.transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913"));
	map.setCenter(ll);
      }
    </script>
  </HEAD>

  <BODY style='margin: 0px;' onload="init()" bgcolor=#FFFFFF>
    <div style=" width:100%; heigth:100%;" id="map"></div> 
    <div style="position:absolute; bottom:10px;width:700px;z-index: 2001;" align="center">
      <b><a href="http://mapserver.flightgear.org/">Back</a></b> to the intro page.
    </div>
  </BODY>

</HTML>
