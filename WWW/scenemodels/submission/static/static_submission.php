<?php
if ((isset($_POST["action"]))) {
    // Inserting libs
    require_once ('../../inc/functions.inc.php');
    $page_title = "Automated Models Submission Form";
    include '../../inc/header.php';

    // Prepare a generic mail

    // If $action=reject
        // - Drop fgs_position_requests;
        // - Send 2 mails

    if ($_POST["action"] == "Reject model") {
        echo "<center>Deleting corresponding pending query.</center>";
            if ((isset($_POST["ob_sig"])) && (isset($_POST["mo_sig"]))) {
                $resource_rw = connect_sphere_rw();

                // If connection is OK
                if ($resource_rw != '0') {

                // Checking the presence of sig into the database
                $ob_result = @pg_query($resource_rw, "select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_POST["ob_sig"] ."';");
                $mo_result = @pg_query($resource_rw, "select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_POST["mo_sig"] ."';");
                if ((pg_num_rows($ob_result) != 1) || (pg_num_rows($mo_result) != 1)) {
                    echo "<center>";
                    echo "<font color=\"red\">Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?</font><br />\n";
                    echo "Else, please report to fg-devel ML or FG Scenery forum<br />.";
                    echo "</center>";
                    include '../../inc/footer.php';
                    @pg_close($resource_rw);
                    exit;
                }
                else {
                    // Delete the entry from the pending query table.
                    $ob_delete_request = "delete from fgs_position_requests where spr_hash = '". $_POST["ob_sig"] ."';";
                    $mo_delete_request = "delete from fgs_position_requests where spr_hash = '". $_POST["mo_sig"] ."';";
                    $ob_resultdel = @pg_query($resource_rw, $ob_delete_request);
                    $mo_resultdel = @pg_query($resource_rw, $mo_delete_request);

                    if ((!$ob_resultdel) || (!$mo_resultdel)) {
                        echo "<center>\n";
                        echo "Signature found.<br /> Now deleting requests with number ". $_POST["ob_sig"]." and ". $_POST["mo_sig"].".<br />";
                        echo "<font color=\"red\">Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</font><br />\n";
                        echo "</center>\n";

                        // Closing the rw connection.
                        include '../../inc/footer.php';
                        pg_close($resource_rw);
                        exit;
                    }
                    else {
                        echo "<center>";
                        echo "Signature found.<br />Now deleting request with number ". $_POST["ob_sig"]." and ". $_POST["mo_sig"]." with comment \"". $_POST["maintainer_comment"] ."\".<br />";
                        echo "<font color=\"green\">Entries have correctly been deleted from the pending requests table.</font>";
                        echo "</center>";

                        // Closing the rw connection.
                        include '../../inc/footer.php';
                        pg_close($resource_rw);

                        // Sending mail if entry was correctly deleted.
                        // Sets the time to UTC.

                        date_default_timezone_set('UTC');
                        $dtg = date('l jS \of F Y h:i:s A');

                        // OK, let's start with the mail redaction.
                        // Who will receive it ?
                        $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
                        if(isset($_POST['email'])) {
                            // $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                            $to .= $_POST["email"];
                        }
                        else {
                            // $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                        }

                        // What is the subject ?
                        $subject = "[FG Scenery Submission forms] Automatic 3D model insertion DB reject and deletion confirmation.";

                        // Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
                        $message0 = "Hi,"  . "\r\n" .
                                    "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                                    "http://scenemodels.flightgear.org/static/static_submission.php"  . "\r\n" .
                                    "I just wanted to let you know that the 3D model import named Blah."."\r\n" .
                                    "has been rejected and successfully deleted from the pending requests table"."\r\n" .
                                    "with the following comment :\"".$_POST["maintainer_comment"]."\"."."\r\n" .
                                    "We're sorry about this. Please use the maintainer's comment to enhance or"."\r\n" .
                                    "correct your model before submitting it again.";

                        $message = wordwrap($message0, 77, "\r\n");

                        // Preparing the headers.
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "From: \"FG Scenery Pending Requests forms\" <martin.spott@mgras.net>" . "\r\n";
                        $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

                        // Let's send it ! No management of mail() errors to avoid being too talkative...
                        @mail($to, $subject, $message, $headers);
                        exit;
                        }
                    }
                    echo "The user submission has been rejected with the following warning: ".$_POST["maintainer_comment"].". User has been informed by mail.";
    exit;
    }}}

    // If $action=accept
        // - Execute both requests
        // - Send 2 mails

    if ($_POST["action"] == "Submit model") {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != '0') {

        // Checking the presence of sig into the database
            $result = @pg_query($resource_rw,"select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_POST["sig"] ."';");
            if (pg_num_rows($result) != 1) {
                echo "<center>";
                echo "<font color=\"red\">Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?</font><br />\n";
                echo "Else, please report to fg-devel ML or FG Scenery forum<br />.";
                echo "</center>";
                @pg_close($resource_rw);
                include '../../inc/footer.php';
                exit;
            }
            else {
                    while ($row = pg_fetch_row($result)) {
                        $sqlzbase64 = $row[1];

                        // Base64 decode the query
                        $sqlz = base64_decode($sqlzbase64);

                        // Gzuncompress the query
                        $query_rw = gzuncompress($sqlz);

                        // Sending the request...
                        $resultrw = @pg_query($resource_rw, $query_rw);

                        if(!$resultrw) {
                            echo "<center>";
                            echo "Signature found.<br /> Now processing query with request number ". $_POST[sig].".<br /><br />";
                            echo "<font color=\"red\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</font><br />";
                            echo "</center>";

                            // Closing the rw connection.
                            include '../../inc/footer.php';
                            pg_close($resource_rw);
                            exit;
                        }
                        else {
                            echo "<center>";
                            echo "Signature found.<br /> Now processing INSERT model query with number ". $_POST[sig].".<br /><br />";
                            echo "<font color=\"green\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</font><br />";

                            // Delete the entry from the pending query table.
                            $delete_request = "delete from fgs_position_requests where spr_hash = '". $_POST["sig"] ."';";
                            $resultdel = @pg_query($resource_rw, $delete_request);

                            if(!resultdel) {
                                echo "<font color=\"red\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</font><br /></center>";

                                // Closing the rw connection.
                                include '../../inc/footer.php';
                                pg_close($resource_rw);
                                exit;
                            }
                            else {
                                echo "<font color=\"green\">Entry correctly deleted from the pending request table.</font></center>";

                                // Closing the rw connection.
                                pg_close($resource_rw);

                                // Sending mail if SQL was correctly inserted and entry deleted.
                                // Sets the time to UTC.
                                date_default_timezone_set('UTC');
                                $dtg = date('l jS \of F Y h:i:s A');

                                // OK, let's start with the mail redaction.
                                // Who will receive it ?
                                $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
                                if(isset($_POST['email'])) {
                                //$to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                                $to .= $_POST["email"];
                                }
                                else {
                                //$to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                                }

                                // What is the subject ?
                                $subject = "[FG Scenery Submission forms] Automatic 3D model insertion DB insertion confirmation.";

                                // Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
                                $message0 = "Hi,"  . "\r\n" .
                                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                                        "http://scenemodels.flightgear.org/submission/static/static_submission.php"  . "\r\n" .
                                        "I just wanted to let you know that the 3D model import named Blah." . "\r\n" .
                                        "has been successfully treated in the pending requests table." . "\r\n" .
                                        "with the following comment :\"".$_POST["maintainer_comment"]."\"."."\r\n" .
                                        "The corresponding pending entry has consequently been deleted" . "\r\n" .
                                        "from the pending requests table." . "\r\n" .
                                        "The corresponding entry will be added in Terrasync" . "\r\n" .
                                        "at 1230Z today or tomorrow if this time has already passed." . "\r\n" .
                                        "You can follow Terrasync's data update at the following url: " . "\r\n" .
                                        "http://code.google.com/p/terrascenery/source/list" . "\r\n" . "\r\n" .
                                        "You can also check the model direcly at http://scenemodels.flightgear.org/" ."\r\n" .
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
            }
        }
    }
    include '../../inc/footer.php';
}

if (!(isset($_POST["action"]))) {

// Inserting libs
require_once ('../../inc/functions.inc.php');
include_once '../../inc/geshi/geshi.php';

$page_title = "Automated Models Submission Form";
include '../../inc/header.php';

// Checking DB availability before all
$ok = check_availability();

if(!$ok) {
?>
    <p class="center"><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font>
    <br />The FlightGear team.</p>
    <?php include '../../inc/footer.php';
}
else {

    // Working on the object, first
    // Check the presence of "ob_sig", its length (64) and its content.
    if ((isset($_GET["ob_sig"])) && ((strlen($_GET["ob_sig"])) == 64) && preg_match("/[0-9a-z]/", $_GET["ob_sig"])) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if($resource_rw != '0') {

            // Checking the presence of sig into the database
            $result = @pg_query($resource_rw, "select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_GET["ob_sig"] ."';");
            if (pg_num_rows($result) != 1) {
                echo "<center>";
                echo "<font color=\"red\">Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?</font><br />\n";
                echo "Else, please report to fg-devel ML or FG Scenery forum.<br />";
                echo "</center>";
                include '../../inc/footer.php';
                @pg_close($resource_rw);
                exit;
            }
            else {
                    while ($row = pg_fetch_row($result)) {
                        $sqlzbase64 = $row[1];

                        // Base64 decode the query
                        $sqlz = base64_decode($sqlzbase64);

                        // Gzuncompress the query
                        $query_rw = gzuncompress($sqlz);

                        $trigged_query_rw = str_replace("INSERT INTO fgsoj_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_group)","",$query_rw); // Removing the start of the query from the data;
                        $tab_tags = explode(", (", $trigged_query_rw); // Separating the data based on the ST_PointFromText existence
                        foreach ($tab_tags as $value_tag) {
                                $trigged_0 = str_replace("ST_PointFromText('POINT(", "", $value_tag); // Removing ST_PointFromText...;
                                $trigged_1 = str_replace(")', 4326),","",$trigged_0);                 // Removing )", 4326), from data;
                                $trigged_2 = str_replace(", '1')","",$trigged_1);                        // Removing 1); from data;
                                $trigged_3 = str_replace(", 1)","",$trigged_2);                       // Removing " 1)," - family;
                                $trigged_4 = str_replace(" NULL","",$trigged_3);                      // Removing NULL from offset;
                                $trigged_5 = str_replace("VALUES (","",$trigged_4);                      // Removing VALUES(;
                                $trigged_6 = str_replace("'","",$trigged_5);                        // Finally, removing ' from data;
                                $trigged_7 = str_replace(",","",$trigged_6);                        // Finally, removing ' from data;
                                $data = explode(" ",$trigged_7);                                     // Now showing the results
                                $j = 0;
                                foreach ($data as $data_from_query) {
                                    if ($j == 1) $ob_long = $data_from_query;
                                    if ($j == 2) $ob_lat = $data_from_query;
                                    if ($j == 3) $ob_gndelev = $data_from_query;
                                    if ($j == 4) $ob_elevoffset = $data_from_query;
                                    if ($j == 5) $ob_heading = $data_from_query;
                                    if ($j == 6) ; // Not using model for now, it's not yet inserted
                                    $j++;
                                }
                        }
                    }
                }
        }
    }

    // Working on the model, now
    // Check the presence of "mo_sig", its length (64) and its content.
    if ((isset($_GET["mo_sig"])) && ((strlen($_GET["mo_sig"])) == 64) && preg_match("/[0-9a-z]/", $_GET["mo_sig"])) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if($resource_rw != '0') {

            // Checking the presence of sig into the database
            $result = @pg_query($resource_rw, "select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_GET["mo_sig"] ."';");
            if (pg_num_rows($result) != 1) {
                echo "<center>";
                echo "<font color=\"red\">Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?</font><br />\n";
                echo "Else, please report to fg-devel ML or FG Scenery forum.<br />";
                echo "</center>";
                include '../../inc/footer.php';
                @pg_close($resource_rw);
                exit;
            }
            else {
                    while ($row = pg_fetch_row($result)) {
                        $sqlzbase64 = $row[1];

                        // Base64 decode the query
                        $sqlz = base64_decode($sqlzbase64);

                        // Gzuncompress the query
                        $query_rw = gzuncompress($sqlz);

                        // INSERT INTO fgsoj_models (mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared)
                        // VALUES (DEFAULT, '$path', $author', '$name', '$comment', '$thumbFile', '$modelFile', '$mo_shared') RETURNING mo_id";
                        $trigged_query_rw = str_replace("INSERT INTO fgsoj_models (mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared) VALUES (DEFAULT, ","",$query_rw); // Removing the start of the query from the data;
                        $tab_tags = explode(", ", $trigged_query_rw); // Separating the data based on ', '
                        $j = 0;
                        foreach ($tab_tags as $value_tag) {
                            $j++;
                            if ($j == 1) {
                                $mo_path = str_replace(".xml", "", (str_replace("'", "", $value_tag)));
                            }
                                else if ($j == 2) {
                                    $mo_author = get_authors_name_from_authors_id(str_replace("'", "", $value_tag));
                                }
                                    else if ($j == 3) {
                                        $mo_name = str_replace("'", "", $value_tag);
                                    }
                                        else if ($j == 4) {
                                            $mo_notes = str_replace("'", "", $value_tag);
                                        }
                                            else if ($j == 5) {
                                                $mo_thumbfile = str_replace("'", "", $value_tag);
                                            }
                                                else if ($j == 6) {
                                                $mo_modelfile = str_replace("'", "", $value_tag);
                                                }
                                                    else if ($j == 7) {
                                                        $mo_shared = str_replace("'", "", $value_tag);
                                                        $mo_shared = str_replace(") RETURNING mo_id", "", $mo_shared);
                                                        $mo_shared = family_name($mo_shared);
                                                    }
                        }


                    }
                }
        }
    }

}
?>

<p class="center">Hi, this is the static submission form at http://scenemodels.flightgear.org/submission/static.</p>
<p class="center">The following model has passed all (numerous) verifications by the forementionned script. It should be fine to validate it. However, it's always sane to eye-check it.</p>

<form name="validation" method="post" action="static_submission.php">
<table>
    <tr>
        <th>Data</th>
        <th>Value</th>
    </tr>
    <tr>
        <td>Author</td>
        <td><?php echo $mo_author; ?></td>
    </tr>
    <tr>
        <td>Contributor</td>
        <td></td>
    </tr>
    <tr>
        <td>Email</td>
        <td><?php echo $_GET["email"]; ?></td>
        <input type="hidden" name="email" value="<?php echo $_GET["email"]; ?>">
    </tr>
    <tr>
        <td>Family</td>
        <td><?php echo $mo_shared; ?></td>
    </tr>
    <tr>
        <td>Proposed Path Name</td>
        <td><?php echo $mo_path; ?></td>
    </tr>
    <tr>
        <td>Full Name</td>
        <td><?php echo $mo_name; ?></td>
    </tr>
    <tr>
        <td>Notes</td>
        <td><?php echo $mo_notes; ?></td>
    </tr>
    <tr>
        <td>Latitude</td>
        <td><?php echo $ob_lat; ?></td>
    </tr>
    <tr>
        <td>Longitude</td>
        <td><?php echo $ob_long; ?></td>
    </tr>
    <tr>
        <td>Map</td>
        <td>
        <center>
        <iframe src="http://mapserver.flightgear.org/map/?lon=<?php echo $ob_long; ?>&lat=<?php echo $ob_lat; ?>&zoom=14&layers=000B0000TFFFTFFFTFTFFFFF" width="320" height="240" scrolling="auto" marginwidth="2" marginheight="2" frameborder="0">
        </iframe>
        </center>
        </td>
    </tr>
    <tr>
        <td>Country</td>
        <td></td>
    </tr>
    <tr>
        <td>Ground Elevation</td>
        <td><?php echo $ob_gndelev; ?></td>
    </tr>
    <tr>
        <td>Elevation offset</td>
        <td><?php echo $ob_elevoffset; ?></td>
    </tr>
    <tr>
        <td>True DB orientation</td>
        <td><?php echo $ob_heading; ?></td>
    </tr>
    <tr>
        <td>Corresponding Thumbnail</td>
        <td><center><img src="get_thumbnail_from_mo_sig.php?mo_sig=<?php echo $_GET["mo_sig"] ?>"></center></td>
    </tr>
<?php
// Now (hopefully) trying to manage the AC3D + XML + PNG texture files stuff

    // This should be done at the end of the script
    unlink('/tmp/submission/submitted_files.tar.gz');  // Deletes compressed file
    clearDir('/tmp/submission');                       // Deletes temporary submission directory

    //while (file_exists('/tmp/submission')) {
    //    usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
    //}

    if (!mkdir('/tmp/submission/')) {
        echo "Impossible to create '/tmp/submission/' directory!";
    }
    $targetPath = '/tmp/submission';

    if (file_exists($targetPath) && is_dir($targetPath)) {
        $archive = base64_decode($mo_modelfile);            // DeBase64 file
        $file = '/tmp/submission/submitted_files.tar.gz';   // Defines the destination file
        file_put_contents ($file, $archive);            // Writes the content of $mo_modelfile into submitted_files.tar.gz
    }

    system('tar xvzf /tmp/submission/submitted_files.tar.gz -C /tmp/submission');

    $dir = opendir("/tmp/submission");
    while ($file = readdir($dir)) {
        if (ShowFileExtension($file) == "ac") {
            $ac3d_file = $file; echo "AC3D file: $ac3d_file <br />\n";
        }
        if (ShowFileExtension($file) == "png") {
            $png_file = $file; echo "PNG file: $png_file <br />\n";
        }
        if (ShowFileExtension($file) == "xml") {
            $xml_file = $file; echo "XML file: $xml_file <br />\n";
        }
    }
    closedir($dir);
?>
    <tr>
        <td>Corresponding AC3D File</td>
        <td><?php echo $ac3d_file; ?>...</td>
    </tr>
    <tr>
        <td>Corresponding XML File</td>
        <td>
            <?php
            // Geshi stuff
            $file = '/tmp/submission/'.$xml_file;
            $source = file_get_contents($file);
            $language = 'xml';
            $geshi = new GeSHi($source, $language);
            $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
            $geshi->set_line_style('background: #fcfcfc;');
            echo $geshi->parse_code();
            ?>
        </td>
    </tr>
    <tr>
        <td>Corresponding PNG Texture Files<br />(click on the pictures to get them bigger)</td>
        <td>
            <center>
            <a href="" rel="lightbox[submission]" title="1st texture">#1<img src="get_texture_from_dir.php"></a>
            <a href="ATR42BR0.bmp" rel="lightbox[submission]" title="2nd texture">#2<img src="ATR42BR0.bmp"></a>
            <a href="ATR42BR2.bmp" rel="lightbox[submission]" title="3rd texture">#3<img src="ATR42BR2.bmp"></a>
            </center>
        </td>
    </tr>
    <tr>
        <td>Leave a comment to the submitter
        </td>
        <td><input type="text" name="maintainer_comment" size="100" value="Drop a comment to the submitter" /></td>
    </tr>
    <tr>
        <td>Action</td>
        <td><center>
        <input type="hidden" name="ob_sig" value="<?php echo $_GET["ob_sig"]; ?>" />
        <input type="hidden" name="mo_sig" value="<?php echo $_GET["mo_sig"]; ?>" />
        <input type="submit" name="action" value="Submit model" />
        <input type="submit" name="action" value="Reject model" />
        </center></td>
    </tr>
</form>
</table>
<?php
}
include '../../inc/footer.php';
?>
