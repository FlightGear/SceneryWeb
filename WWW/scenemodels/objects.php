<?php
    include('inc/functions.inc.php');

    if (isset($_REQUEST['offset']) && (preg_match('/^[0-9]+$/u',$_GET['offset']))){
        $offset = $_REQUEST['offset'];
    } else {
        $offset = 0;
    }

    $filter = "";

    if (isset($_REQUEST['model']) && (preg_match('/^[0-9]+$/u',$_GET['model'])) && $_REQUEST['model']>0){
        $model = $_REQUEST['model'];
        $filter.= " AND ob_model=".$_REQUEST['model'];
    } else {
        $model = "";
    }

    if (isset($_REQUEST['group']) && (preg_match('/^[0-9]+$/u',$_GET['group'])) && $_REQUEST['group']>0){
        $group = $_REQUEST['group'];
        $filter.= " AND ob_group=".$_REQUEST['group'];
    } else {
        $group = "";
    }

    if (isset($_REQUEST['elevation']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['elevation']))){
        $min = $_REQUEST['elevation']-25;
        $max = $_REQUEST['elevation']+25;
        $elevation = $_REQUEST['elevation'];
        $filter.= " AND ob_gndelev>".$min." and ob_gndelev<".$max;
    } else {
        $elevation = "";
    }

    if (isset($_REQUEST['elevoffset']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['elevoffset']))){
        $min = $_REQUEST['elevoffset']-25;
        $max = $_REQUEST['elevoffset']+25;
        $elevoffset = $_REQUEST['elevoffset'];
        $filter.= " AND ob_gndelev>".$min." and ob_gndelev<".$max;
    } else {
        $elevoffset = "";
    }

    if (isset($_REQUEST['heading']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['heading']))){
        $min = $_REQUEST['heading']-5;
        $max = $_REQUEST['heading']+5;
        $heading = $_REQUEST['heading'];
        $filter.= " AND ob_heading>".$min." AND ob_heading<".$max;
    } else {
        $heading = "";
    }

    if (isset($_REQUEST['lat']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['lat']))){
        $lat = $_REQUEST['lat'];
        $filter.= " AND CAST (ST_Y(wkb_geometry) AS text) LIKE '".$_REQUEST['lat']."%'";
    } else {
        $lat = "";
    }

    if (isset($_REQUEST['lon']) && (preg_match('/^[0-9\.\-]+$/u',$_GET['lon']))){
        $lon = $_REQUEST['lon'];
        $filter.= " AND CAST (ST_X(wkb_geometry) AS text) LIKE '".$_REQUEST['lon']."%'";
    } else {
        $lon = "";
    }

    if (isset($_REQUEST['country']) && (preg_match('/^[a-z][a-z]$/u',$_GET['country']))){
        $country = $_REQUEST['country'];
        $filter.= " AND ob_country='".$_REQUEST['country']."'";
    } else {
        $country = "";
    }

    if (isset($_REQUEST['description']) && (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u',$_GET['description']))){
        $description = $_REQUEST['description'];
        $filter.= " AND (ob_text like '%".$_REQUEST['description']."\" OR ob_text LIKE \"".$_REQUEST['description']."%' OR ob_text LIKE '%".$_REQUEST['description']."%')";
    } else {
        $description = "";
    }

    include('inc/header.php');
?>
<script type="text/javascript">
  function popmap(lat,lon) {
    popup = window.open("http://mapserver.flightgear.org/popmap?zoom=12&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
  }
</script>

<form action="objects.php" method="get">
  <table>
    <tr valign="bottom">
        <th>ID</th>
        <th>Lat<br/>Lon<br/><input type="text" name="lat" size="12" <?php echo "value=\"".$lat."\""; ?>/>
          <br/><input type="text" name="lon" size="12" <?php echo "value=\"".$lon."\""; ?>/></th>
        <th>Ground&nbspElevation<br/>+ Offset (m)<br/><input type="text" name="elevation" size="6" <?php echo "value=\"".$elevation."\""; ?>/>
          <br/><input type="text" name="elevoffset" size="6" <?php echo "value=\"".$elevoffset."\""; ?>/></th>
        <th>Heading<br/><input type="text" name="heading" size="3" <?php echo "value=\"".$heading."\""; ?>/></th>
        <th>Description<br/><input type="text" name="description" size="12" <?php echo "value=\"".$description."\""; ?>/></th>
        <th>
        Model<br/>
        Group<br/>
        <select name="model" style="font-size: 0.7em;">
          <option value="0"></option>
<?php
          $result = pg_query("SELECT mo_id, mo_path FROM fgs_models ORDER BY mo_path;");
          while ($row = pg_fetch_assoc($result)) {
              $models[$row["mo_id"]] = $row["mo_path"];
              echo "<option value=\"".$row["mo_id"]."\"";
              if ($row["mo_id"] == $model)
                  echo " selected=\"selected\"";
              echo ">".$row["mo_path"]."</option>\n";
          }
?>
        </select>
        <br/>
        <select name="group" style="font-size: 0.7em;">
          <option value="0"></option>
<?php
            $result = pg_query("SELECT gp_id, gp_name FROM fgs_groups;");
            while ($row = pg_fetch_assoc($result)){
                $groups[$row["gp_id"]] = $row["gp_name"];
                echo "<option value=\"".$row["gp_id"]."\"";
                if ($row["gp_id"] == $group)
                    echo " selected=\"selected\"";
                echo ">".$row["gp_name"]."</option>\n";
            }
?>
        </select>
      </th>
      <th>
        Country<br/>
        <select name="country" style="font-size: 0.7em;">
          <option value="0"></option>
<?php
            $result = pg_query("SELECT co_code,co_name FROM fgs_countries;");
            while ($row = pg_fetch_assoc($result)){
              $countries{$row["co_code"]}=$row["co_name"];
              echo "<option value=\"".$row["co_code"]."\"";
              if ($row["co_code"] == $country) echo " selected=\"selected\"";
              echo ">".$row["co_name"]."</option>\n";
            }
?>
        </select>
      </th>
      <th><input type="submit" name="filter" value="Filter"/></th>
    </tr>
    <tr class="bottom">
      <td colspan="8" align="center">
<?php
        $prev = $offset-20;
        $next = $offset+20;

        $filter_text="";
        if($lat != "") $filter_text .= "&amp;lat=".$lat;
        if($lon != "") $filter_text .= "&amp;lon=".$lon;
        if($elevation != "") $filter_text .= "&amp;elevation=".$elevation;
        if($elevoffset != "") $filter_text .= "&amp;elevoffset=".$elevoffset;
        if($description != "") $filter_text .= "&amp;description=".$description;
        if($heading != "") $filter_text .= "&amp;heading=".$heading;
        if($model != 0) $filter_text .= "&amp;model=".$model;
        if($group != 0) $filter_text .= "&amp;group=".$group;
        if($country != "") $filter_text .= "&amp;country=".$country;

        echo "<a href=\"objects.php?filter=Filter&amp;offset=".$prev . $filter_text."\">&lt;&lt; Previous</a>&nbsp;&nbsp;";
        echo "<a href=\"objects.php?filter=Filter&amp;offset=".$next . $filter_text."\">Next &gt;&gt;</a>";
?>
      </td>
    </tr>
<?php
      $query = "SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon ";
      $query.= "FROM fgs_objects ";
      $query.= "WHERE ob_id IS NOT NULL ".$filter." ";
      $query.= "LIMIT 20 OFFSET ".$offset;

      $result = pg_query($query);
      while ($result && $row = pg_fetch_assoc($result)) {
          echo "<tr class=\"object\">\n";
          echo "  <td><a href='http://scenemodels.flightgear.org/objectedit.php?id=".$row["ob_id"]."'>#".$row["ob_id"]."</a></td>\n";
          echo "  <td>".$row["ob_lat"]."<br/>".$row["ob_lon"]."</td>\n";
          $offset = ($row["ob_elevoffset"] == "")?"0":$row["ob_elevoffset"];
          echo "  <td>".$row["ob_gndelev"]."<br/>".$offset."</td>\n";
          echo "  <td>".$row["ob_heading"]."</td>\n";
          echo "  <td>".$row["ob_text"]."</td>\n";
          echo "  <td>".$models[$row["ob_model"]]."<br/><b>".$groups[$row["ob_group"]]."</b></td>\n";
          echo "  <td>".$countries[$row["ob_country"]]."</td>\n";
          echo "  <td>\n";
            if (is_shared_or_static($row["ob_id"]) == 'shared') {
?>
                <a href="submission/shared/check_update_shared.php?update_choice=<?php echo $row["ob_id"]; ?>">Update</a>
                <br/>
                <a href="submission/shared/check_delete_shared.php?delete_choice=<?php echo $row["ob_id"]; ?>">Delete</a>
<?php
            }
          echo "    <a href=\"javascript:popmap(".$row["ob_lat"].",".$row["ob_lon"].")\">Map</a>";
          echo "  </td>\n";
          echo "</tr>\n";
      }
?>
    <tr class="bottom">
      <td colspan="7" align="center">
<?php
          echo "<a href=\"objects.php?filter=Filter&amp;offset=".$prev . $filter_text."\">&lt;&lt; Previous</a>&nbsp;&nbsp;";
          echo "<a href=\"objects.php?filter=Filter&amp;offset=".$next . $filter_text."\">Next &gt;&gt;</a>";
?>
      </td>
    </tr>
  </table>
</form>

<?php include 'inc/footer.php';?>
