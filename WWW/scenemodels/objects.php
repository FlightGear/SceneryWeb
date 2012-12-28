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
            <th>Description</th>
            <th>Model<br/>Group</th>
            <th>Country</th>
            <th>Lat<br/>Lon</th>
            <th>Ground&nbsp;elev.<br/>Offset (m)</th>
            <th>Heading</th>
            <th>&nbsp;</th>
        </tr>
        <tr valign="bottom">
            <th>&nbsp;</th>
            <th><input type="text" name="description" size="12" <?php echo "value=\"".$description."\""; ?>/></th>
            <th>
                <select name="model" style="font-size: 0.7em; width: 100%">
                    <option value="0"></option>
                    <?php
                    $result = pg_query("SELECT mo_id, mo_path FROM fgs_models ORDER BY mo_path;");
                    while ($row = pg_fetch_assoc($result)) {
                        $models[$row["mo_id"]] = $row["mo_path"];
                        echo "<option value=\"".$row["mo_id"]."\"";
                        if ($row["mo_id"] == $model) echo " selected=\"selected\"";
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
                        if ($row["gp_id"] == $group) echo " selected=\"selected\"";
                        echo ">".$row["gp_name"]."</option>\n";
                    }
                ?>
                </select>
            </th>
            <th>
                <select name="country" style="font-size: 0.7em; width: 100%">
                    <option value="0"></option>
                    <?php
                    $result = pg_query("SELECT co_code, co_name FROM fgs_countries ORDER BY co_name;");
                    while ($row = pg_fetch_assoc($result)){
                        $countries{$row["co_code"]}=$row["co_name"];
                        echo "<option value=\"".$row["co_code"]."\"";
                        if ($row["co_code"] == $country) echo " selected=\"selected\"";
                        echo ">".$row["co_name"]."</option>\n";
                    }
                    ?>
                </select>
            </th>
            <th><input type="text" name="lat" size="12" <?php echo "value=\"".$lat."\""; ?>/>
              <br/><input type="text" name="lon" size="12" <?php echo "value=\"".$lon."\""; ?>/></th>
            <th><input type="text" name="elevation" size="6" <?php echo "value=\"".$elevation."\""; ?>/>
              <br/><input type="text" name="elevoffset" size="6" <?php echo "value=\"".$elevoffset."\""; ?>/></th>
            <th><input type="text" name="heading" size="3" <?php echo "value=\"".$heading."\""; ?>/></th>
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

                if ($prev >= 0) {
                    echo "<a href=\"objects.php?filter=Filter&amp;offset=".$prev . $filter_text."\">Prev</a> | ";
                }
                ?>
                <a href="objects.php?filter=Filter&amp;offset=<?php echo $next . $filter_text;?>">Next</a>
            </td>
        </tr>
<?php
        $query = "SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon " .
                 "FROM fgs_objects " .
                 "WHERE ob_id IS NOT NULL ".$filter." " .
                 "ORDER BY ob_modified DESC " .
                 "LIMIT 20 OFFSET ".$offset;

        $result = pg_query($query);
        $rowclass;
        while ($result && $row = pg_fetch_assoc($result)) {
            $offset = ($row["ob_elevoffset"] == "")?"0":$row["ob_elevoffset"];
            echo "<tr class=\"object".$rowclass."\">\n";
            echo "  <td><a href='objectview.php?id=".$row["ob_id"]."'>#".$row["ob_id"]."</a></td>\n" .
                 "  <td>".$row["ob_text"]."</td>\n" .
                 "  <td><a href=\"modelview.php?id=".$row["ob_model"]."\">".$models[$row["ob_model"]]."</a><br/>".$groups[$row["ob_group"]]."</td>\n" .
                 "  <td>".$countries[$row["ob_country"]]."</td>\n" .
                 "  <td>".$row["ob_lat"]."<br/>".$row["ob_lon"]."</td>\n" .
                 "  <td>".$row["ob_gndelev"]."<br/>".$offset."</td>\n" .
                 "  <td>".$row["ob_heading"]."</td>\n" .
                 "  <td style=\"width: 58px; text-align: center\">\n" .
                 "  <a href=\"submission/shared/check_update_shared.php?update_choice=".$row["ob_id"]."\"><img class=\"icon\" src=\"http://scenery.flightgear.org/img/icons/edit.png\"/></a>";
            if (is_shared_or_static($row["ob_id"]) == 'shared') {
?>
                <a href="submission/shared/check_delete_shared.php?delete_choice=<?php echo $row["ob_id"]; ?>"><img class="icon" src="http://scenery.flightgear.org/img/icons/delete.png"/></a>
<?php
            }
            echo "    <a href=\"javascript:popmap(".$row["ob_lat"].",".$row["ob_lon"].")\"><img class=\"icon\" src=\"http://scenery.flightgear.org/img/icons/world.png\"/></a>" .
                 "  </td>\n" .
                 "</tr>\n";
            if ($rowclass == "") {
                $rowclass = "light";
            } else {
                $rowclass = "";
            }
        }
?>
        <tr class="bottom">
            <td colspan="7" align="center">
                <?php
                if ($prev >= 0) {
                    echo "<a href=\"objects.php?filter=Filter&amp;offset=".$prev . $filter_text."\">Prev</a> | ";
                }
                ?>
                <a href="objects.php?filter=Filter&amp;offset=<?php echo $next . $filter_text;?>">Next</a>
            </td>
        </tr>
    </table>
</form>

<?php include 'inc/footer.php';?>
