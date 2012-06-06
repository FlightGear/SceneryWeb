<?php include("include/menu.php"); ?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Home</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

    <h1>Positions Objects Validation</h1>

    <table class="objects">
      <tr>
        <th width="82px">Lat</th>
        <th width="81px">Lon</th>
        <th width="90px">Ground Elevation (m)</th>
        <th width="72px">Elevation Offset</th>
        <th width="64px">Heading</th>
        <th>Description</th>
        <th>Model</th>
        <th width="68px"></th>
      </tr>
      <?php
        $query = "SELECT *, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon ";
        $query.= "FROM fgs_objects, fgs_models ";
        $query.= "WHERE ob_id IS NOT NULL AND mo_id=ob_model AND valid=1";
        $result=pg_query($query);
        while ($row = pg_fetch_assoc($result)){	
          echo "<tr class=object>\n";
            echo "<td align=\"center\">".$row["ob_lat"]."</td>\n";
            echo "<td align=\"center\">".$row["ob_lon"]."</td>\n";
            echo "<td align=\"center\">".$row["ob_gndelev"]."</td>\n";
            echo "<td align=\"center\">".$row["ob_elevoffset"]."</td>\n";
            echo "<td align=\"center\">".$row["ob_heading"]."</td>\n";
            echo "<td>".$row["ob_text"]."</td>\n";
            echo "<td>".$models[$row["ob_model"]]."</td>\n";
            echo "<td align=\"center\"><a href=\"show-objects-validation.php?id=".$row["ob_id"]."\">Show</a></td>\n";
          echo "</tr>\n";
        }
      ?>
    </table>

  </div>

</div>
<?php include("include/footer.php"); ?>
