<?php 
include("include/pgsql.php");

if (isset($_GET['id']) && (preg_match('/^[0-9]+$/u',$_GET['id']))){

  $id     = $_GET['id'];
  $result = pg_query("SELECT mo_modelfile FROM fgs_models WHERE mo_id=$id;");
  $model  = pg_fetch_assoc($result);

  header("Content-type: application/x-gtar");
  header("Content-Disposition: inline; filename=".$id.".tgz");
  echo base64_decode($model["mo_modelfile"]);
}
?>
