require.config({
    shim: {
      'jquery-ui': {
        depends: 'jquery',
      },
      'leaflet': {
        exports: 'L',
      },
      'leaflet-tilegrid': {
        deps: [ 'leaflet' ]
      },
      'leaflet-coordinates': {
        deps: [ 'leaflet' ]
      },
      'leaflet-contextmenu': {
        deps: [ 'leaflet' ]
      },
    },
    baseUrl : '.',
    paths : {
//      'leaflet': '3rdparty/leaflet',
    },          
    waitSeconds : 30,
});     

require([
], function( ) {
  $(window).resize(function() {
    $map = $("#map");
    var offset = $map.offset();
    var h = $(window).height() - offset.top - offset.left*2;
    $map.outerHeight(h);
  });
  $(window).trigger('resize');
    var lat = 53.5;
    var lon = 10.0;
    var zoom = 10;

    var map = L.map('map', {
    }).setView([lat, lon], zoom);

    var osm_layer = L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                minZoom : 3,
                maxZoom : 19,
                attribution : 'Map data &copy; <a target="_blank" href="http://openstreetmap.org">OpenStreetMap</a> contributors'
    }).addTo(map);

    var esriLayer = L.tileLayer( 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; <a href="http://www.esri.com/">Esri</a>, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            maxZoom: 18,
    });


});

