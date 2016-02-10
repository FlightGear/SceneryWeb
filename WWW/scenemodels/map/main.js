require.config({
    shim: {
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
      'leaflet': 'http://cdn.leafletjs.com/leaflet/v0.7.7/leaflet',
      'jquery': 'https://code.jquery.com/jquery-1.11.3.min',
      'leaflet-coordinates': 'http://mrmufflon.github.io/Leaflet.Coordinates/dist/Leaflet.Coordinates-0.1.3.min',
      'leaflet-contextmenu': 'http://aratcliffe.github.io/Leaflet.contextmenu/dist/leaflet.contextmenu',
      'leaflet-tilegrid': 'tilegrid',
      'knockout': 'https://cdnjs.cloudflare.com/ajax/libs/knockout/3.4.0/knockout-min',
      'text': 'https://cdnjs.cloudflare.com/ajax/libs/require-text/2.0.12/text.min'
    },          
    waitSeconds : 30,
});     

require([           
        'knockout', 'TheMap', 
], function(ko, map ) {

        var ViewModel = function() {
          var self = this;
        }

        ko.components.register('AirportInfo', {
          require: 'AirportInfo'
        });
        ko.applyBindings( new ViewModel, document.getElementById('map') );
});
