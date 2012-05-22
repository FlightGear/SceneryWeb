<?php
  if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id']))){
    include("include/menu.php");

    $id     = $_REQUEST['id'];
    $result = pg_query("SELECT * FROM fgs_models WHERE mo_id=$id;");
    $model  = pg_fetch_assoc($result);
  }else{
    header("Location: http://scenery.flightgear.org");
  }
?>

<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Models library editor</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

    <form method=post action=update/model.php>
      <table>
        <tr>
          <td rowspan="8">
            <img src="modelthumb.php?id=<?php if(isset($model['mo_id'])) echo $model['mo_id']; ?>" width="320px" />
          </td>
          <td>Name</td>
          <td>
            <input type="text" size="40" name="name" <?php if(isset($model["mo_name"])) echo 'value="'.$model["mo_name"].'"'; ?> />
          </td>
        </tr>
        <tr>
          <td>Path</td>
          <td>
            <input type="text" size="40" name="path" <?php if(isset($model["mo_path"])) echo 'value="'.$model["mo_path"].'"'; ?> />
          </td>
        </tr>
        <tr>
          <td>Type</td>
          <td>
            <select name="shared">
              <option value="0">
              <?php
                $result=pg_query("SELECT mg_id,mg_name FROM fgs_modelgroups;");
                while ($row = pg_fetch_assoc($result)){
                  echo "<option value=\"".$row["mg_id"]."\"";
                  if ($row["mg_id"]==$model["mo_shared"]) echo " selected";
                  echo ">".$row["mg_name"]."\n";
                }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td>Author</td>
          <td>
            <select name="author">
            <?php
              $result=pg_query("SELECT * FROM fgs_authors;");
              while ($row = pg_fetch_assoc($result)){
                echo "<option value=\"".$row["au_id"]."\"";
                if ($model["mo_author"]==$row["au_id"]) echo " selected";
                echo ">".$row["au_name"]."\n";
              }
            ?>
            </select>
          </td>
        </tr>
        <tr>
          <td>Last Updated</td>
          <td>
            <input type="text" size="15" name="updated" <?php if(isset($model["mo_modified"])) echo 'value="'.$model["mo_modified"].'"'; ?>">
          </td>
        </tr>
        <tr>
          <td>Model-ID</td>
          <td><?php echo "$id"; ?></td>
        </tr>
        <tr>
          <td colspan="2">
            <?php
              $result=pg_query("SELECT mo_shared FROM fgs_models WHERE mo_id = $id;");
              while ($row = pg_fetch_assoc($result)){
                if ($row["mo_shared"]==0){	
                  $chunks=pg_query("SELECT ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon FROM fgs_objects WHERE ob_model=$id");
                  while ($chunk = pg_fetch_assoc($chunks)){
                    $lat=floor($chunk["ob_lat"]/10)*10;
                    $lon=floor($chunk["ob_lon"]/10)*10;
                    echo "<a href=\"javascript:popmap(".$chunk["ob_lat"].",".$chunk["ob_lon"].",13)\">Map</a>&nbsp;-&nbsp";
                  }
                }
              }
            ?>
            <a href="show-file.php?id=<?php echo $id; ?>">Download Model</a>
            &nbsp;-&nbsp;
            <a href="update/thumbupload.php?id=<?php echo $id; ?>">Upload Thumbnail</a>
            &nbsp;-&nbsp;
            <a href="update/modelupload.php?id=<?php echo $id; ?>">Upload Model</a>

            <?php
              $result=pg_query("SELECT mo_author,au_name FROM fgs_models, fgs_authors WHERE mo_author=au_id AND mo_id = $id;");
              while ($row = pg_fetch_assoc($result)){
                echo "<br>List all of <a href=\"models-library.php?family=&author=".$row["mo_author"]."&orderby=0#anchor\">".$row["au_name"]."</a>";
              }
            ?>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <textarea cols="80" rows="10" name="notes"><?php if (isset($model["mo_notes"])) echo $model["mo_notes"]; ?></textarea>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <input type="hidden" name="id" value="<?php if(isset($id)) echo $id;?>" />
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
