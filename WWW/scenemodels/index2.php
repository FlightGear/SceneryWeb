<?php include 'inc/header.php';?>
<?php
    if (isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u', $_REQUEST['offset'])) {
        $offset = $_REQUEST['offset'];
    }
    else {
        $offset = 0;
    }
?>

  <h1>FlightGear Scenery Database</h1>
  
  <p>Welcome to the FlightGear scenery models database!</p>
  <p>The website is used to gather all 3D models and positions of objects around the world! You can here contribute to FlightGear scenery by adding objects in your favorite place! Please don't hesitate, your help is welcomed!</p>
  
  <table>
    <tr><th colspan="2">Latest news</th></tr>
<?php
    $query = "SELECT *, date_trunc('seconds',ne_timestamp) AS formdate ";
    $query.= "FROM fgs_news, fgs_authors ";
    $query.= "WHERE au_id = ne_author ";
    $query.= "ORDER BY ne_timestamp DESC ";
    $query.= "LIMIT 5 OFFSET ".$offset;
    $result = pg_query($query);
    while ($row = pg_fetch_assoc($result)) {
        echo "<tr><td>\n" .
             "<div class=\"newsdate\">".$row["formdate"]."</div>\n" .
             "<div class=\"newsnormal\">by</div><div class=\"newsauthor\"><a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</a></div><div class=\"clear\"></div><hr/>\n" .
             "".$row["ne_text"]."</td></tr>\n";
    }
?>

    <tr class="bottom">
        <td colspan="9" align="center">
            <?php 
            if ($offset >= 3) {
                echo "<a href=\"index.php?offset=".($offset-3)."\">&lt; Newer news</a> | ";
            }
            ?>
            <a href="index.php?offset=<?php echo $offset+3;?>">Older news &gt;</a>
        </td>
    </tr>
  </table>
  
  <table class="float">
      <tr><th colspan="2">Recently updated objects</th></tr>
<?php
        $query = "SELECT ob_id, ob_text, ob_model, to_char(ob_modified,'YYYY-mm-dd (HH24:MI)') AS ob_datedisplay " .
                 "FROM fgs_objects " .
                 "ORDER BY ob_modified DESC " .
                 "LIMIT 10";
        $result = pg_query($query);
        while ($row = pg_fetch_assoc($result)) {
            echo "<tr>\n" .
                    "<td><a href=\"objectview.php?id=".$row["ob_id"]."\">".$row["ob_text"]."</a><br/>" .
                    $row["ob_datedisplay"]."</td>\n" .
                    "<td>".
                    "<a href=\"/modelview.php?id=". $row['ob_model'] . "\">" .
                    "    <img title=\"". $row['ob_text']."\"" .
                    "    src=\"modelthumb.php?id=". $row['ob_model'] . "\" width=\"100\" height=\"75\"" .
                    "    alt=\"\" />" .
                    "</a>" .
                    "</td>\n" .
                 "</tr>\n";
        }
?>
  </table>
  <table class="float">
      <tr><th colspan="2">Recently updated models</th></tr>
<?php
        $query = "SELECT mo_id, mo_name, mo_path, to_char(mo_modified,'YYYY-mm-dd (HH24:MI)') AS mo_datedisplay " .
                 "FROM fgs_models " .
                 "ORDER BY mo_modified DESC " .
                 "LIMIT 10";
        $result = pg_query($query);
        while ($row = pg_fetch_assoc($result)){
            echo "<tr>\n" .
                    "<td><a href=\"modelview.php?id=".$row["mo_id"]."\">".$row["mo_name"]."</a><br/>" .
                    $row["mo_datedisplay"]. "</td>\n" .
                    "<td>".
                    "<a href=\"/modelview.php?id=". $row['mo_id'] ."\">" .
                    "    <img title=\"". $row['mo_name'].' ['.$row['mo_path'].']'."\"" .
                    "    src=\"modelthumb.php?id=". $row['mo_id'] ."\" width=\"100\" height=\"75\"" .
                    "    alt=\"\" />" .
                    "</a>" .
                    "</td>\n" .
                "</tr>\n";
        }
?>
  </table>
  <div class="clear"></div><br/>
  
<?php include 'inc/footer.php';?>
