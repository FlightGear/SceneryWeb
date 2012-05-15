<?php
  include("include/menu.php");

  if (isset($_REQUEST['offset']) && (preg_match('/^[0-9]+$/u',$_GET['offset']))){
    $offset = $_REQUEST['offset'];
  }else{
    $offset = 0;
  }

  $filter = "";

  if (isset($_REQUEST['model']) && (preg_match('/^[0-9]+$/u',$_GET['model'])) && $_REQUEST['model']>0){
    $model = $_REQUEST['model'];
    $filter.= " and ob_model=".$_REQUEST['model'];
  }else{
    $model = "";
  }

  if (isset($_REQUEST['group']) && (preg_match('/^[0-9]+$/u',$_GET['group'])) && $_REQUEST['group']>0){
    $group = $_REQUEST['group'];
    $filter.= " and ob_group=".$_REQUEST['group'];
  }else{
    $group = "";
  }

  if (isset($_REQUEST['elevation']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['elevation']))){
    $min = $_REQUEST['elevation']-25;
    $max = $_REQUEST['elevation']+25;
    $elevation = $_REQUEST['elevation'];
    $filter.= " and ob_gndelev>".$min." and ob_gndelev<".$max;
  }else{
    $elevation = "";
  }

  if (isset($_REQUEST['elevoffset']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['elevoffset']))){
    $min = $_REQUEST['elevoffset']-25;
    $max = $_REQUEST['elevoffset']+25;
    $elevoffset = $_REQUEST['elevoffset'];
    $filter.= " and ob_gndelev>".$min." and ob_gndelev<".$max;
  }else{
    $elevoffset = "";
  }

  if (isset($_REQUEST['heading']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['heading']))){
    $min = $_REQUEST['heading']-5;
    $max = $_REQUEST['heading']+5;
    $heading = $_REQUEST['heading'];
    $filter.= " AND ob_heading>".$min." AND ob_heading<".$max;
  }else{
    $heading = "";
  }

  if (isset($_REQUEST['lat']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['lat']))){
    $lat = $_REQUEST['lat'];
    $filter.= " AND ST_Y(wkb_geometry) LIKE ".$_REQUEST['lat']."";
  }else{
    $lat = "";
  }

  if (isset($_REQUEST['lon']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['lon']))){
    $lon = $_REQUEST['lon'];
    $filter.= " AND ST_X(wkb_geometry) LIKE ".$_REQUEST['lon']."";
  }else{
    $lon = "";
  }

  if (isset($_REQUEST['country']) && (preg_match('/^[a-z][a-z]$/u',$_GET['country']))){
    $country = $_REQUEST['country'];
    $filter.= " and ob_country='".$_REQUEST['country']."'";
  }else{
    $country = "";
  }

  if (isset($_REQUEST['description']) && (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u',$_GET['description']))){
    $description = $_REQUEST['description'];
    $filter.= " and (ob_text like '%".$_REQUEST['description']."\" or ob_text like \"".$_REQUEST['description']."%' or ob_text like '%".$_REQUEST['description']."%')";
  }else{
    $description = "";
  }

?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Objects position</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

    <h1>Add a new object position</h1>
    <p>
      In order to add a new object position you must use this form : <a href="#">Add a new object position</a>
    </p>


    <h1>Update/delete object position</h1>
    <p>
      In order to update/delete an object position you need to browse our objects positions library available in this page.
    </p>


    <h1>Objects positions library</h1>
    <fieldset>
      <form action="objects-position.php" method="GET">
      <legend>Filter</legend>
      <table width="1036px">
        <tr>
          <td>Latitude: </td><td><input type="text" name="lat"/></td>
          <td>Ground elevation (m): </td><td><input type="text" name="elevation"/></td>
          <td>Model: </td>
          <td>
            <select name="model">
              <option value="0"></option>
              <?php
                $result=pg_query("SELECT mo_id,mo_path FROM fgs_models ORDER BY mo_path;");
                while ($row = pg_fetch_assoc($result)){	
                  $models[$row["mo_id"]]=$row["mo_path"];
                  echo '<option value="'.$row["mo_id"].'"';
                  if ($row["mo_id"]==$model) echo " selected";
                  echo ">".$row["mo_path"]."</option>\n";
                }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td>Longitude: </td><td><input type="text" name="lon"/></td>
          <td>Elevation offset: </td><td><input type="text" name="elevoffset"/></td>
          <td>Country: </td>
          <td>
            <select name="country">
              <option value="0"></option>
              <?php
                $result=pg_query("SELECT co_code,co_name FROM fgs_countries;");
                while ($row = pg_fetch_assoc($result)){
                  $countries{$row["co_code"]}=$row["co_name"];
                  echo '<option value="'.$row["co_code"].'"';
                  if ($row["co_code"]==$country) echo " selected";
                  echo ">".$row["co_name"]."</option>\n";
                }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td>Heading: </td><td><input type="text" name="heading"/></td>
          <td>Description: </td><td><input type="text" name="description"/></td>
          <td>Group: </td>
          <td>
            <select name="group">
              <option value="0"></option>
              <?php
                $result=pg_query("SELECT gp_id,gp_name FROM fgs_groups;");
                while ($row = pg_fetch_assoc($result)){
                  $groups[$row["gp_id"]]=$row["gp_name"];
                  echo '<option value="'.$row["gp_id"].'"';
                  if ($row["gp_id"]==$group) echo " selected";
                  echo ">".$row["gp_name"]."</option>\n";
                }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="6"><button style="float:right;">Filter</button></td>
        </tr>
      </table>
      </form>
    </fieldset>
    <br/>

    <table class="objects">
      <tr>
        <th>Lat</th>
        <th>Lon</th>
        <th>Ground<br>Elevation (m)</th>
        <th>Elevation<br>Offset</th>
        <th>Heading</th>
        <th>Description</th>
        <th>Model</th>
        <th>Group</th>
        <th>Country</th>
      </tr>
      <?php
        $query = "SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon ";
        $query.= "FROM fgs_objects ";
        $query.= "WHERE ob_id IS NOT NULL ".$filter." ";
        $query.= "LIMIT 20 OFFSET ".$offset;
        $result=pg_query($query);
        while ($row = pg_fetch_assoc($result)){	
          echo "<tr class=object>\n";
            echo "<td>".$row["ob_lat"]."</td>\n";
            echo "<td>".$row["ob_lon"]."</td>\n";
            echo "<td>".$row["ob_gndelev"]."</td>\n";
            echo "<td>".$row["ob_elevoffset"]."</td>\n";
            echo "<td>".$row["ob_heading"]."</td>\n";
            echo "<td>".$row["ob_text"]."</td>\n";
            echo "<td>".$models[$row["ob_model"]]."</td>\n";
            echo "<td>".$groups[$row["ob_group"]]."</td>\n";
            echo "<td>".$countries[$row["ob_country"]]."</td>\n";
            echo "<td>\n";
              echo "<a href=\"objectedit.php?id=".$row["ob_id"]."\">Edit</a> ";
              echo "<a href=\"javascript:popmap(".$row["ob_lat"].",".$row["ob_lon"].")\">Map</a>";
            echo "</td>\n";
          echo "</tr>\n";
        }
      ?>
      <tr>
        <td colspan="11" align="center">
          <?php
            $prev = $offset-20;
            $next = $offset+20;
            echo "<a href=\"objects-position.php?offset=".$prev."&lat=".$lat."&lon=".$lon."&elevation=".$elevation."&elevoffset=".$elevoffset."&description=".$description."&heading=".$heading."&model=".$model."&group=".$group."&country=".$country."&filter=Filter"."\">Prev</a>";
            echo "<a href=\"objects-position.php?offset=".$next."&lat=".$lat."&lon=".$lon."&elevation=".$elevation."&elevoffset=".$elevoffset."&heading=".$heading."&description=".$description."&model=".$model."&group=".$group."&country=".$country."&filter=Filter"."\">Next</a>";
          ?>
        </td>
      </tr>
    </table>
    <br/>

  </div>

</div>
<?php include("include/footer.php"); ?>
