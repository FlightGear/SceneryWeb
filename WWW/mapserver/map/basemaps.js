//    var customscene = new OpenLayers.Layer.WMS( "Landcover-DB CS Test",
//        [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
//          "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
//        {layers: 'cs_herbtundra,cs_heath,cs_barrencover,cs_dirt,cs_rainforest,cs_mixedforest,cs_evergreenforest,cs_deciduousforest,cs_olives,cs_orchard,cs_vineyard,cs_burnt,cs_sclerophyllous,cs_scrub,cs_greenspace,cs_grassland,cs_golfcourse,cs_agroforest,cs_cropgrass,cs_naturalcrop,cs_complexcrop,cs_mixedcrop,cs_rice,cs_irrcrop,cs_drycrop,cs_cemetery,cs_transport,cs_construction,cs_industrial,cs_dump,cs_openmining,cs_lava,cs_rock,cs_saltmarsh,cs_littoral,cs_saline,cs_sand,cs_marsh,cs_bog,cs_estuary,cs_lagoon,cs_floodland,cs_town,cs_suburban,cs_port,cs_urban,cs_polarice,cs_packice,cs_glacier,cs_watercourse,cs_intermittentlake,cs_lake,cs_asphalt,cs_airport', format: 'image/png'},
//        {minZoomLevel: 10}
//    );

    var customscene = new OpenLayers.Layer.WMS( "Landcover-DB CS Test",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'customscene', format: 'image/png'}
    );

    var v0cover = new OpenLayers.Layer.WMS( "Landcover-DB VMap0 Ed.5",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'v0cover', format: 'image/png'},
        {attribution: "Origin: <a href='http://www.nga.mil/'>NGA</a>"}
    );

    var icubed = new OpenLayers.Layer.WMS( "TelaScience i-Cubed",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'icubed', format: 'image/png'},
        {wrapDateLine: true}
    );

    var mapnik = new OpenLayers.Layer.OSM.Mapnik( "OSM Mapnik (ODbL)",
        {attribution: "&copy; <a href='http://www.openstreetmap.org/copyright'>OpenStreetMap</a> contributors"}
    );

//    var mapnik = new OpenLayers.Layer.TMS( "mapnik",
//        "http://tile.openstreetmap.org/mapnik/",
//        {type:'png', getURL: get_osm_url, displayOutsideMaxExtent: true, 'buffer':1, transitionEffect: 'resize'}
//    );

    var tarmac = new OpenLayers.Layer.WMS( "Airfield layouts (v8.10)",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'tarmac', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false}
    );

    var tarmac850 = new OpenLayers.Layer.WMS( "Airfield layouts (v9.00, 2013.04B)",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'tarmac850', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false}
    );

    var osmlines = new OpenLayers.Layer.WMS( "OSM line features (#CURRENTOSMVERSION#)",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'osmlines', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false}
//        {gutter: 50}
    );

    var airfield = new OpenLayers.Layer.WMS( "Aerodrome reference points",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'airfield', format: 'image/png'},
        {isBaseLayer: false}
    );

    var navaid = new OpenLayers.Layer.WMS( "Navaids",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'navaid', format: 'image/png'},
        {isBaseLayer: false}
    );

    var fix = new OpenLayers.Layer.WMS( "Fixes",
        [ "http://1.flightgear.telascience.org/tc?",
          "http://2.flightgear.telascience.org/tc?",
          "http://3.flightgear.telascience.org/tc?",
          "http://4.flightgear.telascience.org/tc?" ],
        {layers: 'fix', format: 'image/png'},
        {isBaseLayer: false}
    );

    var sceneobject = new OpenLayers.Layer.WMS( "Scenery Objects (point)",
        [ "http://2.flightgear.telascience.org/ms?srs=EPSG%3A900913&",
          "http://4.flightgear.telascience.org/ms?srs=EPSG%3A900913&" ],
        {layers: 'fgs_staticobjects,fgs_sharedobjects', transparent: 'true', format: 'image/png'},
        {isBaseLayer: false}
    );

//    var sceneobject = new OpenLayers.Layer.WMS( "Scenery Objects (point)",
//        [ "http://1.flightgear.telascience.org/tc?",
//          "http://2.flightgear.telascience.org/tc?",
//          "http://3.flightgear.telascience.org/tc?",
//          "http://4.flightgear.telascience.org/tc?" ],
//        {layers: 'sceneobject', transparent: 'true', format: 'image/png'},
//        {isBaseLayer: false}
//    );

