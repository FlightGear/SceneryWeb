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


//    vearthsat = new OpenLayers.Layer.VirtualEarth( "FIXME: VirtualEarth",
//        { minZoomLevel: 1, maxZoomLevel: 16, 'type': VEMapStyle.Aerial}
//    );

    var customscene = new OpenLayers.Layer.WMS( "Landcover-DB CS Test",
	    [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	      "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
	    {layers: 'cs_herbtundra,cs_heath,cs_barrencover,cs_dirt,cs_rainforest,cs_mixedforest,cs_evergreenforest,cs_deciduousforest,cs_olives,cs_orchard,cs_vineyard,cs_burnt,cs_sclerophyllous,cs_scrub,cs_greenspace,cs_grassland,cs_golfcourse,cs_agroforest,cs_cropgrass,cs_naturalcrop,cs_complexcrop,cs_mixedcrop,cs_rice,cs_irrcrop,cs_drycrop,cs_cemetery,cs_transport,cs_construction,cs_industrial,cs_dump,cs_openmining,cs_lava,cs_rock,cs_saltmarsh,cs_littoral,cs_saline,cs_sand,cs_marsh,cs_bog,cs_estuary,cs_lagoon,cs_floodland,cs_town,cs_suburban,cs_port,cs_urban,cs_polarice,cs_packice,cs_glacier,cs_watercourse,cs_intermittentlake,cs_lake,cs_asphalt,cs_airport', format: 'image/png'},
        {minZoomLevel: 10}
    );

//    var customscene = new OpenLayers.Layer.WMS( "Landcover-DB CS Test",
//	      [ "http://1.flightgear.telascience.org/tc?",
//	        "http://2.flightgear.telascience.org/tc?",
//	        "http://3.flightgear.telascience.org/tc?",
//	        "http://4.flightgear.telascience.org/tc?" ],
//	      {layers: 'customscene', format: 'image/png'}
//    );

    var csdefault = new OpenLayers.Layer.WMS( "Landcover-DB CS Voidfill",
	    [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
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

	var clc00 = new OpenLayers.Layer.WMS( "CORINE CLC2000v15",
	    [ "http://1.flightgear.telascience.org/tc?",
	      "http://2.flightgear.telascience.org/tc?",
	      "http://3.flightgear.telascience.org/tc?",
	      "http://4.flightgear.telascience.org/tc?" ],
	    {layers: 'clc00', format: 'image/png'}
	);

	var clc06 = new OpenLayers.Layer.WMS( "CORINE CLC2006v15",
	    [ "http://1.flightgear.telascience.org/tc?",
	      "http://2.flightgear.telascience.org/tc?",
	      "http://3.flightgear.telascience.org/tc?",
	      "http://4.flightgear.telascience.org/tc?" ],
	    {layers: 'clc06', format: 'image/png'}
	);

	var icubed = new OpenLayers.Layer.WMS( "TelaScience i-Cubed", 
	    [ "http://1.flightgear.telascience.org/tc?",
	      "http://2.flightgear.telascience.org/tc?",
	      "http://3.flightgear.telascience.org/tc?",
	      "http://4.flightgear.telascience.org/tc?" ],
	    {layers: 'icubed', format: 'image/png'},
	    {wrapDateLine: true}
	);

    var mapnik = new OpenLayers.Layer.OSM.Mapnik( "OSM Mapnik (ODbL)");

//    var mapnik = new OpenLayers.Layer.TMS( "mapnik",
//        "http://tile.openstreetmap.org/mapnik/",
//        {type:'png', getURL: get_osm_url, displayOutsideMaxExtent: true, 'buffer':1, transitionEffect: 'resize'}
//    );

    var osmarender = new OpenLayers.Layer.TMS( "osmarender", 
        [ "http://a.tah.openstreetmap.org/Tiles/tile/",
          "http://b.tah.openstreetmap.org/Tiles/tile/",
          "http://c.tah.openstreetmap.org/Tiles/tile/" ],
        {type:'png', getURL: get_osm_url, displayOutsideMaxExtent: true, 'buffer':1, transitionEffect: 'resize'}
    );

    var tarmac = new OpenLayers.Layer.WMS( "Airfield layouts (v8.10)",
	    [ "http://1.flightgear.telascience.org/tc?",
	      "http://2.flightgear.telascience.org/tc?",
	      "http://3.flightgear.telascience.org/tc?",
	      "http://4.flightgear.telascience.org/tc?" ],
	    {layers: 'tarmac', transparent: 'true', format: 'image/png'},
	    {isBaseLayer: false}
	);

    var tarmac850 = new OpenLayers.Layer.WMS( "Airfield layouts (v9.00, 2012.08)",
	    [ "http://1.flightgear.telascience.org/tc?",
	      "http://2.flightgear.telascience.org/tc?",
	      "http://3.flightgear.telascience.org/tc?",
	      "http://4.flightgear.telascience.org/tc?" ],
	    {layers: 'tarmac850', transparent: 'true', format: 'image/png'},
	    {isBaseLayer: false}
	);

    var osmlines = new OpenLayers.Layer.WMS( "OSM line features (121017)",
	    [ "http://1.flightgear.telascience.org/tc?",
	      "http://2.flightgear.telascience.org/tc?",
	      "http://3.flightgear.telascience.org/tc?",
	      "http://4.flightgear.telascience.org/tc?" ],
	    {layers: 'osmlines', transparent: 'true', format: 'image/png'},
	    {isBaseLayer: false}
//    {gutter: 50}
    );

    var osmtarmac = new OpenLayers.Layer.WMS( "OSM Airfield layouts (121017)",
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

//    var cslines = new OpenLayers.Layer.WMS( "VMap0/CS line features",
//	      [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
//	        "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
//	      {layers: 'cs_freeway,cs_road,cs_railroad2,cs_railroad1,cs_canal,cs_intermittentstream,cs_stream', format: 'image/png'},
//	      {isBaseLayer: false}
//    );

    var noaroads = new OpenLayers.Layer.WMS( "StatsCan/TIGER roads (2006se)",
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

    var airport850 = new OpenLayers.Layer.WMS( "v8.50 Airfields (FGx)",
	      "http://map.fgx.ch:81/mapnik/fgxcache.py?",
	      {layers: 'fgx_850_apt', transparent: 'true', format: 'image/png'},
	      {isBaseLayer: false}
    );

//    var airport850 = new OpenLayers.Layer.WMS( "v8.50 Airfields (FGx)",
//	    [ "http://1.flightgear.telascience.org/tc?",
//	      "http://2.flightgear.telascience.org/tc?",
//	      "http://3.flightgear.telascience.org/tc?",
//	      "http://4.flightgear.telascience.org/tc?" ],
//	    {layers: 'airport850', transparent: 'true', format: 'image/png'},
//	    {isBaseLayer: false}
//	);

    var navaid850 = new OpenLayers.Layer.WMS( "v8.50 Navaids (FGx)",
	      "http://map.fgx.ch:81/mapnik/fgxcache.py?",
	      {layers: 'fgx_850_vor,fgx_850_dme,fgx_850_ndb', transparent: 'true', format: 'image/png'},
	      {isBaseLayer: false}
    );

//    var navaid850 = new OpenLayers.Layer.WMS( "v8.50 Navaids (FGx)",
//	    [ "http://1.flightgear.telascience.org/tc?",
//	      "http://2.flightgear.telascience.org/tc?",
//	      "http://3.flightgear.telascience.org/tc?",
//	      "http://4.flightgear.telascience.org/tc?" ],
//	    {layers: 'navaid850', transparent: 'true', format: 'image/png'},
//	    {isBaseLayer: false}
//	);

    var sceneobject = new OpenLayers.Layer.WMS( "Scenery Objects (point)",
	    [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	      "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
	    {layers: 'fgs_staticobjects,fgs_sharedobjects', transparent: 'true', format: 'image/png'},
	    {isBaseLayer: false}
	);

//    var sceneobject = new OpenLayers.Layer.WMS( "Scenery Objects (point)",
//	      [ "http://1.flightgear.telascience.org/tc?",
//	        "http://2.flightgear.telascience.org/tc?",
//	        "http://3.flightgear.telascience.org/tc?",
//	        "http://4.flightgear.telascience.org/tc?" ],
//	      {layers: 'sceneobject', transparent: 'true', format: 'image/png'},
//	      {isBaseLayer: false}
//    );

    var wmssigns = new OpenLayers.Layer.WMS( "WMS Taxiway Signs",
        [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
          "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
        {layers: 'fgs_signs', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false, maxScale: 5000 }
    );

    //TODO This is temporary code    
    var wfssigns = new OpenLayers.Layer.WMS( "WMS Taxiway Signs",
        [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
          "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
        {layers: 'fgs_signs', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false, minScale: 5000 }
    );

// This code is buggy
//    var wfssigns = new OpenLayers.Layer.WFS( "WFS Taxiway Signs",
//        [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
//          "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
//        { typename: "fgs_signs", maxfeatures: 200},
//        { minScale: 5000 }
//    );

    var wmsobjects = new OpenLayers.Layer.WMS( "Scenery Objects",
        [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
          "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
        {layers: 'fgs_staticobjects,fgs_sharedobjects', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false, maxScale: 12500 }
    );
    
    var jsonobjects = new OpenLayers.Layer.Vector("Scenery Object Details",{
        strategies: [new OpenLayers.Strategy.BBOX()],
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
            }
        }), 
        projection: new OpenLayers.Projection("EPSG:4326"),
        visibility: true,
        minScale: 12500 }
    );

    var gshhs = new OpenLayers.Layer.WMS( "GSHHS coastline (1.6)",
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
	    [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
	      "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
	    {layers: 'osm_roadcover', format: 'image/png'},
	    {isBaseLayer: false}
	);
