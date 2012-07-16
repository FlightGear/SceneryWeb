<?php include("inc/header.php");?>

<script type="text/javascript">
  function popmap(lat,lon,zoom) {
    popup = window.open("http://mapserver.flightgear.org/popmap?zoom="+zoom+"&lat="+lat+"&lon="+lon, "map", "height=500,width=500,scrollbars=no,resizable=no");
    popup.focus();
  }
</script>

<h1>FlightGear Scenery Model Directory</h1>

  <table>
    <?php
      if(isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u',$_GET['offset'])){
        $offset=$_REQUEST['offset'];
      }else{
        $offset=0;
      }
    ?>
    <tr class="bottom">
      <td colspan="9" align="center">
        <a href="models.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="models.php?offset=<?php echo $offset+10;?>">Next</a>
      </td>
    </tr>
    <?php
      $query = "SELECT mo_id, mo_name, mo_path, mo_notes, mo_author, au_name, mo_modified, mo_shared, CHAR_LENGTH(mo_modelfile) ";
      $query.= "AS mo_modelsize, mg_name, mg_id ";
      $query.= "FROM fgs_models, fgs_authors, fgs_modelgroups ";
      $query.= "WHERE mo_author=au_id AND mo_shared=mg_id ";
      $query.= "ORDER BY mo_modified DESC ";
      $query.= "LIMIT 10 OFFSET ".$offset;
      $result=pg_query($query);
      while ($row = pg_fetch_assoc($result)){
        echo "<tr>\n";
          echo "<td width=\"320\">\n";
            echo "<a href=\"modeledit.php?id=".$row["mo_id"]."\"><img src=\"modelthumb.php?id=".$row["mo_id"]."\" alt=\"Model ".$row["mo_id"]."\"/></a>\n";
          echo "</td>\n";
          echo "<td>\n";
            echo "<p><b>Name:</b> ".$row["mo_name"]."</p>\n";
            echo "<p><b>Path:</b> ".$row["mo_path"]."</p>\n";
            echo "<p><b>Notes:</b> ".$row["mo_notes"]."</p>\n";
            echo "<p><b>Author: </b><a href=\"author.php?id=".$row["mo_author"]."\">".$row["au_name"]."</a></p>\n";
            echo "<p><b>Last Updated: </b>".$row["mo_modified"]."</p>\n";
            echo "<p><b>Type: </b><a href=\"modelbrowser.php?shared=".$row["mg_id"]."\">".$row["mg_name"]."</a></p>\n";

            if ($row["mo_modelsize"]>0){
              echo "<p><b>Model: </b>Available in database</p>\n";

              if ($row["mo_shared"]==0){	
                $modelid = $row["mo_id"];
                $query = "SELECT ST_Y(wkb_geometry) AS ob_lat, ";
                $query.= "ST_X(wkb_geometry) AS ob_lon ";
                $query.= "FROM fgs_objects ";
                $query.= "WHERE ob_model=".$modelid;
                $chunks=pg_query($query);
                while ($chunk = pg_fetch_assoc($chunks)){
                  $lat=floor($chunk["ob_lat"]/10)*10;
                  $lon=floor($chunk["ob_lon"]/10)*10;
                  
                  if ($lon < 0){
                    $lon=sprintf("w%03d", 0-$lon);
                  }else{
                    $lon=sprintf("e%03d", $lon);
                  }

                  if ($lat < 0){
                    $lat=sprintf("s%02d", 0-$lat);
                  }else{
                    $lat=sprintf("n%02d", $lat);
                  }

                  echo " (<a href=\"download/".$lon.$lat.".tgz\">".$lon.$lat."</a>) ";
                  echo "<a href=\"javascript:popmap(".$chunk["ob_lat"].",".$chunk["ob_lon"].",13)\">Map</a>\n";
                }
              }
            }else{
              print "<p><b>Model: </b>Not present in database</p>\n";
            }

            echo "<p align=\"right\"><a href=\"modeledit.php?id=".$row["mo_id"]."\">Edit</a></p>\n";
          echo "</td>\n";
        echo "</tr>\n";
      }
    ?>
    <tr class="bottom">
      <td colspan="9" align="center">
        <a href="models.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="models.php?offset=<?php echo $offset+10;?>">Next</a>
      </td>
    </tr>
  </table>
<?php include 'inc/footer.php';?>
