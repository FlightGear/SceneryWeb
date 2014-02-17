<?php
header("Content-type: image/jpg");
require 'inc/form_checks.php';

$link = pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass. 'sslmode=disable');
if (isset($_REQUEST['id']) && preg_match($regex['modelid'], $_REQUEST['id']))
{   
    $id = $_REQUEST['id'];
    $result = pg_query("SELECT mo_thumbfile FROM fgs_models WHERE mo_id=$id;");
    $model = pg_fetch_assoc($result);
    if (strlen($model["mo_thumbfile"])>1024)
        echo base64_decode($model["mo_thumbfile"]);
    else
        readfile("http://scenery.flightgear.org/img/nothumb.jpg");
}

?>
