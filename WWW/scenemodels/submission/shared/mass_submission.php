<?php

    // Inserting libs
    require_once('../../inc/functions.inc.php');

    // Checking DB availability before all
    $ok = check_availability();

    if (!$ok) {
        $page_title = "Automated Objects Massive Import Request Form";
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
                    echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $_GET[sig].".\n</p>\n";
                    
                    $trigged_query_rw = str_replace("INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_group) VALUES (","",$query_rw); // Removing the start of the query from the data;
                    $tab_tags = explode(", (",$trigged_query_rw); // Separating the data based on the ST_PointFromText existence
                    echo "<form id=\"check_mass\" method=\"post\" action=\"mass_submission.php\">";
                    echo "<table>\n<tr>\n<th>Line #</th>\n<th>Longitude</th>\n<th>Latitude</th>\n<th>Elevation</th>\n<th>Elev. offset</th>\n<th>True orientation</th>\n<th>Model</th>\n<th>Map</th>\n</tr>\n";
                    $i = 1;
                    foreach ($tab_tags as $value_tag) {
                        $pattern = "/'', ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>[0-9.-]+), (?P<orientation>[0-9.-]+), (?P<model_id>[0-9]+), 1\)/";
                    
                        $error === preg_match($pattern, $value_tag, $matches);

                        $long = $matches['long'];
                        $lat = $matches['lat'];
                        $elev = $matches['elev'];
                        $elevoffset = $matches['elevoffset'];
                        $orientation = $matches['orientation'];
                        $model_id = $matches['model_id'];
                        
                        echo "<tr>\n" .
                             "<td><center>".$i."</center></td>\n" .
                             "<td><center>".$long."</center></td>\n" .
                             "<td><center>".$lat."</center></td>\n" .
                             "<td><center>".$elev."</center></td>\n" .
                             "<td><center>".$elevoffset."</center></td>\n" .
                             "<td><center>".$orientation."</center></td>\n" .
                             "<td><center>".object_name($model_id)."</center></td>\n" .
                             "<td><center><a href=\"http://mapserver.flightgear.org/submap/?lon=".$long."&amp;lat=".$lat."&amp;zoom=14\">Map</a></center></td>\n" .
                             "</tr>\n";

                        $i++;
                    }
?>
                    <tr>
                    <td colspan="8" class="submit">
                        <?php echo "<input type=\"hidden\" name=\"email\" value=\"".$_GET[email]."\" />"; ?>
                        <?php echo "<input type=\"hidden\" name=\"hsig\" value=\"".$_GET[sig]."\" />"; ?>
                        <input type="submit" name="submit" value="Submit the mass import!" />
                        <input type="submit" name="cancel" value="Reject - Do not import!" />
                    </td>
                    </tr>
                    </table>
<?php
                    include '../../inc/footer.php';
                }
            }
        }
    }

    // Managing the cancellation of a mass import by DB maintainer.
    if (isset($_POST["cancel"]) && isset($_POST["hsig"]) && (strlen($_POST["hsig"]) == 64) && preg_match("/[0-9a-z]/", $_POST["hsig"]) && ($_POST["cancel"] == "Reject - Do not import!")) {

         $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != 0) {

            // Checking the presence of sig into the database
            $delete_query = "SELECT spr_hash FROM fgs_position_requests WHERE spr_hash = '". $_POST["hsig"] ."';";
            $result = @pg_query($delete_query);

            // If not ok...
            if (pg_num_rows($result) != 1) {
                $page_title = "Automated Objects Massive Import Request Form";
                $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?<br/>";
                $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
                include '../../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }
            else {
                // Delete the entry from the pending query table.
                $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["hsig"] ."';";
                $resultdel = @pg_query($resource_rw,$delete_request);

                if(!resultdel) {
                    $page_title = "Automated Objects Massive Import Request Form";
                    $process_text = "Signature found.<br /> Now deleting request with number ". $_POST["hsig"].".";
                    $error_text = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                    include '../../inc/error_page.php';

                    // Closing the rw connection.
                    pg_close($resource_rw);
                    exit;
                }

                $page_title = "Automated Objects Massive Import Request Form";
                include '../../inc/header.php';
                echo "<center>Signature found.<br />Now deleting request with number ". $_POST["hsig"].".</center><br />";
                echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.</p>";

                // Closing the rw connection.
                pg_close($resource_rw);

                // Sending mail if entry was correctly deleted.
                // Sets the time to UTC.
                date_default_timezone_set('UTC');
                $dtg = date('l jS \of F Y h:i:s A');

                // OK, let's start with the mail redaction.
                // Who will receive it ?
                $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
                if (isset($_POST["email"])) {
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, " ;
                    $to .= $_POST["email"];
                }
                else {
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>";
                }

                // What is the subject ?
                $subject = "[FG Scenery Submission forms] Automatic objects massive import DB reject and deletion confirmation.";

                // Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
                $message0 = "Hi,"  . "\r\n" .
                            "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                            "http://".$_SERVER['SERVER_NAME']."/submission/mass_submission.php"  . "\r\n" .
                            "I just wanted to let you know that the objects massive insertion request nr:"  . "\r\n" .
                            "" .$_POST[hsig]. ""."\r\n" .
                            "has been rejected and successfully deleted from the pending requests table.";

                $message = wordwrap($message0, 77, "\r\n");

                // Preparing the headers.
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "From: \"FG Scenery Pending Requests forms\" <martin.spott@mgras.net>" . "\r\n";
                $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

                // Let's send it ! No management of mail() errors to avoid being too talkative...
                @mail($to, $subject, $message, $headers);
                include '../../inc/footer.php';
                exit;
            }
        }
    }

    // Now managing the insertion
    if (isset($_POST["submit"]) && isset($_POST["hsig"]) && (strlen($_POST["hsig"]) == 64) && preg_match("/[0-9a-z]/", $_POST["hsig"]) && ($_POST["submit"] == "Submit the mass import!")) {

        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != 0) {

            // Checking the presence of sig into the database
            $result = @pg_query($resource_rw,"SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_POST["hsig"] ."';");
            if (pg_num_rows($result) != 1) {
                $page_title = "Automated Objects Massive Import Request Form";
                $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
                $advise_text = "Else, please report to devel ML or FG Scenery forum";
                include '../../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }

            while ($row = pg_fetch_row($result)) {
                $sqlzbase64 = $row[1];

                // Base64 decode the query
                $sqlz = base64_decode($sqlzbase64);

                // Gzuncompress the query
                $query_rw = gzuncompress($sqlz);

                // ####################################################################################################################################################
                // We have to work on the ob_text field here (working on it before make ob_text hard to parse to show to the maintainer).
                // Sorry, this means we have to explode the request once more and, line per line, find the model name and set ob_text='".object_name($model_name)."'
                // and rebuild the query, taking care of the presence of " or , in the ob_text field (while I did not do it in the single addition.
                // ####################################################################################################################################################

                $trigged_query_rw = str_replace("INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_group)","",$query_rw); // Removing the start of the query from the data;
                $tab_tags = explode(", (",$trigged_query_rw); // Separating the data based on the ST_PointFromText existence
                $i = 1;
                foreach ($tab_tags as $value_tag) {
                    $pattern = "/'', ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>[0-9.-]+), (?P<orientation>[0-9.-]+), (?P<model_id>[0-9]+), 1\)/";
                    
                    $error === preg_match($pattern, $value_tag, $matches);

                    $long = $matches['long'];
                    $lat = $matches['lat'];
                    $elevation = $matches['elev'];
                    $elevoffset = $matches['elevoffset'];
                    $orientation = $matches['orientation'];
                    $model = $matches['model_id'];
                    $ob_text = object_name($model);

                    $data_rw[$i] = "('".pg_escape_string($ob_text)."', ST_PointFromText('POINT(".$long." ".$lat.")', 4326), ".$elevation.", ".elevoffset.", ".$orientation.", ".$model.", 1)";

                    $i++;
                }

                $query_rw = "INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_group) VALUES ";
                for ($j = 1; $j<$i; $j++) { // For each line, add the data content to the request
                    if ($j == ($i-1)) {
                        $data_query_rw = $data_query_rw.$data_rw[$j].";";
                    }
                    else {
                        $data_query_rw = $data_query_rw.$data_rw[$j].", ";
                    }
                }
                $mass_rw_query = $query_rw.$data_query_rw;

                // ###########################################################################################################################

                // Sending the request...
                $result_rw = @pg_query($resource_rw, $mass_rw_query);

                if (!$result_rw) {
                    $page_title = "Automated Objects Massive Insertion Request Form";
                    include '../../inc/header.php';
                    echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $_POST[hsig].".</p><br />";
                    echo "<p class=\"warning\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

                    // Closing the rw connection.
                    include '../../inc/footer.php';
                    pg_close($resource_rw);
                    exit;
                }

                $page_title = "Automated Objects Massive Insertion Request Form";
                include '../../inc/header.php';
                echo "<p class=\"center\">Signature found.<br /> Now processing INSERT or DELETE or UPDATE position query with number ". $_POST[hsig].".</p><br />\n";
                echo "<p class=\"center ok\">".pg_affected_rows($result_rw)." objects were added to the database!</p>\n";
                echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

                // Delete the entry from the pending query table.
                $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["hsig"] ."';";
                $resultdel = @pg_query($resource_rw, $delete_request);

                if (!resultdel) {
                    echo "<p class=\"warning\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

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

                // OK, let's start with the mail redaction.
                // Who will receive it ?
                $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
                if (isset($_POST['email'])) {
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                    $to .= $_POST["email"];
                }
                else {
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                }

                // What is the subject ?
                $subject = "[FG Scenery Submission forms] Automatic Objects massive DB pending request process confirmation.";

                // Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
                $message0 = "Hi,"  . "\r\n" .
                            "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                            "http://".$_SERVER['SERVER_NAME']."/submission/mass_submission.php"  . "\r\n" .
                            "I just wanted to let you know that the objects massive insertion request nr :" . "\r\n" .
                            $_POST[hsig]. "\r\n" .
                            "has been successfully treated in the fgs_objects table." . "\r\n" .
                            "The corresponding pending entry has consequently been deleted" . "\r\n" .
                            "from the pending requests table." . "\r\n" .
                            "The corresponding entries will added in Terrasync" . "\r\n" .
                            "at 1230Z today or tomorrow is this time has already passed." . "\r\n" .
                            "You can follow Terrasync's data update at the following url: " . "\r\n" .
                            "http://code.google.com/p/terrascenery/source/list" . "\r\n" . "\r\n" .
                            "Thanks for your help in making FG better!";
                $message = wordwrap($message0, 77, "\r\n");

                // Preparing the headers.
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "From: \"FG Scenery Pending Requests forms\" <martin.spott@mgras.net>" . "\r\n";
                $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";


                // Let's send it ! No management of mail() errors to avoid being too talkative...
                @mail($to, $subject, $message, $headers);
                include '../../inc/footer.php';
                exit;
            }
        }
    }
?>
