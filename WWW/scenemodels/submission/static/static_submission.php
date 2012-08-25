<?php
if (isset($_POST["action"])) {
    // Inserting libs
    require_once ('../../inc/functions.inc.php');
    $page_title = "Automated Models Submission Form";

    // Prepare a generic mail
    // If $action=reject
        // - Drop fgs_position_requests;
        // - Send 2 mails

    if ($_POST["action"] == "Reject model") {

        if (isset($_POST["ob_sig"]) && isset($_POST["mo_sig"])) {
            $resource_rw = connect_sphere_rw();

            // If connection is OK
            if ($resource_rw != '0') {

                // Checking the presence of sig into the database
                $ob_result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_POST["ob_sig"] ."';");
                $mo_result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_POST["mo_sig"] ."';");
                if ((pg_num_rows($ob_result) != 1) || (pg_num_rows($mo_result) != 1)) {
                    $process_text = "Deleting corresponding pending query.";
                    $error_text   = "Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?";
                    $advise_text  = "Else, please report to fg-devel ML or FG Scenery forum.";
                    include '../../inc/error_page.php';
                    @pg_close($resource_rw);
                    exit;
                }

                // Delete the entry from the pending query table.
                $ob_delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["ob_sig"] ."';";
                $mo_delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["mo_sig"] ."';";
                $ob_resultdel = @pg_query($resource_rw, $ob_delete_request);
                $mo_resultdel = @pg_query($resource_rw, $mo_delete_request);

                if ((!$ob_resultdel) || (!$mo_resultdel)) {
                    $process_text = "Deleting corresponding pending query.<br/>Signature found.<br /> Now deleting requests with numbers ". $_POST["ob_sig"]." and ". $_POST["mo_sig"];
                    $error_text   = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                    include '../../inc/error_page.php';

                    // Closing the rw connection.
                    pg_close($resource_rw);
                    exit;
                }

                include '../../inc/header.php';
                echo "<p class=\"center\">Deleting corresponding pending query.</p>";
                echo "<p class=\"center\">";
                echo "Signature found.<br />Now deleting requests with number ". $_POST["ob_sig"]." and ". $_POST["mo_sig"]." with comment \"". $_POST["maintainer_comment"] ."\".</p>";
                echo "<p class=\"center ok\">Entries have correctly been deleted from the pending requests table.";
                echo "</p>";

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
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                    $to .= "\"Julien NGUYEN\" <jnguyen@etu.emse.fr>, ";
                    $to .= $_POST["email"];
                }
                else {
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                    $to .= "\"Julien NGUYEN\" <jnguyen@etu.emse.fr>";
                }

                // What is the subject ?
                $subject = "[FG Scenery Submission forms] Automatic 3D model insertion DB reject and deletion confirmation.";

                // Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
                $message0 = "Hi,"  . "\r\n" .
                            "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                            "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']  . "\r\n" .
                            "I just wanted to let you know that your 3D model import"."\r\n" .
                            "- ".substr($_POST["mo_sig"],0,10). "... (model) and " . "\r\n" .
                            "- ".substr($_POST["ob_sig"],0,10). "... (object)" . "\r\n" .
                            "and named '".$_POST["mo_name"]."' " . "\r\n" .
                            "has been rejected and therefore successfully deleted from the pending requests table"."\r\n" .
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

                /*echo "The user submission has been rejected with the following warning: ".$_POST["maintainer_comment"].". User has been informed by mail.";
                exit;*/
            }
        }
    }

    // If $action=accept
        // - Execute both requests
        // - Send 2 mails

    if ($_POST["action"] == "Submit model") {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != '0') {

            // Checking the presence of sigs into the database
            $mo_result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_POST["mo_sig"] ."';");
            $ob_result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_POST["ob_sig"] ."';");

            if ((pg_num_rows($ob_result) != 1) || (pg_num_rows($mo_result) != 1)) {
                $error_text = "Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?";
                $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }

            while (($row_mo = pg_fetch_row ($mo_result)) && ($row_ob = pg_fetch_row ($ob_result))) {
                $sqlzbase64_mo = $row_mo[1];
                $sqlzbase64_ob = $row_ob[1];

                // Base64 decode the query
                $sqlz_mo = base64_decode ($sqlzbase64_mo);
                $sqlz_ob = base64_decode ($sqlzbase64_ob);

                // Gzuncompress the query
                $query_rw_mo = gzuncompress ($sqlz_mo);
                $query_rw_ob = gzuncompress ($sqlz_ob);

                // Sending the requests...
                $result_rw_mo = @pg_query ($resource_rw, $query_rw_mo);
                $mo_id = pg_fetch_row ($result_rw_mo);
                $query_rw_ob_with_mo_id = str_replace("Thisisthevalueformo_id", $mo_id[0], $query_rw_ob); // Adding mo_id in the object request... sorry didn't find a shorter way.
                $query_rw_ob_with_mo_id = $query_rw_ob_with_mo_id." RETURNING ob_id;";

                $result_rw_ob = @pg_query ($resource_rw, $query_rw_ob_with_mo_id);
                $ret_ob_id = pg_fetch_row ($result_rw_ob);
                $query_ob_text = "update fgs_objects set ob_text = $$". object_name($mo_id[0]) ."$$ where ob_id = '".$ret_ob_id[0]."';"; // Adding ob_text;
                $result_obtext_update = @pg_query ($resource_rw, $query_ob_text);

                if((!$result_rw_mo) || (!$result_rw_ob)) {
                    $process_text = "Signatures found.<br /> Now processing queries with request numbers ". $_POST["ob_sig"]." and ". $_POST["mo_sig"];
                    $error_text = "Sorry, but the INSERT queries could not be processed.";
                    $advise_text = "Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                    include '../../inc/error_page.php';

                    // Closing the rw connection.
                    pg_close ($resource_rw);
                    exit;
                }

                include '../../inc/header.php';
                echo "<p class=\"center\">";
                echo "Signatures found.<br /> Now processing INSERT queries of model and object with numbers ". $_POST["ob_sig"]." and ". $_POST["mo_sig"].".</p>";
                echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

                // Delete the entries from the pending query table.
                $delete_request_mo = "delete from fgs_position_requests where spr_hash = '". $_POST["mo_sig"] ."';";
                $delete_request_ob = "delete from fgs_position_requests where spr_hash = '". $_POST["ob_sig"] ."';";
                $resultdel_mo = @pg_query ($resource_rw, $delete_request_mo);
                $resultdel_ob = @pg_query ($resource_rw, $delete_request_ob);

                if((!$resultdel_mo) || (!$resultdel_ob)) {
                    echo "<p class=\"center warning\">Sorry, but the pending requests DELETE queries could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p>";

                    // Closing the rw connection.
                    include '../../inc/footer.php';
                    pg_close($resource_rw);
                    exit;
                }

                echo "<p class=\"center ok\">Pending entries correctly deleted from the pending request table.</p>";

                // Closing the rw connection.
                pg_close($resource_rw);

                // Sending mail if SQL was correctly inserted and entry deleted.
                // Sets the time to UTC.
                date_default_timezone_set('UTC');
                $dtg = date('l jS \of F Y h:i:s A');

                // OK, let's start with the mail redaction.
                // Who will receive it ?
                $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
                if (isset($_POST["email"])) {
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                    $to .= "\"Julien NGUYEN\" <jnguyen@etu.emse.fr>, ";
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
                        "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'] . "\r\n" .
                        "I just wanted to let you know that the 3D model import with numbers :" . "\r\n" .
                        "- ".substr($_POST["mo_sig"],0,10). "... (model) and " . "\r\n" .
                        "- ".substr($_POST["ob_sig"],0,10). "... (object)" . "\r\n" .
                        "and named '".$_POST["mo_name"]."' " . "\r\n" .
                        "has been successfully treated in the scenemodel database" . "\r\n" .
                        "with the following comment :\"".$_POST["maintainer_comment"]."\"."."\r\n" .
                        "The corresponding pending entries has consequently been deleted" . "\r\n" .
                        "from the pending requests table." . "\r\n" .
                        "The corresponding entries will be added in Terrasync" . "\r\n" .
                        "at 1230Z today or tomorrow if this time has already passed." . "\r\n" .
                        "You can follow Terrasync's data update at the following url: " . "\r\n" .
                        "http://code.google.com/p/terrascenery/source/list" . "\r\n" . "\r\n" .
                        "You can also check the model directly at http://".$_SERVER['SERVER_NAME']."/modeledit.php?id=".$mo_id[0].""."\r\n" .
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
    include '../../inc/footer.php';
}

if (!isset($_POST["action"])) {

    // Inserting libs
    require_once ('../../inc/functions.inc.php');
    include_once '../../inc/geshi/geshi.php';


    // Checking DB availability before all
    $ok = check_availability();

    if(!$ok) {
        $page_title = "Automated Models Submission Form";
        $error_text = "Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Models Submission Form";

    // Working on the object, first
    // Check the presence of "ob_sig", its length (64) and its content.
    if (isset($_GET["ob_sig"]) && strlen($_GET["ob_sig"]) == 64 && preg_match("/[0-9a-z]/", $_GET["ob_sig"])) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if($resource_rw != '0') {

            // Checking the presence of ob_sig into the database
            $result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_GET["ob_sig"] ."';");
            if (pg_num_rows($result) != 1) {
                $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
                $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }

            while ($row = pg_fetch_row($result)) {
                $sqlzbase64 = $row[1];

                // Base64 decode query
                $sqlz = base64_decode($sqlzbase64);

                // Gzuncompress query
                $query_rw = gzuncompress($sqlz);

                // Retrieve data from query
                $search = 'ob_elevoffset'; // We're searching for ob_elevoffset presence in the request to correctly preg it.
                $pos = strpos($query_rw, $search);

                if ($pos === false) { // No offset is present
                    $pattern  = "/INSERT INTO fgs_objects \(wkb_geometry, ob_gndelev, ob_heading, ob_country, ob_model, ob_group\) VALUES \(ST_PointFromText\('POINT\((?P<longitude>[0-9.-]+) (?P<latitude>[0-9.-]+)\)', 4326\), (?P<gndelev>[0-9.-]+), (?P<heading>[0-9.-]+), '(?P<country>[a-z-A-Z-]+)', (?P<model>[a-z-A-Z_0-9-]+), 1\)/";
                    preg_match($pattern, $query_rw, $matches);
                    $ob_long       = $matches['longitude'];
                    $ob_lat        = $matches['latitude'];
                    $ob_gndelev    = $matches['gndelev'];
                    $ob_heading    = $matches['heading'];
                    $ob_country    = $matches['country'];
                }
                else { // ob_elevoffset has been found
                    $pattern  = "/INSERT INTO fgs_objects \(wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_country, ob_model, ob_group\) VALUES \(ST_PointFromText\('POINT\((?P<longitude>[0-9.-]+) (?P<latitude>[0-9.-]+)\)', 4326\), (?P<gndelev>[0-9.-]+), (?P<offset>[NULL0-9.-]+), (?P<heading>[0-9.-]+), '(?P<country>[a-z-A-Z-]+)', (?P<model>[a-z-A-Z_0-9-]+), 1\)/";
                    preg_match($pattern, $query_rw, $matches);
                    $ob_long       = $matches['longitude'];
                    $ob_lat        = $matches['latitude'];
                    $ob_gndelev    = $matches['gndelev'];
                    $ob_elevoffset = $matches['offset'];
                    $ob_heading    = $matches['heading'];
                    $ob_country    = $matches['country'];
                }
            }
        }
    }

    // Working on the model, now
    // Check the presence of "mo_sig", its length (64) and its content.
    if (isset($_GET["mo_sig"]) && (strlen($_GET["mo_sig"]) == 64) && preg_match("/[0-9a-z]/", $_GET["mo_sig"])) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != '0') {

            // Checking the presence of sig into the database
            $result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_GET["mo_sig"] ."';");
            if (pg_num_rows($result) != 1) {
                $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
                $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }

            // We are sure there is only 1 row
            $row = pg_fetch_row($result);

            $sqlzbase64 = $row[1];

            // Base64 decode the query
            $sqlz = base64_decode($sqlzbase64);

            // Gzuncompress the query
            $query_rw = gzuncompress($sqlz);

            // Retrieve data from query
            $pattern = "/INSERT INTO fgs_models \(mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared\) VALUES \(DEFAULT, '(?P<path>[a-zA-Z0-9_.-]+)', (?P<author>[0-9]+), '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', '(?P<modelfile>[a-zA-Z0-9=+\/]+)', (?P<shared>[0-9]+)\) RETURNING mo_id/";
            $error === preg_match($pattern, $query_rw, $matches);

            $mo_path      = $matches['path'];
            $mo_author    = get_authors_name_from_authors_id($matches['author']);
            $mo_name      = $matches['name'];
            $mo_notes     = $matches['notes'];
            $mo_thumbfile = $matches['thumbfile'];
            $mo_modelfile = $matches['modelfile'];
            $mo_shared    = $matches['shared'];
        }
    }

include '../../inc/header.php';

?>
<script type="text/javascript" src="/inc/js/check_form.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("validation");

    if (!checkComment(form["maintainer_comment"]))
        return false;

}
/*]]>*/
</script>

<p class="center">Hi, this is the static submission form at http://<?php echo $_SERVER['SERVER_NAME'];?>/submission/static.</p>
<p class="center">The following model has passed all (numerous) verifications by the forementionned script. It should be fine to validate it. However, it's always sane to eye-check it.</p>

<form id="validation" method="post" action="static_submission.php" onsubmit="return validateForm();">
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
        <td>Email</td>
        <td><?php echo $_GET["email"]; ?></td>
        <input type="hidden" name="email" value="<?php echo htmlentities($_GET["email"]); ?>" />
    </tr>
    <tr>
        <td>Family</td>
        <td><?php echo family_name($mo_shared); ?></td>
    </tr>
    <tr>
        <td>Proposed Path Name</td>
        <td><?php echo $mo_path; ?></td>
    </tr>
    <tr>
        <td>Full Name</td>
        <td><?php echo $mo_name; ?></td>
        <input type="hidden" name="mo_name" value="<?php echo $mo_name; ?>" />
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
        <object data="http://mapserver.flightgear.org/submap/?lon=<?php echo $ob_long; ?>&amp;lat=<?php echo $ob_lat; ?>&amp;zoom=14" type="text/html" width="320" height="240"></object>
        </center>
        </td>
    </tr>
    <tr>
        <td>Country</td>
        <td><?php echo get_country_name_from_country_code($ob_country); ?></td>
    </tr>
    <tr>
        <td>Ground Elevation</td>
        <td><?php echo $ob_gndelev; ?></td>
    </tr>
<?php
    if(isset($ob_elevoffset)) {
?>
    <tr>
        <td>Elevation offset</td>
        <td><?php echo $ob_elevoffset; ?></td>
    </tr>
<?php
    }
?>
    <tr>
        <td>True DB orientation</td>
        <td><?php echo $ob_heading; ?></td>
    </tr>
    <tr>
        <td>Corresponding Thumbnail</td>
        <td><center><img src="get_thumbnail_from_mo_sig.php?mo_sig=<?php echo $_GET["mo_sig"] ?>" alt="Thumbnail"/></center></td>
    </tr>
<?php
    // Now (hopefully) trying to manage the AC3D + XML + PNG texture files stuff

    // Managing possible concurrent accesses on the maintainer side.
    $target_path = sys_get_temp_dir() .'/submission_'.random_suffix();

    while (file_exists($target_path)) {
        usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
    }

    if (!mkdir($target_path)) {
        echo "Impossible to create ".$target_path." directory!";
    }

    if (file_exists($target_path) && is_dir($target_path)) {
        $archive = base64_decode ($mo_modelfile);           // DeBase64 file
        $file = $target_path.'/submitted_files.tar.gz';     // Defines the destination file
        file_put_contents ($file, $archive);                // Writes the content of $mo_modelfile into submitted_files.tar.gz
    }

    $detar_command = 'tar xvzf '.$target_path.'/submitted_files.tar.gz -C '.$target_path . '> /dev/null';
    system($detar_command);

    $dir = opendir($target_path);
    $png_file_number = 0;   // Counter for PNG files.
    while ($file = readdir($dir)) {
        if (show_file_extension($file) == "ac") {
            $ac3d_file = $file;
        }
        if (show_file_extension($file) == "png") {
            $png_file_name[$png_file_number] = $file;
            $png_file_number++;
        }
        if (show_file_extension($file) == "xml") {
            $xml_file = $file;
        }
    }
    closedir($dir);
?>
    <tr>
        <td>Download</td>
        <td><center><a href="get_targz_from_mo_sig.php?mo_sig=<?php echo $_GET["mo_sig"]; ?>">Download the submission as .tar.gz for external viewing.</a></center></td>
    </tr>
    <tr>
        <td>Corresponding AC3D File</td>
        <td>
            <center>
<?php
            $based64_target_path = base64_encode($target_path);
            $encoded_target_path = rawurlencode($based64_target_path);
?>
            <object data="model/index.php?mo_sig=<?php echo $_GET["mo_sig"]; ?>" type="text/html" width="720px" height="620px"></object>
            </center>
        </td>
    </tr>
    <tr>
        <td>Corresponding XML File</td>
        <td>
<?php
            // Geshi stuff
            $file = $target_path.'/'.$xml_file;
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
<?php
            if ($png_file_number <= 1)
                echo $png_file_number." texture file has been submitted:<br/>\n"; // Some eye caviar for the poor scenery maintainers.
            else echo $png_file_number." texture files have been submitted:<br/>\n";

            // Sending the directory as parameter. This is no user input, so low risk. Needs to be urlencoded.
            $based64_target_path = base64_encode($target_path);
            $encoded_target_path = rawurlencode($based64_target_path);
            for ($j=0; $j<$png_file_number; $j++) {
                $texture_file = "http://scenemodels.flightgear.org/submission/static/model/get_texture_by_filename.php?mo_sig=".$_GET["mo_sig"]."&name=".$png_file_name[$j];
                //$texture_file = "get_texture_from_dir.php?mo_sig=".$encoded_target_path."&amp;png_file_number=".$j;
                $tmp = getimagesize($texture_file); // (returning false right now)
                $width  = $tmp[0];
                $height = $tmp[1];
?>
                <a href="<?php echo $texture_file; ?>" rel="lightbox[submission]" />
                <?php //imagethumb($texture_file) ?>
                <img src="<?php echo $texture_file; ?>" alt="Texture #<?php echo $j; ?>" />
<?php
                echo $png_file_name[$j]." (Original dimensions: ".$width."x".$height.")</a><br/>";
            }
?>
            </center>
        </td>
    </tr>
    <tr>
        <td>Leave a comment to the submitter</td>
        <td><input type="text" name="maintainer_comment" size="100" value="Drop a comment to the submitter" onchange="checkComment(this)"/></td>
    </tr>
    <tr>
        <td>Action</td>
        <td class="submit">
            <input type="hidden" name="ob_sig" value="<?php echo $_GET["ob_sig"]; ?>" />
            <input type="hidden" name="mo_sig" value="<?php echo $_GET["mo_sig"]; ?>" />
            <input type="submit" name="action" value="Submit model" />
            <input type="submit" name="action" value="Reject model" />
        </td>
    </tr>
</table>
</form>
<p class="center">This tool uses part of the following software: gl-matrix, by Brandon Jones, and Hangar, by Juan Mellado.</p>
<?php
// The deletion of the tmp directory
unlink($target_path.'/submitted_files.tar.gz');  // Deletes compressed file
clear_dir($target_path);
}
include '../../inc/footer.php';
?>
