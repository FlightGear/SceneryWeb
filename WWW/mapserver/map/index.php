<!--
  FlightGear MapServer Landcover-DB OpenLayers Map
  Copyright (C) 200x - 2011  Martin Spott - Martin (at) flightgear (dot) org
 
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License as
  published by the Free Software Foundation; either version 2 of the
  License, or (at your option) any later version.
 
  This program is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  General Public License for more details.
 
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
-->

<HTML>
  <HEAD>
    <TITLE>FlightGear Land Web Map</TITLE>
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
      #slider1 {
      	  width: 150px;
      	  position: relative;
      	  margin-left:30px;
      	  z-index:2001;
      	  display:block;
      	  float:left;
      }
      #slider2 {
      	  width: 150px;
      	  position: relative;
      	  margin-left:30px;
      	  z-index:2001;
      	  display:block;
      	  float:left;
      }
    </style>
    <link rel="stylesheet" href="jquery/jquery-ui-1.8.17.custom.css" type="text/css">
    <script src="jquery/jquery-1.7.1.min.js"></script>
    <script src="jquery/jquery-ui-1.8.17.custom.min.js"></script>
<!--    <script src="http://www.openlayers.org/dev/OpenLayers.js"></script> -->
    <script src="/ol/OpenLayers.js"></script>
<!--    <script src="../openlayers-ys/OpenLayers.js"></script> -->
    <script src="http://www.openstreetmap.org/openlayers/OpenStreetMap.js"></script>

    <script language='javascript' src='http://mapserver.flightgear.org/map/maplayers.js' type='text/javascript'></script>

    <script type="text/javascript">
    jQuery.noConflict();
    // click on map to select a box of coordinates
    var box_extents = [];
    function toggleDldBox(ev) {
    	if(document.getElementById('dlbox').style.display=="none") {
    		document.getElementById('dlbox').style.display="inline";
    	}
    	else {
    		document.getElementById('dlbox').style.display="none";
    	}
    }
    
    function toggleSliders(ev) {
    	if(document.getElementById('sliders').style.display=="none") {
    		document.getElementById('sliders').style.display="inline";
    	}
    	else {
    		document.getElementById('sliders').style.display="none";
    	}
    }
    
    var downloadbox  = new OpenLayers.Layer.Boxes( "Download-Box" );
    downloadbox.events.on({
                    'visibilitychanged': toggleDldBox
                });
    var opacity_sliders = new OpenLayers.Layer.Boxes( "Opacity Sliders" );
    opacity_sliders.events.on({
                    'visibilitychanged': toggleSliders
                });
    OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {                
                defaultHandlerOptions: {
                    'single': true,
                    'double': false,
                    'pixelTolerance': 0,
                    'stopSingle': false,
                    'stopDouble': false
                },

                initialize: function(options) {
                    this.handlerOptions = OpenLayers.Util.extend(
                        {}, this.defaultHandlerOptions
                    );
                    OpenLayers.Control.prototype.initialize.apply(
                        this, arguments
                    ); 
                    this.handler = new OpenLayers.Handler.Click(
                        this, {
                            'click': this.trigger
                        }, this.handlerOptions
                    );
                }, 

                trigger: function(e) {
                	if(box_extents.length==4) {
                		downloadbox.removeMarker(box);
                		box_extents=[];
                		document.getElementById('xmin').value='';
						document.getElementById('xmax').value='';
						document.getElementById('ymin').value='';
						document.getElementById('ymax').value='';
                	}
                    var lonlat = map.getLonLatFromViewPortPx(e.xy);
                    
                    if(box_extents.length==0) {
						box_extents[0]=lonlat.lon;
						box_extents[1]=lonlat.lat;
					}
					else {
						box_extents[2]=lonlat.lon;
						box_extents[3]=lonlat.lat;
					}
					if(box_extents.length==4) {
						
						var tmp;
						if (box_extents[0] > box_extents[2]) {
							tmp = box_extents[0];
							box_extents[0] = box_extents[2];
							box_extents[2] = tmp;
						}
						if (box_extents[1] > box_extents[3]) {
							tmp = box_extents[1];
							box_extents[1] = box_extents[3];
							box_extents[3] = tmp;
						}
						
						ext = box_extents;
						
						
						bounds = OpenLayers.Bounds.fromArray(ext);
						
						
						box = new OpenLayers.Marker.Box(bounds);
						
						box.events.register("click", box, function (e) {
							this.setBorder("yellow");
						});
						downloadbox.addMarker(box);
						bounds.transform(new OpenLayers.Projection("EPSG:900913"), new OpenLayers.Projection("EPSG:4326"));
						//alert(bounds.toString());
						var coord1= new OpenLayers.LonLat(box_extents[0],box_extents[1]);
						coord1.transform(new OpenLayers.Projection("EPSG:900913"), new OpenLayers.Projection("EPSG:4326"));
						var coord2= new OpenLayers.LonLat(box_extents[2],box_extents[3]);
						coord2.transform(new OpenLayers.Projection("EPSG:900913"), new OpenLayers.Projection("EPSG:4326"));
						document.getElementById('xmin').value=coord1.lon;
						document.getElementById('xmax').value=coord2.lon;
						document.getElementById('ymin').value=coord1.lat;
						document.getElementById('ymax').value=coord2.lat;
					}
                }

            });

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

	tarmac.setVisibility(true);
	tarmac850.setVisibility(false);
	osmtarmac.setVisibility(false);
	cslines.setVisibility(false);
	osmlines.setVisibility(true);
	osmlinecover.setVisibility(false);
	noaroads.setVisibility(false);
	airfield.setVisibility(false);
	airport850.setVisibility(true);
	navaid850.setVisibility(false);
	sceneobject.setVisibility(false);
	gshhs.setVisibility(false);
	fgbuckets.setVisibility(false);
	csdefault.setVisibility(false);
	downloadbox.setVisibility(false);
	opacity_sliders.setVisibility(false);
	
	map.addLayers([customscene, v0cover, yahoosat, googlesat, icubed, mapnik, clc00, clc06, tarmac, tarmac850, osmtarmac, cslines, osmlines, osmlinecover, noaroads, airfield, airport850, navaid850, sceneobject, gshhs, fgbuckets, csdefault, downloadbox, opacity_sliders]);

	map.addControl(new OpenLayers.Control.LayerSwitcher());
	map.addControl(new OpenLayers.Control.PanZoom());
	map.addControl(new OpenLayers.Control.Permalink('permalink'));
	map.addControl(new OpenLayers.Control.MouseToolbar());
	map.addControl(new OpenLayers.Control.MousePosition({'numDigits': 7}));
	var ll = new OpenLayers.LonLat(lon, lat), zoom;
	ll.transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913"));
	map.setCenter(ll);
	
	// click control
	var click = new OpenLayers.Control.Click();
	map.addControl(click);
	click.activate();
	
	jQuery("#slider1").slider({
			value: 100,
			slide: function(e, ui) {
				cslines.setOpacity(ui.value / 100);
			}
		});
	 
	 jQuery("#slider2").slider({
			value: 100,
			slide: function(e, ui) {
				osmlines.setOpacity(ui.value / 100);
			}
		});
	
      }
      
        
    </script>
  </HEAD>

  <BODY style='margin: 0px;' onload="init()" bgcolor=#FFFFFF>
    <div style=" width:100%; height:100%;" id="map"></div> 
    <div id="sliders" style="position:absolute; bottom:150px;width:700px;z-index: 2001;height:30px;" align="center">
    <div id="slider1"><span style="position:relative;top:20px;">CS Lines</span><div class="ui-slider-handle" style="background:#aaa;"></div></div>
    <div id="slider2"><span style="position:relative;top:20px;">OSM lines</span><div class="ui-slider-handle" style="background:#aaa;"></div></div>
    </div>
    <div style="position:absolute; bottom:10px;width:700px;z-index: 2001;" align="center">
      <table>
        <tr>
          <td>
            <b><a href="/">Back</a></b> to the intro page.
          </td>
          <td>
           <div id="dlbox" style="display:inline;">
            <form action="/shpdl" method="POST">
            <input type="text" id="xmin" name="xmin" value=""/>
            <input type="text" id="xmax" name="xmax" value=""/><br/>
            <input type="text" id="ymin" name="ymin" value=""/>
            <input type="text" id="ymax" name="ymax" value=""/><br/>
            <input type="submit" value="Download shapefiles">
            </form>
           </div>
          </td>
        </tr>
      </table>
    </div>
  </BODY>

</HTML>
