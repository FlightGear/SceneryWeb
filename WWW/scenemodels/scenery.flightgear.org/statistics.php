<?php include("include/menu.php"); ?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Statistics</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

  <h1>Objects scenery coverage</h1>
  <p>In this map you can see all objects already present in our database.</p>
  <ul>Legend:
    <li><span style="color:purple;">Shared objects</span></li>
    <li><span style="color:red;">Static objects</span></li>
  </ul>
  <br/>
  <center><iframe src="http://scenemodels.flightgear.org/maps/index.php?lat=10&lon=0&zoom=2" width="720px" height="450px"></iframe></center>
  <br/>
  <br/>

  <h1>Objects scenery statistics</h1>
<?php 
  $result=pg_query("SELECT count(mo_id) AS count FROM fgs_models;");
  $row = pg_fetch_assoc($result);
  $models=$row["count"];
  $result=pg_query("SELECT count(ob_id) AS count FROM fgs_objects;");
  $row = pg_fetch_assoc($result);
  $objects=$row["count"];
  $result=pg_query("SELECT count(si_id) AS count FROM fgs_signs;");
  $row = pg_fetch_assoc($result);
  $signs=$row["count"];

  echo '<p>The database currently contains <a href="#">'.$models.' models</a> placed in the scenery as <a href="#">'.$objects.' positionned objects</a>, plus '.$signs.' taxiway signs.</p>';
?>

      <table class="statistics">
        <th colspan="2">Objects By Country</th>
        <?php
          $query = "SELECT count(ob_id) AS count,co_name,co_code ";
          $query.= "FROM fgs_objects,fgs_countries ";
          $query.= "WHERE ob_country=co_code ";
          $query.= "GROUP BY co_code,co_name ";
          $query.= "ORDER BY count DESC ";
          $query.= "LIMIT 20";
          $result = pg_query($query);
          while ($row = pg_fetch_assoc($result)){
            echo "<tr>\n";
              echo "<td><a href=\"objects.php?country=".$row["co_code"]."\">".$row["co_name"]."</a></td>\n";
              echo "<td>".$row["count"]."</td>\n";
            echo "</tr>\n";
          }
        ?>
      </table>

      <table class="statistics">
        <th colspan="2">Models By Author</th>
        <?php
          $query = "SELECT count(mo_id) as count,au_name,au_id ";
          $query.= "FROM fgs_models,fgs_authors ";
          $query.= "WHERE mo_author=au_id ";
          $query.= "GROUP BY au_id,au_name ";
          $query.= "ORDER BY count DESC ";
          $query.= "LIMIT 20";
          $result = pg_query($query);
          while ($row = pg_fetch_assoc($result)){
            echo "<tr>\n";
              echo "<td><a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</td>\n";
              echo "<td>".$row["count"]."</td>\n";
            echo "</tr>\n";
          }
        ?>
      </table>

      <table class="statistics">
        <th colspan="2">Recently Updated Objects</th>
        <?php
          $query = "SELECT ob_id,ob_text,ob_modified ";
          $query.= "FROM fgs_objects ";
          $query.= "ORDER BY ob_modified DESC ";
          $query.= "LIMIT 10";
          $result = pg_query($query);
          while ($row = pg_fetch_assoc($result)){
            echo "<tr>\n";
              echo "<td><a href=\"objectedit.php?id=".$row["ob_id"]."\">".$row["ob_text"]."</td>\n";
              echo "<td>".$row["ob_modified"]."</td>\n";
            echo "</tr>\n";
          }
        ?>
      </table>

      <table class="statistics">
        <th colspan="2">Recently Updated Models</th>
        <?php
          $query = "SELECT mo_id,mo_name,mo_modified ";
          $query.= "FROM fgs_models ";
          $query.= "ORDER BY mo_modified DESC ";
          $query.= "LIMIT 10";
          $result = pg_query($query);
          while ($row = pg_fetch_assoc($result)){
            echo "<tr>\n";
              echo "<td><a href=\"modeledit.php?id=".$row["mo_id"]."\">".$row["mo_name"]."</td>\n";
              echo "<td>".$row["mo_modified"]."</td>\n";
            echo "</tr>\n";
          }
        ?>
      </table>

  </div>

</div>
<?php include("include/footer.php"); ?>
