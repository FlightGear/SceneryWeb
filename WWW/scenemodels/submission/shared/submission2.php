<?php

    // Inserting libs
    require_once('../../inc/email.php');
    require_once('../../inc/functions.inc.php');

    // Checking DB availability before all
    $ok = check_availability();

    if (!$ok) {
        $page_title = "Automated Objects Pending Requests Form";
        $error_text = "Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.";
        include '../../inc/error_page.php';
        exit;
    }
    
    // Check the presence of "action", the presence of "signature", its length (64) and its content.
    if (isset($_GET["action"]) && isset($_GET["sig"]) && (strlen($_GET["sig"]) == 64) && preg_match("/[0-9a-z]/", $_GET["sig"]) && ($_GET["action"] == "check")) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != '0') {

            // Checking the presence of sig into the database
            $result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';");
            if (pg_num_rows($result) != 1) {
                $page_title = "Automated Objects Massive Import Request Form";
                $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?<br/>";
                $advise_text = "Else, please report to devel ML or FG Scenery forum.";
                include '../../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }

            if ($_GET["action"] == "check") {  // If action comes from the mass submission script
                while ($row = pg_fetch_row($result)) {
                    $sqlzbase64 = $row[1];

                    // Base64 decode the query
                    $sqlz = base64_decode($sqlzbase64);

                    // Gzuncompress the query
                    $query_rw = gzuncompress($sqlz);
                    $page_title = "Automated Objects Massive Import Requests Form";
                    include '../../inc/header.php';
                    echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $_GET["sig"].".\n</p>\n";
                    
                    $trigged_query_rw = strstr($query_rw, 'ST_PointFromText'); // Removing the start of the query from the data;
                    
                    echo "<form id=\"check_mass\" method=\"post\" action=\"submission2.php\">";
                    echo "<table>\n<tr>\n<th>Longitude</th>\n<th>Latitude</th>\n<th>Country</th>\n<th>Elevation</th>\n<th>Elev. offset</th>\n<th>True orientation</th>\n<th>Model</th>\n<th>Map</th>\n</tr>\n";
                    
                    $pattern = "/ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>(([0-9.-]+)|NULL)), (?P<orientation>[0-9.-]+), '(?P<country>[a-z]+)', (?P<model_id>[0-9]+), 1\)/";
                    
                    $error === preg_match($pattern, $trigged_query_rw, $matches);

                    $long = $matches['long'];
                    $lat = $matches['lat'];
                    $elev = $matches['elev'];
                    $elevoffset = $matches['elevoffset'];
                    $orientation = $matches['orientation'];
                    $country = $matches['country'];
                    $model_id = $matches['model_id'];

                    echo "<tr>\n" .
                         "<td><center>".$long."</center></td>\n" .
                         "<td><center>".$lat."</center></td>\n" .
                         "<td><center>".$country."</center></td>\n" .
                         "<td><center>".$elev."</center></td>\n" .
                         "<td><center>".$elevoffset."</center></td>\n" .
                         "<td><center>".$orientation."</center></td>\n" .
                         "<td><center>".object_name($model_id)."</center></td>\n" .
                         "<td><center><a href=\"http://mapserver.flightgear.org/popmap/?lon=".$long."&amp;lat=".$lat."&amp;zoom=14\">Map</a></center></td>\n" .
                         "</tr>\n";
?>
                    <tr>
                        <td colspan="3">Leave a comment to the submitter</td>
                        <td colspan="5"><input type="text" name="maintainer_comment" size="85" value="Drop a comment to the submitter" onfocus="emptyDefaultValue(this, 'Drop a comment to the submitter');"/></td>
                    </tr>
                    <tr>
                        <td colspan="8" class="submit">
                            <?php echo "<input type=\"hidden\" name=\"email\" value=\"".$_GET[email]."\" />"; ?>
                            <?php echo "<input type=\"hidden\" name=\"sig\" value=\"".$_GET[sig]."\" />"; ?>
                            <input type="submit" name="action" value="Accept" />
                            <input type="submit" name="action" value="Reject" />
                        </td>
                    </tr>
                    </table>
<?php
                    include '../../inc/footer.php';
                }
            }
        }
    }

    // Check the presence of "action", the presence of "signature", its length (64) and its content.
    if (isset($_POST["action"]) && isset($_POST["sig"]) && (strlen($_POST["sig"]) == 64) && preg_match("/[0-9a-z]/",$_POST["sig"]) && ($_POST["action"] == 'Accept')) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != '0') {

        // Checking the presence of sig into the database
            $result = @pg_query($resource_rw,"SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_POST["sig"] ."';");
            if (pg_num_rows($result) != 1) {
                $page_title = "Automated Objects Pending Requests Form";
                $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
                $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }

            if ($_POST["action"] == 'Accept') {   // If action comes from the unitary insertion script
                while ($row = pg_fetch_row($result)) {
                    $sqlzbase64 = $row[1];

                    // Base64 decode the query
                    $sqlz = base64_decode($sqlzbase64);

                    // Gzuncompress the query
                    $query_rw = gzuncompress($sqlz);

                    // Sending the request...
                    $resultrw = @pg_query($resource_rw, $query_rw);

                    if (!$resultrw) {
                        $page_title = "Automated Objects Pending Requests Form";
                        include '../../inc/header.php';
                        echo "<p class=\"center\">";
                        echo "Signature found.<br /> Now processing query with request number ". $_POST[sig].".</p><br />";
                        echo "<p class=\"center warning\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

                        // Closing the rw connection.
                        include '../../inc/footer.php';
                        pg_close($resource_rw);
                        exit;
                    }

                    $page_title = "Automated Objects Pending Requests Form";
                    include '../../inc/header.php';
                    echo "<p class=\"center\">Signature found.<br /> Now processing INSERT or DELETE or UPDATE position query with number ". $_POST[sig].".</p><br />";
                    echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

                    // Delete the entry from the pending query table.
                    $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["sig"] ."';";
                    $resultdel = @pg_query($resource_rw,$delete_request);

                    if(!resultdel) {
                        echo "<p class=\"center warning\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

                        // Closing the rw connection.
                        include '../../inc/footer.php';
                        pg_close($resource_rw);
                        exit;
                    }

                    echo "<p class=\"center ok\">Entry correctly deleted from the pending request table.</p>";

                    // Closing the rw connection.
                    pg_close($resource_rw);

                    // Sending mail if SQL was correctly inserted and entry deleted.
                    // Sets the time to UTC.
                    date_default_timezone_set('UTC');
                    $dtg = date('l jS \of F Y h:i:s A');
                    $comment = $_POST['maintainer_comment'];

                    // OK, let's start with the mail redaction.
                    // Who will receive it ?
                    if (isset($_POST['email'])) $to .= $_POST["email"];
                    else $to = "";
                    $sig = $_POST['sig'];
                    
                    email("pending_request_process_confirmation");

                    exit;
                }
            }
        }
    }

    // If it's not to validate the submission... it's to delete it... check the presence of "action", the presence of "signature", its length (64), its content.
    else {
        if (isset($_POST["action"]) && isset($_POST["sig"]) && (strlen($_POST["sig"]) == 64) && preg_match("/[0-9a-z]/",$_POST["sig"]) && ($_POST["action"] == 'Reject')) {
            $resource_rw = connect_sphere_rw();

            // If connection is OK
            if ($resource_rw != '0') {

                // Checking the presence of sig into the database
                $delete_query = "SELECT spr_hash FROM fgs_position_requests WHERE spr_hash = '". $_POST["sig"] ."';";
                $result = @pg_query($delete_query);

                // If not ok...

                if (pg_num_rows($result) != 1) {
                    $page_title = "Automated Objects Pending Requests Form";
                    $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?";
                    $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
                    include '../../inc/error_page.php';

                    // Closing the rw connection.
                    @pg_close($resource_rw);
                    exit;
                }

                // Delete the entry from the pending query table.
                $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["sig"] ."';";
                $resultdel = @pg_query($resource_rw, $delete_request);

                if (!resultdel) {
                    $page_title = "Automated Objects Pending Requests Form";
                    include '../../inc/header.php';
                    echo "<p class=\"center\">\n";
                    echo "Signature found.<br /> Now deleting request with number ". $_POST[sig].".</p>";
                    echo "<p class=\"center warning\">Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />\n";

                    // Closing the rw connection.
                    include '../../inc/footer.php';
                    pg_close($resource_rw);
                    exit;
                }

                $page_title = "Automated Objects Pending Requests Form";
                include '../../inc/header.php';
                echo "<p class=\"center\">";
                echo "Signature found.<br />Now deleting request with number ". $_POST[sig].".</p>";
                echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.";
                echo "</p>";

                // Closing the rw connection.
                include '../../inc/footer.php';
                pg_close($resource_rw);

                // Sending mail if entry was correctly deleted.
                // Sets the time to UTC.

                date_default_timezone_set('UTC');
                $dtg = date('l jS \of F Y h:i:s A');
                $comment = $_POST['maintainer_comment'];

                // OK, let's start with the mail redaction.
                // Who will receive it ?
                if(isset($_POST['email'])) $to = $_POST["email"];
                    else $to = "";
                $sig = $_POST['sig'];

                email("reject_and_deletion_confirmation");
                
                exit;
            }
        }

        // Sending the visitor elsewhere if he has no idea what he's doing here.
        else {
            header("Location: /submission/shared/");
        }
    }
?>
