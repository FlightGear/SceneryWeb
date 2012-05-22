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

    <form method="post" action="update/object.php">
      <table border="1" align="center">
        <tr>
          <td>Latitude</td>
          <td>
            <input type="text" size="12" name="lat" <?php if(isset($object["ob_lat"])) echo 'value="'.$object["ob_lat"].'"'; ?> />
          </td>
        </tr>
        <tr>
          <td>Longitude</td>
          <td>
            <input type="text" size="12" name="lon" <?php if(isset($object["ob_lon"])) echo 'value="'.$object["ob_lon"].'"'; ?> />
          </td>
        </tr>
        <tr>
          <td>Country</td>
          <td>
            <select name="country">
              <?php
                $result=pg_query("SELECT * FROM fgs_countries;");
                while ($row = pg_fetch_assoc($result)){
                  echo "<option value=\"".$row["co_code"]."\"";
                  if ($object["ob_country"]==$row["co_code"]) echo " selected";
                  echo ">".$row["co_name"]."</option>\n";
                }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td>Ground Elevation</td>
          <td>
            <input type="text" size="6" name="gndelev" <?php if(isset($object["ob_gndelev"])) echo 'value="'.$object["ob_gndelev"].'"'; ?> />
          </td>
        </tr>
        <tr>
          <td>Elevation Offset</td>
          <td>
            <input type="text" size="6" name="elevoffset" <?php if(isset($object["ob_elevoffset"])) echo 'value="'.$object["ob_elevoffset"].'"'; ?> />
          </td>
        </tr>
        <tr>
          <td>Heading</td>
          <td>
            <input type="text" size="3" name="heading" <?php if(isset($object["ob_heading"])) echo 'value="'.$object["ob_heading"].'"'; ?> />
          </td>
        </tr>
        <tr>
          <td>Group</td>
          <td>
            <select name="group">
              <?php
                $result=pg_query("SELECT * FROM fgs_groups;");
                while ($row = pg_fetch_assoc($result)){
                  echo "<option value=\"".$row["gp_id"]."\"";
                  if ($object["ob_group"]==$row["gp_id"]) echo " selected";
                  echo ">".$row["gp_name"]."</option>\n";
                }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td>Model</td>
          <td>
            <select name="model">
              <?php
                $result=pg_query("SELECT mo_id,mo_path FROM fgs_models;");
                while ($row = pg_fetch_assoc($result)){
                  echo "<option value=\"".$row["mo_id"]."\"";
                  if ($object["ob_model"]==$row["mo_id"]) echo " selected";
                  echo ">".$row["mo_path"]."</option>\n";
                }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td>Description</td>
          <td>
            <input type="text" size="40" name="text" <?php if(isset($object["ob_text"])) echo 'value="'.$object["ob_text"].'"'; ?> />
          </td>
        </tr>
        <tr>
          <td>
            <?php
              $id=$_REQUEST['id'];
              $result=pg_query("SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_id=$id;");
              while ($row = pg_fetch_assoc($result)){
                echo '<a href="javascript:popmap('.$row["ob_lat"].','.$row["ob_lon"].')">Map</a>\n';
              }
            ?>
          </td>
          <td align="center">
            <input type="hidden" name="id" value="<?php if (isset($id)) echo $id; ?>" />
            <input type="submit" name="submit" value="add" disabled="disabled" />&nbsp;
            <input type="submit" name="submit" value="update" disabled="disabled" />&nbsp;
            <input type="submit" name="submit" value="delete" disabled="disabled" />
          </td>
        </tr>
      </table>
    </form>

  </div>

</div>

<script type="text/javascript">
  function popmap(lat,lon) {
    popup = window.open("/maps?zoom=12&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
  }
</script>

<?php include("include/footer.php"); ?>
