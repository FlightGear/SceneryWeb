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

<html>
    <head>
        <title>FlightGear Land Web Map</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <meta name="robots" content="index, nofollow" />
        <meta name="keywords" content="VMap0, VMap1, GSHHS, PGS, SWBD, DAFIF, ICAO, PostgreSQL, PostGIS, Mapserver, OGC, FlightGear, OSGeo, TelaScience" />
        <meta name="description" content="TelaScience / OSGeo / FlightGear Landcover / land use database consisting of different datasets fom VMap0, VMap1, GSHHS, PGS, SWBD and NIMA DAFIF Airfield database." />

        <style type="text/css">
            #map {
                width: 100%;
                height: 100%;
            }
            body {
                font-family: "Lucida Grande", Verdana, Geneva, Lucida, Arial, Helvetica, sans-serif;
                font-size: 0.8em;
            }
            .olControlAttribution, .olControlScaleLine {
                bottom: 40px;
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
            .olFramedCloudPopupContent { padding: 5px; }
            .olPopup p { margin:0px; font-size: .9em;}
            h2 { margin:0px; font-size: 1.2em;}
        </style>

        <link rel="stylesheet" href="jquery/jquery-ui-1.8.17.custom.css" type="text/css">
        <script src="jquery/jquery-1.7.1.min.js"></script>
        <script src="jquery/jquery-ui-1.8.17.custom.min.js"></script>

        <?php include("../map/addons.php"); ?>

        <script src='http://mapserver.flightgear.org/map/downloadbox.js' type='text/javascript'></script>

        <script type="text/javascript">
        jQuery.noConflict();
        // click on map to select a box of coordinates
        function toggleSliders(ev) {
            if(document.getElementById('sliders').style.display=="none") {
                document.getElementById('sliders').style.display="inline";
            }
            else {
                document.getElementById('sliders').style.display="none";
            }
        }
        var opacity_sliders = new OpenLayers.Layer.Boxes( "Opacity Sliders" );
        opacity_sliders.events.on({
            'visibilitychanged': toggleSliders
        });
 
        // Needed only for interaction, not for the display.
        function onPopupClose(evt) {
            // 'this' is the popup.
            selectControl.unselect(this.feature);
        }

        function onFeatureSelect(evt) {
            feature = evt.feature;

            var content = "<h2>"+feature.attributes.title + "</h2>" +
                feature.attributes.description;
            if(feature.attributes.type=="shared") {
                content += "<br/ ><a href='http://scenemodels.flightgear.org/submission/object/check_update_shared.php?update_choice="+feature.attributes.id+"' target='_blank'>Update</a>"+
                "&nbsp;<a href='http://scenemodels.flightgear.org/submission/object/check_delete_shared.php?delete_choice="+feature.attributes.id+"' target='_blank'>Delete</a>";
            }
            if(feature.attributes.type=="static") {
                content += "<br/ ><a href='http://scenemodels.flightgear.org/submission/object/check_update_shared.php?update_choice="+feature.attributes.id+"' target='_blank'>Update</a>";
            }
            popup = new OpenLayers.Popup.FramedCloud("featurePopup",
                feature.geometry.getBounds().getCenterLonLat(),
                new OpenLayers.Size(100,100),
                content,
                null, true, onPopupClose
            );
            popup.minSize= new OpenLayers.Size(350,350);
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

        var lon = <?php print $_REQUEST["lon"]; ?>;
        var lat = <?php print $_REQUEST["lat"]; ?>;
        var zoom = <?php print $_REQUEST["zoom"]; ?>;
        var map;

        projLonLat   = new OpenLayers.Projection("EPSG:4326");    // WGS84
        projMercator = new OpenLayers.Projection("EPSG:900913");  // Google Spherical Mercator

        function init() {
            var options = {
                projection: projMercator,
                displayProjection: projLonLat,
                units: "m",
                maxResolution: 156543.0339,
                maxExtent: new OpenLayers.Bounds(-20037508.34, -20037508.34, 20037508.34, 20037508.34),
                controls:[
                    new OpenLayers.Control.LayerSwitcher(),
                    new OpenLayers.Control.PanZoom(),
                    new OpenLayers.Control.Attribution(),
                    new OpenLayers.Control.Permalink('permalink'),
                    new OpenLayers.Control.MouseToolbar(),
                    new OpenLayers.Control.MousePosition({'numDigits': 7}),
                    new OpenLayers.Control.ScaleLine()
                ],
            };
            OpenLayers.IMAGE_RELOAD_ATTEMPTS = 1;
            map = new OpenLayers.Map('map', options);

            tarmac810.setVisibility(false);
            tarmac.setVisibility(true);
            osmtarmac.setVisibility(false);
            osmlines.setVisibility(true);
            osmlinecover.setVisibility(false);
            airfield.setVisibility(true);
            navaid.setVisibility(false);
            fix.setVisibility(false);
            airspaces.setVisibility(false);
            sceneobject.setVisibility(false);
            osmcoast.setVisibility(false);
            fgbuckets.setVisibility(false);
            downloadbox.setVisibility(false);

            map.addLayers([customscene, v0cover, yahoosat, googlesat, bingaerial, icubed, mapnik, bingroad, clc00, clc06, nlcd2006r, clc06v16r, tarmac810, tarmac, osmtarmac, osmlines, osmlinecover, airfield, navaid, fix, airspaces, sceneobject, osmcoast, fgbuckets, downloadbox]);

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

            if (!map.getCenter()) {
                var ll = new OpenLayers.LonLat(lon, lat), zoom;
                ll.transform(projLonLat, projMercator);
                map.setCenter(ll);
            }
        }
        </script>
    </head>

    <body style='margin: 0px;' onload="init()" bgcolor=#FFFFFF>
        <div style=" width:100%; height:100%;" id="map"></div>
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
    </body>

</html>
