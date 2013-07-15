<?php
include "inc/header.php";
require_once "inc/functions.inc.php";
?>

<script type="text/javascript">
  function popmap(lat,lon,zoom) {
    popup = window.open("http://mapserver.flightgear.org/popmap?zoom="+zoom+"&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
  }
</script>

<h1>Models without thumbnail</h1>
<?php
$resource_r = connect_sphere_r();
$query = "SELECT COUNT(*) AS number " .
         "FROM fgs_models " .
         "WHERE mo_thumbfile IS NULL;";
$result = pg_query($query);
$number= pg_fetch_assoc($result);
?>
<p>This page lists models that lack a thumbnail. There are currently <?php echo $number['number']; ?> of such models in the database. Please help us bringing this number to 0!</p>

  <table>
<?php
    if(isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u',$_GET['offset'])){
        $offset = $_REQUEST['offset'];
    } else {
        $offset = 0;
    }
?>
    <tr class="bottom">
        <td colspan="2" align="center">
            <?php 
            if ($offset >= 20) {
                echo "<a href=\"modelsnothumb.php?offset=".($offset-20)."\">Prev</a> | ";
            }
            ?>
            <a href="modelsnothumb.php?offset=<?php echo $offset+20;?>">Next</a>
        </td>
    </tr>
<?php
    $query = "SELECT mo_id, mo_name, mo_path, mo_notes, mo_author, mo_thumbfile, au_name, to_char(mo_modified,'YYYY-mm-dd (HH24:MI)') AS mo_datedisplay, mo_shared, CHAR_LENGTH(mo_modelfile) ";
    $query.= "AS mo_modelsize, mg_name, mg_id ";
    $query.= "FROM fgs_models, fgs_authors, fgs_modelgroups ";
    $query.= "WHERE mo_author=au_id AND mo_shared=mg_id AND mo_thumbfile IS NULL ";
    $query.= "ORDER BY mo_modified DESC ";
    $query.= "LIMIT 20 OFFSET ".$offset;
    $result=pg_query($query);
    $odd = true;
    while ($row = pg_fetch_assoc($result)){
        if ($odd) {
            echo "<tr>\n";
        }
        echo "<td>\n" .
             "<ul class=\"table\">" .
             "<li><b>Name:</b> ".$row["mo_name"]."</li>\n" .
             "<li><b>Path:</b> ".$row["mo_path"]."</li>\n";
        if (!empty($row["mo_notes"])) {
            echo "<li><b>Notes:</b> ".$row["mo_notes"]."</li>\n";
        }
        echo "<li><b>Author: </b><a href=\"author.php?id=".$row["mo_author"]."\">".$row["au_name"]."</a></li>\n" .
             "<li><b>Last Updated: </b>".$row["mo_datedisplay"]."</li>\n" .
             "<li><b>Type: </b><a href=\"modelbrowser.php?shared=".$row["mg_id"]."\">".$row["mg_name"]."</a></li>\n";

        if ($row["mo_shared"] == 0) {
            $modelid = $row["mo_id"];
            $query = "SELECT ST_Y(wkb_geometry) AS ob_lat, " .
                     "ST_X(wkb_geometry) AS ob_lon, " .
                     "fn_SceneDir(wkb_geometry) AS dir " .
                     "FROM fgs_objects " .
                     "WHERE ob_model=".$modelid;
            $chunks = pg_query($query);

            while ($chunk = pg_fetch_assoc($chunks)) {             
                echo "<li>(<a href=\"download/".$chunk["dir"].".tgz\">".$chunk["dir"]."</a>) ";
                echo "<a href=\"javascript:popmap(".$chunk["ob_lat"].",".$chunk["ob_lon"].",13)\">Map</a></li>\n";
            }
        }

        echo "<li><a href=\"modelview.php?id=".$row["mo_id"]."\">View more about this model.</a></li>\n";
        echo "</ul>";
        echo "</td>\n";
        if (!$odd) {
            echo "</tr>\n";
            $odd = true;
        } else {
            $odd = false;
        }
    }
    ?>
    <tr class="bottom">
        <td colspan="2" align="center">
            <?php 
            if ($offset >= 20) {
                echo "<a href=\"modelsnothumb.php?offset=".($offset-20)."\">Prev</a> | ";
            }
            ?>
            <a href="modelsnothumb.php?offset=<?php echo $offset+20;?>">Next</a>
        </td>
    </tr>
  </table>
<?php include 'inc/footer.php';?>
