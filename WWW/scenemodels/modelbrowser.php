<?php
include 'inc/header.php';

if (isset($_REQUEST['offset']) && (preg_match('/^[0-9]+$/u',$_GET['offset']))) {
  $offset = $_REQUEST['offset'];
  if ($offset<0) $offset=0;
}
else {
  $offset = 0;
}

if (isset($_REQUEST['shared']) && (preg_match('/^[0-9]+$/u',$_GET['shared']))) {
    if ($_REQUEST['shared']>0) {
        $groupquery = "SELECT mg_name FROM fgs_modelgroups WHERE mg_id =".$_REQUEST['shared'].";";
        $gpresult = pg_query($groupquery);
        $row = pg_fetch_assoc($gpresult);
        $title = "Model Browser: ".$row['mg_name'];
        $query = "SELECT mo_id, mo_path, mo_name FROM fgs_models WHERE mo_shared = ".$_REQUEST['shared']." ORDER BY mo_id LIMIT 99 OFFSET $offset;";
    }
    else {
    $title = "FlightGear Scenery Static Model Browser";
    $query = "SELECT mo_id, mo_path, mo_name FROM fgs_models WHERE mo_shared = 0 ORDER BY mo_id LIMIT 99 OFFSET $offset;";
    }
}
else {
    $query = "SELECT mo_id, mo_path, mo_name FROM fgs_models ORDER BY mo_id LIMIT 99 OFFSET $offset;";
    $title = "FlightGear Scenery Model Browser";
}
?>

<h1><?php echo $title;?></h1>
<table>
    <tr class="bottom">
        <td colspan="9" align="center">
        <a href="modelbrowser.php?offset=<?php echo $offset-99;if (isset($_REQUEST['shared'])) {echo "&shared=".$_REQUEST['shared'];};?>">Prev</a>
        <a href="modelbrowser.php?offset=<?php echo $offset+99;if (isset($_REQUEST['shared'])) {echo "&shared=".$_REQUEST['shared'];};?>">Next</a>
        </td>
    </tr>
    <tr>
        <td>
        <script type="text/javascript">var noPicture = false</script>
        <script src="inc/js/images_fgfsdb.js" type="text/javascript"></script>
        <div id="trailimageid" style="position:absolute;z-index:10000;overflow:visible"></div>
        <?php
            $result=pg_query($query);
            while ($row = pg_fetch_assoc($result)) {
        ?>
            <a href="/modelview.php?id=<?php echo $row['mo_id'];?>">
            <img title="<?php echo $row['mo_name'].' ['.$row['mo_path'].']';?>"
                src="modelthumb.php?id=<?php echo $row['mo_id'];?>" width="100" height="75"
                onmouseover="showtrail('modelthumb.php?id=<?php echo $row['mo_id'];?>','','','1',5,322);"
                onmouseout="hidetrail();"
                alt="" />
        </a>
        <?php
        }
        ?>
        </td>
    </tr>
    <tr class="bottom">
        <td colspan="9" align="center">
        <a href="modelbrowser.php?offset=<?php echo $offset-99;if (isset($_REQUEST['shared'])) {echo "&shared=".$_REQUEST['shared'];};?>">Prev</a>
        <a href="modelbrowser.php?offset=<?php echo $offset+99;if (isset($_REQUEST['shared'])) {echo "&shared=".$_REQUEST['shared'];};?>">Next</a>
        </td>
    </tr>
</table>
<?php include 'inc/footer.php';?>
