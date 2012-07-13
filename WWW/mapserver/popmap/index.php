<html>
    <head>
        <title>Model Position</title>

        <style type="text/css">
            .olFramedCloudPopupContent { padding: 5px; }
            .olPopup p { margin:0px; font-size: .9em;}
            h2 { margin:0px; font-size: 1.2em;}
        </style>

        <?php include("../map/suppscripts.php"); ?>

        <script type="text/javascript">
        <!--
        var lon = <?php print $_REQUEST["lon"]; ?>;
        var lat = <?php print $_REQUEST["lat"]; ?>;
        var zoom = <?php print $_REQUEST["zoom"]; ?>;
        var map;

        function init() {

            OpenLayers.Util.onImageLoadError = function() {
                this.src='http://www.informationfreeway.org/images/emptysea.png'
            }
            map = new OpenLayers.Map ("map", {
                displayProjection: new OpenLayers.Projection("EPSG:4326"),
                controls:[
                    new OpenLayers.Control.MouseDefaults(),
                    new OpenLayers.Control.MousePosition(),
                    new OpenLayers.Control.Permalink(),
                    new OpenLayers.Control.LayerSwitcher(),
                    new OpenLayers.Control.PanZoomBar(),
                    new OpenLayers.Control.ScaleLine()
                ],
                maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
                numZoomLevels:18, maxResolution:156543.0339, units:'m', projection: "EPSG:900913"}
            );

            map.addLayers([yahoosat, mapnik, osmarender, tarmac, osmlines, wmsobjects, jsonobjects, wmssigns, wfssigns]);

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
                map.setCenter (new OpenLayers.LonLat(lon, lat).transform(map.displayProjection, map.getProjectionObject()), zoom);
            }

        }

        // Needed only for interaction, not for the display.
        function onPopupClose(evt) {
            // 'this' is the popup.
            selectControl.unselect(this.feature);
        }

        function onFeatureSelect(evt) {
            feature = evt.feature;
            popup = new OpenLayers.Popup.FramedCloud("featurePopup",
                feature.geometry.getBounds().getCenterLonLat(),
                new OpenLayers.Size(100,100),
                "<h2>"+feature.attributes.title + "</h2>" +
                feature.attributes.description+"<br/ ><a href='http://scenemodels.flightgear.org/submission/shared/check_update_shared.php?update_choice="+feature.attributes.id+"' target='_blank'>Update</a>"+
                "&nbsp;<a href='http://scenemodels.flightgear.org/submission/shared/check_delete_shared.php?delete_choice="+feature.attributes.id+"' target='_blank'>Delete</a>",
                null, true, onPopupClose
            );
            popup.minSize= new OpenLayers.Size(300,300);
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
        //-->
        </script>
    </head>

    <body style='margin: 0px;' onload="init();">
        <div style=" width:100%; height:100%;" id="map"></div>
    </body>
</html>
