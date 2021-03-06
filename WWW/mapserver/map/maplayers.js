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

    var BingKey = "Ajtevu0iL__zuuZp7ot9Uwu-j470VZfLsZKAV6NWkIDU4_dRAq51rr7rzBquQtKs"

    var googlesat = new OpenLayers.Layer.Google( "Google Satellite (License!!)",
        {type: google.maps.MapTypeId.SATELLITE, 'sphericalMercator': true, numZoomLevels: 22}
    );

//    var yahoosat = new OpenLayers.Layer.Yahoo( "Yahoo Satellite (License!?)",
//        {type: YAHOO_MAP_SAT, 'sphericalMercator': true, numZoomLevels: 20}
//    );

    var bingaerial = new OpenLayers.Layer.Bing(
        {name: "Bing Aerial (License!!)", key: BingKey, type: "Aerial"}
    );

    var bingroad = new OpenLayers.Layer.Bing(
        {name: "Bing Road (License!!)", key: BingKey, type: "Road"}
    );

    var mrsmap = new OpenLayers.Layer.WMS( "MSR Maps Map Server",
        "http://msrmaps.com/ogccapabilities.ashx?",
        {layers: '', transparent: 'true'},
        {isBaseLayer: true}
    );


//    vearthsat = new OpenLayers.Layer.VirtualEarth( "FIXME: VirtualEarth",
//        {minZoomLevel: 1, maxZoomLevel: 16, 'type': VEMapStyle.Aerial}
//    );

    var clc00 = new OpenLayers.Layer.WMS( "CORINE CLC2000v16",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'clc00', format: 'image/png'},
        {attribution: "&copy; <a href='http://www.eea.europa.eu/legal/copyright'>EEA</a>"}
    );

    var clc06 = new OpenLayers.Layer.WMS( "CORINE CLC2006v16",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'clc06', format: 'image/png'},
        {attribution: "&copy; <a href='http://www.eea.europa.eu/legal/copyright'>EEA</a>"}
    );

    var osmarender = new OpenLayers.Layer.TMS( "osmarender",
        [ "http://a.tah.openstreetmap.org/Tiles/tile/",
          "http://b.tah.openstreetmap.org/Tiles/tile/",
          "http://c.tah.openstreetmap.org/Tiles/tile/" ],
        {type:'png', getURL: get_osm_url, displayOutsideMaxExtent: true, 'buffer':1, transitionEffect: 'resize'}
    );

    var nlcd2011r = new OpenLayers.Layer.WMS( "NLCD 2011 30m raster",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'nlcd2011r', format: 'image/png'},
        {attribution: "Origin: <a href='http://www.mrlc.gov/nlcd2011.php'>MRLC</a>"}
    );

    var clc06v16r = new OpenLayers.Layer.WMS( "CLC2006v16 100m raster",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'clc06v16r', format: 'image/png'},
        {attribution: "&copy; <a href='http://www.eea.europa.eu/legal/copyright'>EEA</a>"}
    );

    var vfpr = new OpenLayers.Layer.WMS( "VFP 3\" DEM",
        "http://2.flightgear.telascience.org/mc?",
        {layers: 'vfpr', format: 'image/png'},
        {attribution: "&copy; <a href='http://www.viewfinderpanoramas.org/dem3.html'>VFP</a>"}
    );

    var tarmac810 = new OpenLayers.Layer.WMS( "Airfield layouts (v8.10)",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'tarmac810', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false}
    );

    var tarmac900 = new OpenLayers.Layer.WMS( "Airfield layouts (v9.00)",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'tarmac900', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false}
    );

    var osmtarmac = new OpenLayers.Layer.WMS( "OSM Airfield layouts (#CURRENTOSMVERSION#)",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'osmtarmac', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false}
    );

    var airspaces = new OpenLayers.Layer.WMS( "Airspaces (experimental)",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'airspaces', format: 'image/png'},
        {isBaseLayer: false}
    );

//    var airport850 = new OpenLayers.Layer.WMS( "v8.50 Airfields (FGx)",
//          "http://map.fgx.ch:81/mapnik/fgxcache.py?",
//          {layers: 'fgx_850_apt', transparent: 'true', format: 'image/png'},
//          {isBaseLayer: false}
//    );

//    var navaid850 = new OpenLayers.Layer.WMS( "v8.50 Navaids (FGx)",
//      "http://map.fgx.ch:81/mapnik/fgxcache.py?",
//      {layers: 'fgx_850_vor,fgx_850_dme,fgx_850_ndb', transparent: 'true', format: 'image/png'},
//      {isBaseLayer: false}
//);

    var wmssigns = new OpenLayers.Layer.WMS( "WMS Taxiway Signs",
        [ "http://flightgear.telascience.org/ms?srs=EPSG%3A900913&",
          "http://mapserver.flightgear.org/ms?srs=EPSG%3A900913&" ],
        {layers: 'fgs_signs', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false, maxScale: 5000 }
    );

    //TODO This is temporary code
    var wfssigns = new OpenLayers.Layer.WMS( "WMS Taxiway Signs",
        [ "http://flightgear.telascience.org/ms?srs=EPSG%3A900913&",
          "http://mapserver.flightgear.org/ms?srs=EPSG%3A900913&" ],
        {layers: 'fgs_signs', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false, minScale: 5000 }
    );

// This code is buggy
//    var wfssigns = new OpenLayers.Layer.WFS( "WFS Taxiway Signs",
//        [ "http://flightgear.telascience.org/ms?srs=EPSG%3A900913&",
//          "http://mapserver.flightgear.org/ms?srs=EPSG%3A900913&" ],
//        { typename: "fgs_signs", maxfeatures: 200},
//        { minScale: 5000 }
//    );

    var wmsobjects = new OpenLayers.Layer.WMS( "Scenery Objects",
        [ "http://flightgear.telascience.org/ms?srs=EPSG%3A900913&",
          "http://mapserver.flightgear.org/ms?srs=EPSG%3A900913&" ],
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

    var osmcoast = new OpenLayers.Layer.WMS( "OSMCoastline",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'osmcoast', format: 'image/png'},
        {isBaseLayer: false}
    );

    var gshhs = new OpenLayers.Layer.WMS( "GSHHS coastline (1.6)",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'gshhs', format: 'image/png'},
        {isBaseLayer: false}
    );

    var swbd = new OpenLayers.Layer.WMS( "SWBD coastline (polygon)",
        [ "http://flightgear.telascience.org/tc?",
          "http://flightgear.telascience.org/tc?" ],
        {layers: 'swbd', format: 'image/png'},
        {isBaseLayer: false}
    );

    var fgbuckets = new OpenLayers.Layer.WMS( "FG Scenery buckets",
        [ "http://flightgear.telascience.org/mc?",
          "http://mapserver.flightgear.org/mc?" ],
        {layers: 'fgbuckets', format: 'image/png'},
        {isBaseLayer: false}
    );

    var osmlinecover = new OpenLayers.Layer.WMS( "OSM line features (polygon, experimental)",
        [ "http://flightgear.telascience.org/ms?srs=EPSG%3A900913&",
          "http://mapserver.flightgear.org/ms?srs=EPSG%3A900913&" ],
        {layers: 'osm_roadcover', format: 'image/png'},
        {isBaseLayer: false}
    );
