<html>
<head>
<title>Model Position</title>
<style type="text/css">
    .olFramedCloudPopupContent { padding: 5px; }
        .olPopup p { margin:0px; font-size: .9em;}
        h2 { margin:0px; font-size: 1.2em;}
</style>
<script type="text/javascript" src="http://www.openlayers.org/api/OpenLayers.js"></script>
<script src="http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=euzuro-openlayers"></script>
</head>

<script type="text/javascript">
<!--
        var lon = <?php print $_REQUEST["lon"]; ?>;
        var lat = <?php print $_REQUEST["lat"];?>;
        var zoom = <?php print $_REQUEST["zoom"];?>;
	var map;
    function get_osm_url (bounds) {
        var res = this.map.getResolution();
        var x = Math.round ((bounds.left - this.maxExtent.left) / (res * this.tileSize.w));
        var y = Math.round ((this.maxExtent.top - bounds.top) / (res * this.tileSize.h));
        var z = this.map.getZoom();
   var limit = Math.pow(2, z);
   if (y < 0 || y >= limit)
   {
     return null;
   }
   else
   {
     x = ((x % limit) + limit) % limit;

        var path = z + "/" + x + "/" + y + "." + this.type; 
        var url = this.url;
        if (url instanceof Array) {
            url = this.selectUrl(path, url);
        }
        return url + path;
        }
    }


    function init() {

        OpenLayers.Util.onImageLoadError = function() {
           this.src='http://www.informationfreeway.org/images/emptysea.png'
        }
	map = new OpenLayers.Map ("map", {
	    displayProjection: new OpenLayers.Projection("EPSG:4326"),
            controls:[
		new OpenLayers.Control.Permalink(),
		new OpenLayers.Control.MouseDefaults(),
		new OpenLayers.Control.LayerSwitcher(),
		new OpenLayers.Control.MousePosition(),
		new OpenLayers.Control.PanZoomBar(),
		new OpenLayers.Control.ScaleLine()],
	    maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
	    numZoomLevels:18, maxResolution:156543.0339, units:'m', projection: "EPSG:900913"} );

                var yahoosat = new OpenLayers.Layer.Yahoo( "Yahoo Satellite (License!?)",
                  {type: YAHOO_MAP_SAT, 'sphericalMercator': true, numZoomLevels: 20}
                );

                var icubed = new OpenLayers.Layer.WMS( "Telascience i-Cubed", 
                          [ "http://t1.hypercube.telascience.org/tiles?",
                          "http://t2.hypercube.telascience.org/tiles?",
                          "http://t3.hypercube.telascience.org/tiles?",
                          "http://t4.hypercube.telascience.org/tiles?" ],
                          {layers: 'landsat7-google', format: 'image/jpeg'} ,
                          {wrapDateLine: true} );

		var tarmac = new OpenLayers.Layer.WMS( "Airfield layouts",
			  [ "http://1.flightgear.telascience.org/tc?",
			  "http://2.flightgear.telascience.org/tc?",
			  "http://3.flightgear.telascience.org/tc?",
			  "http://4.flightgear.telascience.org/tc?" ],
			  {layers: 'tarmac', transparent: 'true', format: 'image/png'},
			  {isBaseLayer: false} );

                var wmssigns = new OpenLayers.Layer.WMS( "WMS Taxiway Signs",
                          [ "http://1.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
                          "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
                          "http://3.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
                          "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
                          {layers: 'fgs_signs', transparent: 'true', format: 'image/png'},
                          {isBaseLayer: false, maxScale: 5000 });

                var wfssigns = new OpenLayers.Layer.WFS( "WFS Taxiway Signs",
                          "http://scenemodels.flightgear.org/ms?srs=EPSG%3A900913&",
                          { typename: "fgs_signs", maxfeatures: 200},
                          { minScale: 5000 });

                var osmlines = new OpenLayers.Layer.WMS( "Line features",
                          [ "http://1.flightgear.telascience.org/tc?",
                          "http://2.flightgear.telascience.org/tc?",
                          "http://3.flightgear.telascience.org/tc?",
                          "http://4.flightgear.telascience.org/tc?" ],
                          {layers: 'osmlines', transparent: 'true', format: 'image/png'},
                          {isBaseLayer: false} );

	var mapnik = new OpenLayers.Layer.TMS( 
		"mapnik",
		"http://tile.openstreetmap.org/mapnik/",
		{type:'png', getURL: get_osm_url, displayOutsideMaxExtent: true, 'buffer':1, transitionEffect: 'resize'} );

	var osmarender = new OpenLayers.Layer.TMS( 
		"osmarender", 
		["http://a.tah.openstreetmap.org/Tiles/tile/", "http://b.tah.openstreetmap.org/Tiles/tile/", "http://c.tah.openstreetmap.org/Tiles/tile/"],
		{type:'png', getURL: get_osm_url, displayOutsideMaxExtent: true, 'buffer':1, transitionEffect: 'resize'} );

        var wmsobjects = new OpenLayers.Layer.WMS( "Scenery Objects",
                [ "http://1.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
                "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
                "http://3.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
                "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
                {layers: 'fgs_staticobjects,fgs_sharedobjects', transparent: 'true', format: 'image/png'},
                {isBaseLayer: false, maxScale: 12500 });
        
        var objects = new OpenLayers.Layer.Vector("Scenery Object Details",
                {	strategies: [new OpenLayers.Strategy.BBOX({resFactor: 1.1})],
                        protocol: new OpenLayers.Protocol.HTTP({
                          url: "geojson.php",
                          format: new OpenLayers.Format.GeoJSON()
                        }),
                        styleMap: new OpenLayers.StyleMap({
                          "default": {
                            externalGraphic: "arrow.png",
                            graphicWidth: 14,
                            graphicHeight: 15,
                            rotation: "${heading}"
                          },
                        }), 
                        projection: new OpenLayers.Projection("EPSG:4326"),
                        visibility: true,
                        minScale: 12500
        });
        map.addLayers([yahoosat, mapnik, osmarender, tarmac, osmlines, wmsobjects, objects, wmssigns, wfssigns]);

        objects.events.on({
          'featureselected': onFeatureSelect,
          'featureunselected': onFeatureUnselect
        });

        selectControl = new OpenLayers.Control.SelectFeature(
          [objects],
          {
            clickout: true, toggle: true,
            multiple: true, hover: false,
            toggleKey: "ctrlKey", // ctrl key removes from selection
            multipleKey: "shiftKey" // shift key adds to selection
          }
        );
        map.addControl(selectControl);
        selectControl.activate();
        map.addControl(new OpenLayers.Control.LayerSwitcher());
        map.addControl(new OpenLayers.Control.KeyboardDefaults());

	if (!map.getCenter()) {
            map.setCenter (new OpenLayers.LonLat(lon, lat).transform(map.displayProjection, map.getProjectionObject()), zoom);
        }    

                                                                                                                                
    }
    // Needed only for interaction, not for the display.
    function onPopupClose(evt) {
      // 'this' is the popup.
      selectControl.unselect(this.feature);
    }
    function onFeatureSelect(evt) {
      feature = evt.feature;
      popup = new OpenLayers.Popup.FramedCloud("featurePopup",
                      feature.geometry.getBounds().getCenterLonLat(),
                      new OpenLayers.Size(100,100),
                      "<h2>"+feature.attributes.title + "</h2>" +
                      feature.attributes.description,
                      null, true, onPopupClose);
      feature.popup = popup;
      popup.feature = feature;
      map.addPopup(popup);
    }
    function onFeatureUnselect(evt) {
      feature = evt.feature;
      if (feature.popup) {
        popup.feature = null;
        map.removePopup(feature.popup);
        feature.popup.destroy();
        feature.popup = null;
      }
    }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     
//-->
</script>
<body style='margin: 0px;' onload="init();">
  <div style=" width:100%; height:100%;" id="map"></div>
</body>
</html>
