define([
        'knockout', 'text!./AirportInfo.html', 'TheMap', 'jquery'
], function(ko, htmlString, map, jquery) {

        var ProcedureViewModel = function( props ) {
          var self = this;
          props = props || {
            id: -1,
            name: 'unnamed procedure',
            runways: 'All',
            type: 'Star',
          }

          self.id = props.id;
          self.name = ko.observable(props.name);
          self.runways = ko.observable(props.runways);
          self.type = ko.observable(props.type);

          self.valueEdit = function( val, evt ) {
          }
        }

        var ViewModel = function(params) {

          var self = this;
          var geoJson = null;

          self.airportId = ko.observable("");
          self.airportId.subscribe(function(newValue) {
            loadAirport(newValue);
          });

          function loadAirport(icao) {
console.log("load ", icao );

            if( geoJson ) map.removeLayer( geoJson );
            geoJson = null;
            self.procedures.removeAll();

            var url = "/svc/getapt?icao=" + icao;
            var jqxhr = $.get(url).done(function(data) {
              geoJson = L.geoJson( data.runwaysGeometry, {
                style: {
                  'color': '#404040',
                  'weight': 2,
                  'opacity': 0.5,
                  'fill': true,
                  'fillColor': '#c0c0c0',
                  'fillOpacity': 0.5,
                },
              }).addTo(map);
              map.fitBounds(geoJson.getBounds());

              if( data.procedures ) {
                var ps = [];
                data.procedures.forEach( function(p) {
                  ps.push( new ProcedureViewModel(p) );
                });
                self.procedures( ps );
              }

            }).fail(function() {
              console.log('failed to load airport data');
            }).always(function() {
            });
          }

          self.procedures = ko.observableArray([]);

          self.addNew = function( obj, evt ) {

            var inplaceEditor = jquery(jquery('#inplace-editor-template').html());

            var elem = jquery(evt.target);
            elem.hide();
            elem.after(inplaceEditor);
            inplaceEditor.val(elem.text()).focus().select();

            function endEdit(val) {
                inplaceEditor.remove();
                elem.show();

                if (typeof (val) === 'undefined')
                    return;
                var val = val.trim();
                var jqxhr = jquery.post('/svc/getapt', JSON.stringify({
                    'command' : 'newProcedure',
                    'icao': self.airportId(),
                    'name': val,
                    'type': 'Star',
                    'runways': 'All',
                })).done(function(data){
                     // trigger a data reload
console.log("trigger load");
                     loadAirport(self.airportId());
                });
           }

            inplaceEditor.on('keyup', function(evt) {
                switch (evt.keyCode) {
                case 27:
                    endEdit();
                    break;
                case 13:
                    endEdit(inplaceEditor.val());
                    break;
                }
            });

            inplaceEditor.blur(function() {
                endEdit(inplaceEditor.val());
            });
          }

          self.approachExpanded = ko.observable(false);
          self.clickApproach = function() {
            self.approachExpanded(!self.approachExpanded());
            self.sidExpanded(false);
            self.starExpanded(false);
            self.sidTransitionExpanded(false);
            self.starTransitionExpanded(false);
            self.rwyTransitionExpanded(false);
          }

          self.sidExpanded = ko.observable(false);
          self.clickSid = function() {
            self.approachExpanded(false);
            self.sidExpanded(!self.sidExpanded());
            self.starExpanded(false);
            self.sidTransitionExpanded(false);
            self.starTransitionExpanded(false);
            self.rwyTransitionExpanded(false);
          }

          self.starExpanded = ko.observable(false);
          self.clickStar = function() {
            self.approachExpanded(false);
            self.sidExpanded(false);
            self.starExpanded(!self.starExpanded());
            self.sidTransitionExpanded(false);
            self.starTransitionExpanded(false);
            self.rwyTransitionExpanded(false);
          }

          self.sidTransitionExpanded = ko.observable(false);
          self.clickSidTransition = function() {
            self.approachExpanded(false);
            self.sidExpanded(false);
            self.starExpanded(false);
            self.sidTransitionExpanded(!self.sidTransitionExpanded());
            self.starTransitionExpanded(false);
            self.rwyTransitionExpanded(false);
          }

          self.starTransitionExpanded = ko.observable(false);
          self.clickStarTransition = function() {
            self.approachExpanded(false);
            self.sidExpanded(false);
            self.starExpanded(false);
            self.sidTransitionExpanded(false);
            self.starTransitionExpanded(!self.starTransitionExpanded());
            self.rwyTransitionExpanded(false);
          }

          self.rwyTransitionExpanded = ko.observable(false);
          self.clickRwyTransition = function() {
            self.approachExpanded(false);
            self.sidExpanded(false);
            self.starExpanded(false);
            self.sidTransitionExpanded(false);
            self.starTransitionExpanded(false);
            self.rwyTransitionExpanded(!self.rwyTransitionExpanded());
          }

          if( params && params.icao ) self.airportId( params.icao);
        }

    ViewModel.prototype.dispose = function() {
    }

    // Return component definition
    return {
        viewModel : ViewModel,
        template : htmlString
    };
});
