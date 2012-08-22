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
        <th>Author</th>
        <th>Comments of the author</th>
    </tr>
<?php
    $query = "SELECT au_id, au_name, au_notes ";
    $query.= "FROM fgs_authors ";
    $query.= "ORDER BY au_name ";
    $query.= "LIMIT 20 OFFSET ".$offset;
    $result=pg_query($query);
    while ($row = pg_fetch_assoc($result)){
        echo "<tr>\n" .
                 "<td style=\"width: 25%\">\n" .
                     "<b><a href=\"author.php?id=".$row["au_id"]."\">".$row["au_name"]."</a><b/>\n" .
                 "</td>\n" .
                 "<td>".$row["au_notes"]."</td>\n" .
             "</tr>\n";
    }
?>
    <tr class="bottom">
      <td colspan="9" align="center">
        <a href="authors.php?offset=<?php echo $offset-20;?>">Prev</a> <a href="authors.php?offset=<?php echo $offset+20;?>">Next</a>
      </td>
    </tr>
  </table>
<?php include 'inc/footer.php';?>
