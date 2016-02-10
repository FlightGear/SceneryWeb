define(
  ['leaflet-tilegrid', 'leaflet-coordinates', 'leaflet-contextmenu' ], 

function() {

        function getRequestParameter(name) {
           if(name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
              return decodeURIComponent(name[1]);
        }

        var lat = getRequestParameter('lat') || 0;
        var lon = getRequestParameter('lon') || 0;
        var zoom = getRequestParameter('z') || 3;
        var icao = getRequestParameter('icao') || "";


        var SelectedObject = null;

        var map = L.map('map', {
            contextmenu: true,
            contextmenuItems: [{
		      text: 'Get coordinates',
		      callback: function(e) { 
                          window.prompt('Copy to clipboard: Ctrl+C, Enter', e.latlng.lng.toFixed(6) + ' ' + e.latlng.lat.toFixed(6) ); 
                      },
	      }, {
		      text: 'Get bounds',
		      callback: function(e) { 
                          var b = map.getBounds();
                          window.prompt('Copy to clipboard: Ctrl+C, Enter', b.getSouth().toFixed(6) +  ' ' + b.getWest().toFixed(6) +' ' + b.getNorth().toFixed(6) +' ' + b.getEast().toFixed(6) ); 
                      },
	      }, {
		      text: 'Center map here',
		      callback: function(e) { map.panTo(e.latlng); },
	      }, {
		      text: 'Move marked object here',
		      callback: function(e) { 
                        if( SelectedObject )
                          window.open('http://scenemodels.flightgear.org/app.php?c=UpdateObjects&a=updateForm&id_to_update=' + SelectedObject + '&lon=' + e.latlng.lng.toFixed(6) + '&lat=' + e.latlng.lat.toFixed(6) + '', "_blank");
                      },
	      }, {
		      text: 'Place new object here',
		      callback: function(e) { 
                        var url = "http://scenemodels.flightgear.org/app.php?c=AddObjects&a=form&lat=" 
                                 + e.latlng.lat.toFixed(6) + "&lon=" + e.latlng.lng.toFixed(6);
                        window.open(url, "_blank" );
                      },
	      }
	      ]
        }).setView([lat, lon], zoom);

        var osm_layer = L.tileLayer(
            'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                minZoom : 3,
                maxZoom : 18,
                attribution : 'Map data &copy; <a target="_blank" href="http://openstreetmap.org">OpenStreetMap</a> contributors'
            }).addTo(map);

        var esriLayer = L.tileLayer( 'http://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; <a href="http://www.esri.com/">Esri</a>, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
        maxZoom: 18,
        });

       function parse(str) {
           var args = [].slice.call(arguments, 1),
               i = 0;

           return str.replace(/%s/g, function() {
               return args[i++];
           });
        }

        var markerSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="xMinYMin meet">' +
                        '<circle cx="50" cy="50" r="25" stroke="black" fill="none" stroke-width="3" />' +
                        '<g id="arrow" transform="rotate(%s 50 50)">' +
                        '<line x1="50" y1="50" x2="50" y2="2" stroke="black" stroke-width="3" stroke-linecap="round"/>' +
                        '<line x1="40" y1="20" x2="50" y2="2" stroke="black" stroke-width="3" stroke-linecap="round"/>' +
                        '<line x1="60" y1="20" x2="50" y2="2" stroke="black" stroke-width="3" stroke-linecap="round"/>' +
                        '</g>' +
                        '</svg>';

        L.ScenemodelsLayer = L.GeoJSON.extend({
          options : {
            pointToLayer : function(feature, latlng) {
                
                var marker = L.marker(latlng, {
                    icon: L.divIcon({
                      className: feature.properties.shared > 0 ? 'object-marker-shared' : 'object-marker-static',
                      html: parse(markerSvg, feature.properties.heading),
                      iconSize: [ 32, 32 ],
                      iconAnchor: [ 16, 16 ],
                    }),
                    contextmenu: true,
                    contextmenuItems: [{
                        text: 'Mark object',
                        callback: function(e) {
                          SelectedObject = feature.properties.id;
                        },
                    }, ]
                });
/*
                var color = feature.properties.shared > 0 ? '#ff1010' : '#1010ff';
                var marker = L.circleMarker(latlng, {
                    radius: 4,
                    fillColor: color,
                    color: "#000",
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.8,

                    contextmenu: true,
                    contextmenuItems: [{
                        text: 'Move object (experimental)',
                        callback: function(e) {
                          SelectedObject = feature.properties.id;
                          console.log("move item", feature.properties.id);
                        },
                    }, ]
                });
*/
                return marker;
            },

            onEachFeature : function(feature, layer) {
                var popupContent = "<div><a href='/app.php?c=Objects&a=view&id=" + feature.id + "' target='_blank'>Object #" + feature.id + "</a></div>";
                popupContent += "<a href='http://scenery.flightgear.org/map/?lat=" + feature.geometry.coordinates[1] +
                                                                            "&lon=" + feature.geometry.coordinates[0] +
                                                                            "&z=13&obj=" + feature.properties.model_id + "'>permalink</a>";
                if (feature.properties) {
                    function makePopupContentPropertyEntry(title,props,key) {
                      if(key in props)  return '<li>' + title + ': ' + props[key] + '</li>';
                      return '';
                    }
                    if( feature.properties.model_id )
                      popupContent += '<div><img width="160" height="120" alt="Model Thumbnail" src="/app.php?c=Models&a=thumbnail&id=' + feature.properties.model_id + '"></img></div>'
                    popupContent += '<ul>';
                    popupContent += makePopupContentPropertyEntry('Title', feature.properties, 'title' );
                    popupContent += makePopupContentPropertyEntry('Heading', feature.properties, 'heading' );
                    popupContent += makePopupContentPropertyEntry('Ground Elevation', feature.properties, 'gndelev' );
                    popupContent += makePopupContentPropertyEntry('Elevation Offset', feature.properties, 'elevoffset' );
                    popupContent += makePopupContentPropertyEntry('Shared', feature.properties, 'shared' );
                    popupContent += 
                       '<li>STG: <a target="_blank" href="http://flightgear.sourceforge.net/scenery/' + feature.properties.stg + '">' + feature.properties.stg +  '</a></li>';
                    popupContent += '</ul>';
                }

                layer.bindPopup(popupContent, {
                    autoPan: false
                });
            },
          },

          onAdd : function(map) {
            L.GeoJSON.prototype.onAdd.call(this, map);
            this.refresh(map);
          },

          refresh: function(map) {
            var self = this;
            self.clearLayers();
            var bounds = map.getBounds();
            var url = "/svc/getobjects?w=" + bounds.getWest() 
                                       + "&e=" + bounds.getEast() 
                                       + "&n=" + bounds.getNorth() 
                                       + "&s=" + bounds.getSouth(); 

            if( map.getZoom() > 12 ) {
                var jqxhr = $.get(url).done(function(data) {
                    self.addData.call(self, data);
                }).fail(function() {
                    console.log('failed to load scenemodels data');
                }).always(function() {
                });
              } else {
                // make a grid of 200x200px for the map and display number
                // of objects instead of individual objects per grid
                var bounds = map.getBounds();
                var mapWidth = $('#map').width();
                var mapHeight = $('#map').height();
                var gridSizeX = Math.floor(mapWidth/200);
                var gridSizeY = Math.floor(mapHeight/200);
                var degPerPixelX = (bounds.getEast()-bounds.getWest())/mapWidth;
                var degPerPixelY = (bounds.getNorth()-bounds.getSouth())/mapWidth;
              }
          },

        });

        L.scenemodelsLayer = function(options) {
          return new L.ScenemodelsLayer(null, options);
        }

        var scenemodelsLayer = L.scenemodelsLayer({
        }).addTo(map);

        L.SignsLayer = L.GeoJSON.extend({
          options : {
            pointToLayer : function(feature, latlng) {
                return L.circleMarker(latlng, {
                    radius: 3,
                    fillColor: 'yellow',
                    color: "#000",
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.8,
                });
            },

            onEachFeature : function(feature, layer) {
                var popupContent = "";
                popupContent += "<a href='http://scenery.flightgear.org/map/?lat=" + feature.geometry.coordinates[1] +
                                                                            "&lon=" + feature.geometry.coordinates[0] +
                                                                            "&z=13&obj=" + feature.properties.model_id + "'>permalink</a>";
                if (feature.properties) {
                    popupContent += '<div><span>Sign: </span><span>' + feature.properties.definition;
                    popupContent += '<div><span>Heading: </span><span>' + feature.properties.heading;
                    popupContent += '<div><span>Elevation: </span><span>' + feature.properties.gndelev;
                }

                layer.bindPopup(popupContent);
            },
          },

          onAdd : function(map) {
            L.GeoJSON.prototype.onAdd.call(this, map);
            this.refresh(map);
          },

          refresh: function(map) {
            var self = this;
            var bounds = map.getBounds();
            self.clearLayers();
            var url = "/svc/getsigns?w=" + bounds.getWest() 
                                       + "&e=" + bounds.getEast() 
                                       + "&n=" + bounds.getNorth() 
                                       + "&s=" + bounds.getSouth(); 

            if( map.getZoom() > 13 ) {
                var jqxhr = $.get(url).done(function(data) {
                    self.addData.call(self, data);
                }).fail(function() {
                    console.log('failed to load scenemodels data');
                }).always(function() {
                });
              }
          },

        });

        L.signsLayer = function(options) {
          return new L.SignsLayer(null, options);
        }

        var signsLayer = L.signsLayer({
        }).addTo(map);

    
        var grid = L.tileGrid().addTo(map);

        var baseLayers = {
            "Openstreetmap": osm_layer,
            "Esri World Imagery" : esriLayer,
        }

        var overlays = {
            "Scenemodels": scenemodelsLayer,
            "Signs": signsLayer,
            "Grid":  grid,
        }

        L.control.layers(baseLayers, overlays).addTo(map);

        L.control.coordinates({
            position:"bottomleft",
            decimals:6,
            decimalSeperator:",",
            labelTemplateLat:"Lat: {y}",
            labelTemplateLng:"Lng: {x}"
        }).addTo(map);
        L.control.coordinates({
            position:"bottomleft",
            useDMS:true,
            labelTemplateLat:"N {y}",
            labelTemplateLng:"E {x}",
            useLatLngOrder:true
        }).addTo(map);

        map.on( "moveend", function(e) {
          scenemodelsLayer.refresh( map );
          signsLayer.refresh( map );
        });

        var airportInfo = L.control({
          position: 'topleft',
        });

        airportInfo.onAdd = function (map) {
            this._div = L.DomUtil.create('div', 'airport-info');
            this._div.innerHTML = "<div data-bind='component: { name: \"AirportInfo\", params: { icao: \"" + icao + "\" }}'></div>";
            return this._div;
        };

        airportInfo.addTo(map);



   return map;
});