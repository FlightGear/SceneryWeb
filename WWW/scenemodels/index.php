<?php

require 'inc/header.php';

if (isset($_REQUEST['offset']) && preg_match('/^[0-9]+$/u', $_REQUEST['offset'])) {
    $offset = $_REQUEST['offset'];
}
else {
    $offset = 0;
}
?>

    <h1>FlightGear Scenery Website</h1>

    <p>Welcome to the <a href="http://www.flightgear.org">FlightGear</a> scenery website!</p>
    <p>This website is used to share common tools and data for all FlightGear scenery related items. It also features webforms to help gathering 3D models and objects positions all around the world. You can here contribute to FlightGear scenery by adding objects in your favorite place. Please don't hesitate, your help is welcomed!</p>
  
    <table class="left">
        <tr><th colspan="2">Recently updated objects</th></tr>
<?php
        $query = "SELECT ob_id, ob_text, ob_model, to_char(ob_modified,'YYYY-mm-dd (HH24:MI)') AS ob_datedisplay " .
                 "FROM fgs_objects " .
                 "ORDER BY ob_modified DESC " .
                 "LIMIT 5";
        $result = pg_query($query);
        while ($row = pg_fetch_assoc($result)) {
            echo "<tr>\n" .
                    "<td><a href=\"objectview.php?id=".$row["ob_id"]."\">".$row["ob_text"]."</a><br/>" .
                    $row["ob_datedisplay"]."</td>\n" .
                    "<td style=\"width: 100px; padding: 0px;\">".
                    "<a href=\"/objectview.php?id=". $row['ob_id'] . "\">" .
                    "    <img title=\"". $row['ob_text']."\"" .
                    "    src=\"modelthumb.php?id=". $row['ob_model'] . "\" width=\"100\" height=\"75\" style=\"vertical-align: middle\"" .
                    "    alt=\"\" />" .
                    "</a>" .
                    "</td>\n" .
                 "</tr>\n";
        }
?>
        <tr class="bottom">
            <td colspan="2" align="center">
                <a href="objects.php">More recently updated objects</a>
            </td>
        </tr>
    </table>
    <table class="right">
        <tr><th colspan="2">Recently updated models</th></tr>
<?php
        $query = "SELECT mo_id, mo_name, mo_path, to_char(mo_modified,'YYYY-mm-dd (HH24:MI)') AS mo_datedisplay " .
                 "FROM fgs_models " .
                 "ORDER BY mo_modified DESC " .
                 "LIMIT 5";
        $result = pg_query($query);
        while ($row = pg_fetch_assoc($result)){
            echo "<tr>\n" .
                    "<td><a href=\"modelview.php?id=".$row["mo_id"]."\">".$row["mo_name"]."</a><br/>" .
                    $row["mo_datedisplay"]. "</td>\n" .
                    "<td style=\"width: 100px; padding: 0px;\">".
                    "<a href=\"/modelview.php?id=". $row['mo_id'] ."\">" .
                    "    <img title=\"". $row['mo_name'].' ['.$row['mo_path'].']'."\"" .
                    "    src=\"modelthumb.php?id=". $row['mo_id'] ."\" width=\"100\" height=\"75\" style=\"vertical-align: middle\"" .
                    "    alt=\"\" />" .
                    "</a>" .
                    "</td>\n" .
                "</tr>\n";
        }
?>
        <tr class="bottom">
            <td colspan="2" align="center">
                <a href="models.php">More recently updated models</a>
            </td>
        </tr>
    </table>
    <div class="clear"></div>
  
<?php require 'inc/footer.php';?>
