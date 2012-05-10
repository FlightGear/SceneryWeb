<?php
  $link=pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');
if (isset($_REQUEST['offset']) && (preg_match('/^[0-9]+$/u',$_GET['offset'])))
{
  $offset=$_REQUEST['offset'];
  if ($offset<0) $offset=0;      
}
else
{       $offset=0;
};
if (isset($_REQUEST['shared']) && (preg_match('/^[0-9]+$/u',$_GET['shared'])))
{       
  if ($_REQUEST['shared']>0)
  {
    $groupquery="SELECT mg_name FROM fgs_modelgroups WHERE mg_id =".$_REQUEST['shared'].";";
    $gpresult=pg_query($groupquery);
    $row=pg_fetch_assoc($gpresult);
    $title="Model Browser: ".$row['mg_name'];
    $query="select mo_id,mo_path,mo_name from fgs_models where mo_shared =".$_REQUEST['shared']." order by mo_id limit 100 offset $offset;";
  }
  else
  {
    $title="FlightGear Scenery Static Model Browser";
    $query="select mo_id,mo_path,mo_name from fgs_models where mo_shared = 0 order by mo_id limit 100 offset $offset;";
  }  
}
else
{
  $query="select mo_id,mo_path,mo_name from fgs_models order by mo_id limit 100 offset $offset;";
  $title="FlightGear Scenery Model Browser";
};
?>
<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
<?php include 'header.php';?>
<h1 align=center><?php echo $title;?></h1>
<table>
  <tr class=bottom>
    <td colspan=9 align=center>
      <a href="modelbrowser.php?offset=<?php echo $offset-100;if (isset($_REQUEST['shared'])) {echo "&shared=".$_REQUEST['shared'];};?>">Prev</a> 
      <a href="modelbrowser.php?offset=<?php echo $offset+100;if (isset($_REQUEST['shared'])) {echo "&shared=".$_REQUEST['shared'];};?>">Next</a>
    </td>
  </tr>
  <tr>
    <td>
      <script>var noPicture = false</script>
      <script language="javascript" src="images_fgfsdb.js" type="text/javascript"></script>
      <div id="trailimageid" style="position:absolute;z-index:10000;overflow:visible"></div>
      <?php
echo $query;
        $result=pg_query($query);
        while ($row = pg_fetch_assoc($result)){
      ?>
        <a href="/modeledit.php?id=<?php echo $row['mo_id'];?>">


<img border="0" title="<?php echo $row['mo_name'].' ['.$row['mo_path'].']';?>" src="modelthumb.php?id=<?php echo $row['mo_id'];?>" width=100 height=75 onmouseover="showtrail('modelthumb.php?id=<?php echo $row['mo_id'];?>','','','1',5,322);" onmouseout="hidetrail();"></a>
<?php
}
?>
</td></tr>
<tr class=bottom><td colspan=9 align=center><a href="modelbrowser.php?offset=<?php echo $offset-100;if (isset($_REQUEST['shared'])) {echo "&shared=".$_REQUEST['shared'];};?>">Prev</a> <a href="modelbrowser.php?offset=<?php echo $offset+100;if (isset($_REQUEST['shared'])) {echo "&shared=".$_REQUEST['shared'];};?>">Next</a></td></tr>
</table>
</body>
</html>
