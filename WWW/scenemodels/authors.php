<?php include 'inc/header.php';?>
<?php
      if (isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u',$_REQUEST['offset'])){
          $offset = $_REQUEST['offset'];
      } else {
          $offset = 0;
      }
?>

  <h1>FlightGear Scenery Authors Directory</h1>
  
  <table>
    <tr class="bottom">
      <td colspan="9" align="center">
        <a href="authors.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="authors.php?offset=<?php echo $offset+10;?>">Next</a>
       </td>
    </tr>
<?php
      $query = "SELECT * ";
      $query.= "FROM fgs_authors ";
      $query.= "ORDER BY au_name ";
      $query.= "LIMIT 10 OFFSET ".$offset;
      $result=pg_query($query);
      while ($row = pg_fetch_assoc($result)){
        echo "<tr>\n";
          echo "<td width=\"25%\">\n";
            echo "<b>Name: <a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</a></b>\n";
            echo "<p><b>EMail:</b> *disabled*</p>\n";
          echo "</td>\n";
          echo "<td>".$row["au_notes"]."</td>\n";
        echo "</tr>\n";
      }
?>
    <tr class="bottom">
      <td colspan="9" align="center">
        <a href="authors.php?offset=<?php echo $offset-10;?>">Prev</a> <a href="authors.php?offset=<?php echo $offset+10;?>">Next</a>
      </td>
    </tr>
  </table>
<?php include 'inc/footer.php';?>
