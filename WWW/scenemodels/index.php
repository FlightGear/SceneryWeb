<?php include 'inc/header.php';?>
<?php
    if (isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u', $_REQUEST['offset'])) {
        $offset = $_REQUEST['offset'];
    }
    else {
        $offset = 0;
    }
?>

  <h1>FlightGear Scenery Database Latest News</h1>
  
<?php
    $query = "SELECT *, date_trunc('seconds',ne_timestamp) AS formdate ";
    $query.= "FROM fgs_news, fgs_authors ";
    $query.= "WHERE au_id = ne_author ";
    $query.= "ORDER BY ne_timestamp DESC ";
    $query.= "LIMIT 10 OFFSET ".$offset;
    $result = pg_query($query);
    while ($row = pg_fetch_assoc($result)) {
        echo "<div class=\"paragraph_bloc\">\n" .
             "<div class=\"header\"><div class=\"date\">\n" .
             "Posted : ".$row["formdate"]."</div>" .
             "<div class=\"normal\">by</div><div class=\"author\"><a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</a></div><div class=\"clear\"></div></div>\n" .
             "<div class=\"body\">".$row["ne_text"]."</div>\n" .
             "</div>\n";
    }
?>
  <table>  
    <tr class="bottom">
        <td colspan="9" align="center">
        <a href="index.php?offset=<?php echo $offset-10;?>">&lt;&lt; Previous</a> <a href="index.php?offset=<?php echo $offset+10;?>">Next &gt;&gt;</a>
        </td>
    </tr>
  </table>
<?php include 'inc/footer.php';?>
