<?php

// Connecting to the database
$link = pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');

// Are there any boundary boxes defined ?
if (isset($_REQUEST['bbox']) && (preg_match('/^[0-9\,\-\.]+$/u', $_GET['bbox']))) {
    $bounds = explode(",",$_REQUEST['bbox']);
    //echo $bounds[0]." ".$bounds[1]." ".$bounds[2]." ".$bounds[3]."\n";
}
else {
    echo "No bbox defined!\n";
}

// Preparing the request

$query = "SELECT ob_id, ob_text, ob_model, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, ob_heading ";
$query.= "FROM fgs_objects ";
$query.= "WHERE ST_Within(wkb_geometry, ST_GeomFromText('POLYGON((".$bounds[0]." ".$bounds[1].",".$bounds[0]." ".$bounds[3].",".$bounds[2]." ".$bounds[3].",".$bounds[2]." ".$bounds[1].",".$bounds[0]." ".$bounds[1]."))',4326)) ";
$query.= "LIMIT 400";
//echo $query."\n\n\n";
?>
{"type":"FeatureCollection",
"features":[
<?php
// Querying the database

$result = pg_query($query);
while ($row = pg_fetch_assoc($result)) {
?>
{"type": "Feature",
"id": "OpenLayers.Feature.Vector_<?php echo $row["ob_id"];?>",
"properties":{
"heading": <?php echo $row["ob_heading"];?>,
"title": "Object #<?php echo $row["ob_id"];?> - <?php echo $row["ob_text"];?>",
"description": "<img src=/modelthumb.php?id=<?php echo $row["ob_model"];?>>"
},
"geometry":{
"type": "Point","coordinates": [<?php echo $row["ob_lon"];?>, <?php echo $row["ob_lat"];?>]
},
},
<?php
}
?>
]}
