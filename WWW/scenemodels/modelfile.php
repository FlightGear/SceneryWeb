<?php 
$link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id'])))
{   
	$id=$_REQUEST['id'];
	$result=pg_query("select mo_modelfile from fgs_models where mo_id=$id;");
	$model=pg_fetch_assoc($result);
	header("Content-type: application/x-gtar");
	header("Content-Disposition: inline; filename=".$id.".tgz");
	echo base64_decode($model["mo_modelfile"]);
};
?>
