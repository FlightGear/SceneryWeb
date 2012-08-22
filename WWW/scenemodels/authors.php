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
    <tr>
        <th>Author</td>
        <th>Comments of the author</td>
    </tr>

<?php
      $query = "SELECT au_id, au_name, au_notes ";
      $query.= "FROM fgs_authors ";
      $query.= "ORDER BY au_name ";
      $query.= "LIMIT 20 OFFSET ".$offset;
      $result=pg_query($query);
      while ($row = pg_fetch_assoc($result)){
        echo "<tr>\n";
          echo "<td style=\"width: 25%\">\n";
            echo "<b><a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</a><b/>\n";
          echo "</td>\n";
          echo "<td>".$row["au_notes"]."</td>\n";
        echo "</tr>\n";
      }
?>
    <tr class="bottom">
      <td colspan="9" align="center">
        <a href="authors.php?offset=<?php echo $offset-20;?>">Prev</a> <a href="authors.php?offset=<?php echo $offset+20;?>">Next</a>
      </td>
    </tr>
  </table>
<?php include 'inc/footer.php';?>
