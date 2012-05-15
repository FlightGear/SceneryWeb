<?php 
include("pgsql.php");

header("Content-type: image/jpg");
if (isset($_GET['id']) && (preg_match('/^[0-9]+$/u',$_GET['id']))){

  $id     = $_GET['id'];
  $result = pg_query("SELECT mo_thumbfile FROM fgs_models WHERE mo_id=$id;");
  $model  = pg_fetch_assoc($result);

  if(strlen($model["mo_thumbfile"])>1024){
    echo base64_decode($model["mo_thumbfile"]);
  }else{
    readfile("img/nothumb.jpg");
  }
}
?>
