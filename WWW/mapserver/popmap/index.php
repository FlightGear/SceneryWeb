<html>
    <head>
        <title>Model Position</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <meta name="robots" content="index, nofollow" />

        <style type="text/css">
            body {
                font-family: "Lucida Grande", Verdana, Geneva, Lucida, Arial, Helvetica, sans-serif;
                font-size: 0.8em;
            }
            .olControlAttribution, .olControlScaleLine {
                bottom: 40px;
            }
            .olFramedCloudPopupContent { padding: 5px; }
            .olPopup p { margin:0px; font-size: .9em;}
            h2 { margin:0px; font-size: 1.2em;}
        </style>

        <?php include("../map/addons.php"); ?>

        <script type="text/javascript">

        // Needed only for interaction, not for the display.
        function onPopupClose(evt) {
            // 'this' is the popup.
            selectControl.unselect(this.feature);
        }

        function onFeatureSelect(evt) {
            feature = evt.feature;

            var content = "<h2>"+feature.attributes.title + "</h2>" +
                feature.attributes.description;
            content += "<br/ ><a href='http://scenemodels.flightgear.org/submission/object/check_update.php?id_to_update="+feature.attributes.id+"' target='_blank'>Update</a>";
            if(feature.attributes.type=="shared") {
                content += "&nbsp;<a href='http://scenemodels.flightgear.org/submission/object/check_delete_shared.php?delete_choice="+feature.attributes.id+"' target='_blank'>Delete</a>";
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
                    new OpenLayers.Control.MouseDefaults(),
                    new OpenLayers.Control.MousePosition(),
                    new OpenLayers.Control.ScaleLine()
                ],
            };
            OpenLayers.Util.onImageLoadError = function() {
                this.src='http://www.informationfreeway.org/images/emptysea.png'
            }
            OpenLayers.IMAGE_RELOAD_ATTEMPTS = 1;
            map = new OpenLayers.Map('map', options);

            map.addLayers([bingaerial, mapnik, tarmac, osmlines, wmsobjects, jsonobjects, wmssigns]);

            jsonobjects.events.on({
                'featureselected': onFeatureSelect,
                'featureunselected': onFeatureUnselect
            });

            selectControl = new OpenLayers.Control.SelectFeature(
                [jsonobjects],
                {
                    clickout: true, toggle: true,
                    multiple: true, hover: false,
                    toggleKey: "ctrlKey",  // ctrl key removes from selection
                    multipleKey: "shiftKey"  // shift key adds to selection
                }
            );
            map.addControl(selectControl);
            selectControl.activate();
            map.addControl(new OpenLayers.Control.LayerSwitcher());
            map.addControl(new OpenLayers.Control.KeyboardDefaults());


            if (!map.getCenter()) {
                var ll = new OpenLayers.LonLat(lon, lat), zoom;
                ll.transform(projLonLat, projMercator);
                map.setCenter(ll);
            }
        }
        </script>
    </head>

    <body style='margin: 0px;' onload="init();">
        <div style=" width:100%; height:100%;" id="map"></div>
    </body>
</html>
