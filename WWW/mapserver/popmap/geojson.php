<?php
require_once('../include/functions.inc.php');
        
// Connecting to the database
$link = pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');

// Is any boundary box defined?
    if (isset($_REQUEST['bbox']) && (preg_match('/^[0-9\,\-\.]+$/u',$_GET['bbox']))) {
        $bounds = explode(",",$_REQUEST['bbox']);
        //echo $bounds[0]." ".$bounds[1]." ".$bounds[2]." ".$bounds[3]."\n";
    }

    else {
        echo "No bbox defined!\n";
    }

// Preparing the query
$query = "SELECT ob_id, ob_text, ob_model, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, ob_heading ";
$query.= "FROM fgs_objects ";
$query.= "WHERE ST_Within(wkb_geometry, ST_GeomFromText('POLYGON((".$bounds[0]." ".$bounds[1].",".$bounds[0]." ".$bounds[3].",".$bounds[2]." ".$bounds[3].",".$bounds[2]." ".$bounds[1].",".$bounds[0]." ".$bounds[1]."))',4326)) ";
$query.= "LIMIT 400";
//echo $query."\n\n\n";

?>
{"type":"FeatureCollection",
    "features":[
        <?php
            // Grabbing data from query
            $is_first = true;
            $result = pg_query($query);
            while ($row = pg_fetch_assoc($result)){
                if($is_first)
                    $is_first = false;
                else
                    echo ",";
        ?>
        {"type": "Feature",
            "id": "OpenLayers.Feature.Vector_<?php echo $row["ob_id"];?>",
            "properties":{
                "id":<?php echo $row["ob_id"];?>,
                "type":"<?php echo is_shared_or_static($row["ob_id"]);?>",
                "heading": <?php echo $row["ob_heading"];?>,
                "title": "Object #<?php echo $row["ob_id"];?> - <?php echo $row["ob_text"];?>",
                "description": "<a href='http://scenemodels.flightgear.org/app.php?c=Objects&a=view&id=<?php echo $row["ob_id"];?>' target='_blank'><img src='http://scenemodels.flightgear.org/modelthumb.php?id=<?php echo $row["ob_model"];?>' alt=''/></a>"
            },
            "geometry":{
                "type": "Point","coordinates": [<?php echo $row["ob_lon"];?>, <?php echo $row["ob_lat"];?>]
            }
        }
        <?php
            }
        ?>
    ]
}
