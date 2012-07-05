var box_extents = [];

function toggleDldBox(ev) {
    if(document.getElementById('dlbox').style.display=="none") {
        document.getElementById('dlbox').style.display="inline";
    }
    else {
        document.getElementById('dlbox').style.display="none";
    }
}

var downloadbox = new OpenLayers.Layer.Boxes( "Download-Box" );
downloadbox.events.on({
    'visibilitychanged': toggleDldBox
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
        this.handlerOptions = OpenLayers.Util.extend( {},
            this.defaultHandlerOptions
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
