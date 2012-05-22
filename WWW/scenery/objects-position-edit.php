<?php
  include("include/menu.php");

  if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id']))){
    $id=$_REQUEST['id'];
    $result=pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
    $object=pg_fetch_assoc($result);
  }
?>

<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Objects position editor</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">



  </div>

</div>

<script type="text/javascript">
  function popmap(lat,lon) {
    popup = window.open("/maps?zoom=12&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
  }
</script>

<?php include("include/footer.php"); ?>
