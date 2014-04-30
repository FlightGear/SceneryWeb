<?php
require_once 'inc/functions.inc.php';
require_once 'inc/form_checks.php';
require_once 'classes/DAOFactory.php';
require_once 'classes/Criterion.php';

$objectDAO = DAOFactory::getInstance()->getObjectDaoRO();

require 'inc/header.php';

$filter_text="";
$criteria = array();
$pagesize = 20;

if (isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u',$_REQUEST['offset'])){
    $offset = $_REQUEST['offset'];
} else {
    $offset = 0;
}


if (isset($_REQUEST['model']) && is_model_id($_REQUEST['model'])){
    $model = $_REQUEST['model'];
    $filter_text .= "&amp;model=".$model;
    
    $criteria[] = new Criterion("ob_model", Criterion::OPERATION_EQ, $model);
} else {
    $model = "";
}

if (isset($_REQUEST['groupid']) && (preg_match('/^[0-9]+$/u',$_REQUEST['groupid'])) && $_REQUEST['groupid']>0){
    $groupid = $_REQUEST['groupid'];
    $filter_text .= "&amp;groupid=".$groupid;
    
    $criteria[] = new Criterion("ob_group", "=", $groupid);
} else {
    $groupid = "";
}

if (isset($_REQUEST['elevation']) && is_gndelevation($_REQUEST['elevation'])){
    $min = $_REQUEST['elevation']-25;
    $max = $_REQUEST['elevation']+25;
    $elevation = $_REQUEST['elevation'];
    $filter_text .= "&amp;elevation=".$elevation;
    
    $criteria[] = new Criterion("ob_gndelev", Criterion::OPERATION_GT, $min);
    $criteria[] = new Criterion("ob_gndelev", Criterion::OPERATION_LT, $max);
} else {
    $elevation = "";
}

if (isset($_REQUEST['elevoffset']) && is_offset($_REQUEST['elevoffset'])){
    $min = $_REQUEST['elevoffset']-25;
    $max = $_REQUEST['elevoffset']+25;
    $elevoffset = $_REQUEST['elevoffset'];
    $filter_text .= "&amp;elevoffset=".$elevoffset;
    
    $criteria[] = new Criterion("ob_elevoffset", Criterion::OPERATION_GT, $min);
    $criteria[] = new Criterion("ob_elevoffset", Criterion::OPERATION_LT, $max);
} else {
    $elevoffset = "";
}

if (isset($_REQUEST['heading']) && is_heading($_REQUEST['heading'])){
    $min = $_REQUEST['heading']-5;
    $max = $_REQUEST['heading']+5;
    $heading = $_REQUEST['heading'];
    $filter_text .= "&amp;heading=".$heading;
    
    $criteria[] = new Criterion("ob_heading", Criterion::OPERATION_GT, $min);
    $criteria[] = new Criterion("ob_heading", Criterion::OPERATION_LT, $max);
} else {
    $heading = "";
}

if (isset($_REQUEST['lat']) && is_latitude($_REQUEST['lat'])){
    $lat = $_REQUEST['lat'];
    $filter_text .= "&amp;lat=".$lat;
    
    $criteria[] = new Criterion("CAST (ST_Y(wkb_geometry) AS text)", Criterion::OPERATION_LIKE, "'".$lat."%'");
} else {
    $lat = "";
}

if (isset($_REQUEST['lon']) && is_longitude($_REQUEST['lon'])){
    $lon = $_REQUEST['lon'];
    $filter_text .= "&amp;lon=".$lon;
    
    $criteria[] = new Criterion("CAST (ST_X(wkb_geometry) AS text)", Criterion::OPERATION_LIKE, "'".$lon."%'");
} else {
    $lon = "";
}

if (isset($_REQUEST['country']) && is_country_id($_REQUEST['country'])){
    $countryId = $_REQUEST['country'];
    $filter_text .= "&amp;country=".$countryId;
    
    $criteria[] = new Criterion("ob_country", Criterion::OPERATION_EQ, $countryId);
} else {
    $countryId = "";
}

if (isset($_REQUEST['description']) && (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u',$_REQUEST['description']))){
    $description = $_REQUEST['description'];
    $filter_text .= "&amp;description=".$description;
    
    $criteria[] = new Criterion("ob_text", Criterion::OPERATION_LIKE, "'%".$_REQUEST['description']."%'");
} else {
    $description = "";
}

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
                <select name="groupid" style="font-size: 0.7em;">
                    <option value="0"></option>
<?php
                    $result = pg_query("SELECT gp_id, gp_name FROM fgs_groups;");
                    while ($row = pg_fetch_assoc($result)){
                        $groups[$row["gp_id"]] = $row["gp_name"];
                        echo "<option value=\"".$row["gp_id"]."\"";
                        if ($row["gp_id"] == $groupid) echo " selected=\"selected\"";
                        echo ">".$row["gp_name"]."</option>\n";
                    }
?>
                </select>
            </th>
            <th>
                <select name="country" style="font-size: 0.7em; width: 100%">
                    <option value="0"></option>
<?php
                    $countries = $objectDAO->getCountries();
                    
                    foreach ($countries as $country){
                        echo "<option value=\"".$country->getCode()."\"";
                        if ($country->getCode() == $countryId) echo " selected=\"selected\"";
                        echo ">".$country->getName()."</option>\n";
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
                $prev = $offset-$pagesize;
                $next = $offset+$pagesize;

                if ($prev >= 0) {
                    echo "<a href=\"objects.php?filter=Filter&amp;offset=".$prev . $filter_text."\">Prev</a> | ";
                }
?>
                <a href="objects.php?filter=Filter&amp;offset=<?php echo $next . $filter_text;?>">Next</a>
            </td>
        </tr>
<?php
        
        $objects = $objectDAO->getObjects($pagesize, $offset, $criteria);
        
        foreach ($objects as $object) {
            $offset = $object->getElevationOffset();
            echo "<tr class=\"object\">\n";
            echo "  <td><a href='objectview.php?id=".$object->getId()."'>#".$object->getId()."</a></td>\n" .
                 "  <td>".$row["ob_text"]."</td>\n" .
                 "  <td><a href=\"modelview.php?id=".$object->getModelId()."\">".$models[$object->getModelId()]."</a><br/>".$groups[$object->getGroupId()]."</td>\n" .
                 "  <td>".$object->getCountry()->getName() ."</td>\n" .
                 "  <td>".$object->getLatitude()."<br/>".$object->getLongitude()."</td>\n" .
                 "  <td>".$object->getGroundElevation()."<br/>".$offset."</td>\n" .
                 "  <td>".$object->getOrientation()."</td>\n" .
                 "  <td style=\"width: 58px; text-align: center\">\n" .
                 "  <a href=\"submission/object/check_update.php?update_choice=".$object->getId()."\"><img class=\"icon\" src=\"http://scenery.flightgear.org/img/icons/edit.png\"/></a>";
            if (is_shared_or_static($object->getId()) == 'shared') {
?>
                <a href="submission/object/check_delete_shared.php?delete_choice=<?php echo $object->getId(); ?>">
                    <img class="icon" src="http://scenery.flightgear.org/img/icons/delete.png" alt="delete"/>
                </a>
<?php
            }
            echo "    <a href=\"javascript:popmap(".$object->getLatitude().",".$object->getLongitude().")\"><img class=\"icon\" src=\"http://scenery.flightgear.org/img/icons/world.png\"/></a>" .
                 "  </td>\n" .
                 "</tr>\n";
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

<?php require 'inc/footer.php';?>
