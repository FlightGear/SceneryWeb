<?php
  include("include/menu.php");

  if (isset($_REQUEST['offset']) && (preg_match('/^[0-9]+$/u',$_GET['offset']))){
    $offset = $_REQUEST['offset'];
  }else{
    $offset = 0;
  }

  $filter = "1=1";

  if (isset($_REQUEST['family']) && (preg_match('/^[0-9]+$/u',$_GET['family'])) && $_REQUEST['family']>0){
    $family = $_REQUEST['family'];
    $filter.= " and mo_shared=".$_REQUEST['family'];
  }else{
    $family = "";
  }

  if (isset($_REQUEST['author']) && (preg_match('/^[0-9]+$/u',$_GET['author'])) && $_REQUEST['author']>0){
    $author = $_REQUEST['author'];
    $filter.= " and au_name=".$_REQUEST['author'];
  }else{
    $author = "";
  }

  if (isset($_REQUEST['orderby']) && $_REQUEST['orderby']!=""){
    $orderby = $_REQUEST['orderby'];
    $filter.= " ORDER BY=".$_REQUEST['orderby'];
  }else{
    $orderby = "";
    $filter.= " ORDER BY mo_modified DESC";
  }

?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">3D Models Library</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

    <h1>Add a new 3D model</h1>
    <p>
      In order to add a new 3D model you must use this form : <a href="#">Add a new 3D model</a>
    </p>


    <h1>Update/delete 3D model</h1>
    <p>
      Currently it's not possible to update/delete a 3D model. 3D models are automatically deleted if not used (no available position).
    </p>


    <h1>Authors</h1>
    <p>
      Here is the list of all authors, thank to them :<br/>
      $LIST-OF-AUTHORS
    </p>


    <h1 id="anchor">3D models library</h1>
    <fieldset>
      <legend>Filter</legend>
      <form action="models-library.php#anchor" method="GET">
      <table width="1036px">
        <tr>
          <td>Family: </td>
          <td>
            <select name="family">
              <option value="0"></option>
              <?php
                $result = pg_query("SELECT mg_id, mg_name FROM fgs_modelgroups ORDER BY mg_name;");
                while ($row = pg_fetch_assoc($result)){
                  $name = preg_replace('/ /',"&nbsp;",$row["mg_name"]);
                  echo '<option value="'.$row["mg_id"].'"';
                  if($family==$row["mg_id"])echo " selected";
                  echo '>'.$name.'</option>\n';
                }
              ?>
            </select>
          </td>
          <td>Author: </td>
          <td>
            <select name="author">
              <option value="0"></option>
              <?php
                $result = pg_query("SELECT au_id, au_name FROM fgs_authors ORDER BY au_name ASC;");
                while ($row = pg_fetch_assoc($result)){
                  echo '<option value="'.$row["au_id"].'"';
                  if($author==$row["au_id"])echo " selected";
                  echo '>'.$row["au_name"].'</option>\n';
                }
              ?>
            </select>
          </td>
          <td>Order by: </td>
          <td>
            <select name="orderby">
              <option value="0"></option>
              <option value="mo_name" <?php if($orderby == "mo_name")echo "selected"; ?>>Name</option>
              <option value="mo_path" <?php if($orderby == "mo_path")echo "selected"; ?>>Path</option>
              <option value="au_name" <?php if($orderby == "au_name")echo "selected"; ?>>Author</option>
              <option value="mo_modified" <?php if($orderby == "mo_modified")echo "selected"; ?>>Last updated</option>
            </select>
          </td>
          <td colspan="4"><button style="float:right;">Filter</button></td>
        </tr>
      </table>
      </form>
    </fieldset>
    <br/>

    <table class="models">
      <tr>
        <th></th>
        <th>Name</th>
        <th>Path</th>
        <th>Notes</th>
        <th>Author</th>
        <th>Last updated</th>
        <th>Family</th>
        <th>Available in database</th>
      </tr>
    <?php
      $query = "SELECT mo_id, mo_name, mo_path, mo_notes, mo_author, au_name, mo_modified, mo_shared, CHAR_LENGTH(mo_modelfile) ";
      $query.= "AS mo_modelsize, mg_name, mg_id ";
      $query.= "FROM fgs_models, fgs_authors, fgs_modelgroups ";
      $query.= "WHERE ".$filter." ";
//      $query.= "ORDER BY mo_modified DESC ";
      $query.= "LIMIT 20 OFFSET ".$offset;
echo $query;
      $result=pg_query($query);
      while ($row = pg_fetch_assoc($result)){
        echo "<tr>\n";
          echo "<td>\n";
            echo "<a href=\"modeledit.php?id=".$row["mo_id"]."\"><img src=\"show-thumb.php?id=".$row["mo_id"]."\" width=\"160px\"></a>\n";
          echo "</td>\n";
          echo "<td>".$row["mo_name"]."</td>\n";
            echo "<td>".$row["mo_path"]."</td>\n";
            echo "<td>".$row["mo_notes"]."</td>\n";
            echo "<td>".$row["au_name"]."</td>\n";
            echo "<td>".$row["mo_modified"]."</td>\n";
            echo "<td>".$row["mg_name"]."</td>\n";

            if ($row["mo_modelsize"]>0){
              echo "<td>Yes</p>\n";

              if ($row["mo_shared"]==0){	
                $modelid = $row["mo_id"];
                $query = "SELECT ST_Y(wkb_geometry), ST_X(wkb_geometry) ";
                $query.= "AS ob_lat, ob_lon ";
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
              print "<td>No</td>\n";
            }

            echo "<p align=right><a href=\"modeledit.php?id=".$row["mo_id"]."\">Edit</a></p>\n";
          echo "</td>\n";
        echo "</tr>\n";
      }
    ?>
      <tr>
        <td colspan="11" align="center">
          <?php
            $prev = $offset-20;
            $next = $offset+20;
            echo "<a href=\"models-library.php?offset=".$prev."&family=".$family."&author=".$author."&orderby=".$orderby."&filter=Filter#anchor\">Prev</a> ";
            echo "<a href=\"models-library.php?offset=".$next."&family=".$family."&author=".$author."&orderby=".$orderby."&filter=Filter#anchor\">Next</a>";
          ?>
        </td>
      </tr>
    </table>
    <br/>


  </div>

</div>
<?php include("include/footer.php"); ?>
