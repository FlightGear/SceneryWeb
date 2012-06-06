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

    <h1>3D Models Validation</h1>
    <table class="models">
      <tr>
        <th>Name</th>
        <th>Path</th>
        <th>Notes</th>
        <th>Author</th>
        <th></th>
      </tr>
    <?php
      $query = "SELECT mo_id, mo_name, mo_path, mo_notes, mo_author, au_name, mo_modified, mo_shared, CHAR_LENGTH(mo_modelfile) ";
      $query.= "AS mo_modelsize, mg_name, mg_id ";
      $query.= "FROM fgs_models, fgs_authors, fgs_modelgroups ";
      $query.= "WHERE mo_author=au_id AND mo_shared=mg_id AND valid=0";
      $result=pg_query($query);
      while ($row = pg_fetch_assoc($result)){
        echo "<tr>\n";
          echo "<td align=\"center\">".$row["mo_name"]."</td>\n";
          echo "<td align=\"center\">".$row["mo_path"]."</td>\n";
          echo "<td>".$row["mo_notes"]."</td>\n";
          echo "<td align=\"center\">".$row["au_name"]."</td>\n";
          echo "<td align=\"center\"><a href=\"show-models-validation.php?id=".$row["mo_id"]."\">Show</a></td>\n";
        echo "</tr>\n";
      }
    ?>
    </table>

  </div>

</div>
<?php include("include/footer.php"); ?>
