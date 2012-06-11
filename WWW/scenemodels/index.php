<?php include 'header.php';?>

  <h1 align="center">FlightGear Scenery Database Latest News</h1>
  <table border="1" width="100%">
    <?php
      if (isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u',$_GET['offset'])){
        $offset = $_REQUEST['offset'];
      }else{
        $offset = 0;
      }

      $query = "SELECT *, date_trunc('seconds',ne_timestamp) AS formdata ";
      $query.= "FROM fgs_news, fgs_authors ";
      $query.= "WHERE au_id = ne_author ";
      $query.= "ORDER BY ne_timestamp DESC ";
      $query.= "LIMIT 10 OFFSET ".$offset;
      $result=pg_query($query);
      while ($row = pg_fetch_assoc($result)){
        echo "<tr>\n";
         echo "<th>\n";
            echo "<b><i>Posted : ".$row["formdate"]."</i> by <a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</a></b>\n";
         echo "</th>\n";
        echo "</tr>\n";
        echo "<tr>\n";
          echo "<td>".$row["ne_text"]."</td>\n";
        echo "</tr>\n";
      }
    ?>
    <tr class="bottom">
      <td colspan="9" align="center">
        <a href="index.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="news.php?offset=<?php echo $offset+10;?>">Next</a>
      </td>
    </tr>
  </table>
<?php include 'footer.php';?>
