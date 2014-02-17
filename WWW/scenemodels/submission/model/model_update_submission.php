<?php
if (isset($_POST["action"])) {
    // Inserting libs
    include_once '../../inc/functions.inc.php';
    include_once '../../inc/form_checks.php';
    include_once '../../inc/email.php';
    $page_title = "Automated Models Submission Form";

    // Prepare a generic mail
    // If $action=reject
        // - Drop fgs_position_requests;
        // - Send 2 mails

    if ($_POST["action"] == "Reject model") {

        if (isset($_POST["mo_sig"])) {
            $resource_rw = connect_sphere_rw();

            // If connection is OK
            if ($resource_rw != '0') {

                // Checking the presence of sig into the database
                $mo_result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_POST["mo_sig"] ."';");
                if (pg_num_rows($mo_result) != 1) {
                    $process_text = "Deleting corresponding pending query.";
                    $error_text   = "Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?";
                    $advise_text  = "Else, please report to fg-devel ML or FG Scenery forum.";
                    include '../inc/error_page.php';
                    @pg_close($resource_rw);
                    exit;
                }

                // Delete the entry from the pending query table.
                $mo_delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["mo_sig"] ."';";
                $mo_resultdel = @pg_query($resource_rw, $mo_delete_request);

                if (!$mo_resultdel) {
                    $process_text = "Deleting corresponding pending query.<br/>Signature found.<br /> Now deleting request with number ". $_POST["mo_sig"];
                    $error_text   = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                    include '../inc/error_page.php';

                    // Closing the rw connection.
                    pg_close($resource_rw);
                    exit;
                }

                include '../../inc/header.php';
                echo "<p class=\"center\">Deleting corresponding pending query.</p>";
                echo "<p class=\"center\">";
                echo "Signature found.<br />Now deleting request with number ". $_POST["mo_sig"]." with comment \"". $_POST["maintainer_comment"] ."\".</p>";
                echo "<p class=\"center ok\">Entries have correctly been deleted from the pending requests table.";
                echo "</p>";

                // Closing the rw connection.
                include '../../inc/footer.php';
                pg_close($resource_rw);

                // Sending mail if entry was correctly deleted.
                // Sets the time to UTC.

                date_default_timezone_set('UTC');
                $dtg = date('l jS \of F Y h:i:s A');
                $mo_sha_hash = $_POST["mo_sig"];
                $name = $_POST["mo_name"];
                $comment = $_POST["maintainer_comment"];

                if (isset($_POST['contrib_email']))
                    $to = $_POST["contrib_email"];
                else
                    $to = "";

                // Email to contributor
                email("model_update_request_rejected");

                exit;

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

            if (pg_num_rows($mo_result) != 1) {
                $error_text = "Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?";
                $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }

            while ($row_mo = pg_fetch_row ($mo_result)) {
                $sqlzbase64_mo = $row_mo[1];

                // Base64 decode the query
                $sqlz_mo = base64_decode ($sqlzbase64_mo);

                // Gzuncompress the query
                $query_rw_mo = gzuncompress ($sqlz_mo);

                // Sending the requests...
                $result_rw_mo = @pg_query ($resource_rw, $query_rw_mo);

                $pattern = "/UPDATE fgs_models SET mo_path \= '(?P<path>[a-zA-Z0-9_.-]+)', mo_author \= (?P<author>[0-9]+), mo_name \= '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', mo_notes \= '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', mo_thumbfile \= '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', mo_modelfile \= '(?P<modelfile>[a-zA-Z0-9=+\/]+)', mo_shared \= (?P<shared>[0-9]+) WHERE mo_id \= (?P<modelid>[0-9]+)/";
                $error === preg_match($pattern, $query_rw_mo, $matches);

                $mo_id = $matches['modelid'];

                if (!$result_rw_mo) {
                    $process_text = "Signatures found.<br /> Now processing query with request number ". $_POST["mo_sig"];
                    $error_text = "Sorry, but the UPDATE queries could not be processed.";
                    $advise_text = "Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                    include '../inc/error_page.php';

                    // Closing the rw connection.
                    pg_close ($resource_rw);
                    exit;
                }

                include '../../inc/header.php';
                echo "<p class=\"center\">";
                echo "Signatures found.<br /> Now processing UPDATE query of model with number ". $_POST["mo_sig"].".</p>";
                echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

                // Delete the entries from the pending query table.
                $delete_request_mo = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["mo_sig"] ."';";
                $resultdel_mo = @pg_query ($resource_rw, $delete_request_mo);

                if (!$resultdel_mo) {
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
                $mo_sha_hash = $_POST["mo_sig"];
                $name = $_POST["mo_name"];
                $comment = $_POST["maintainer_comment"];
                $model_id = $mo_id;

                // OK, let's start with the mail redaction.
                // Who will receive it ?
                if (isset($_POST["contrib_email"]))
                    $to = $_POST["contrib_email"];
                else
                    $to = "";

                // Email to contributor
                email("model_update_request_accepted");

                include '../../inc/footer.php';
                exit;
            }
        }
    }
    include '../../inc/footer.php';
}

if (!isset($_POST["action"])) {

    // Inserting libs
    include_once '../../inc/functions.inc.php';
    include_once '../../inc/form_checks.php';
    include_once '../../inc/geshi/geshi.php';


    // Checking DB availability before all
    $ok = check_availability();

    if (!$ok) {
        $page_title = "Automated Models Submission Form";
        $error_text = "Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Models Submission Form";

    // Working on the model, now
    // Check the presence of "mo_sig", its length (64) and its content.
    if (isset($_GET["mo_sig"]) && (strlen($_GET["mo_sig"]) == 64) && preg_match($regex['sig'], $_GET["mo_sig"])) {
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
            $pattern = "/UPDATE fgs_models SET mo_path \= '(?P<path>[a-zA-Z0-9_.-]+)', mo_author \= (?P<author>[0-9]+), mo_name \= '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', mo_notes \= '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', mo_thumbfile \= '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', mo_modelfile \= '(?P<modelfile>[a-zA-Z0-9=+\/]+)', mo_shared \= (?P<shared>[0-9]+) WHERE mo_id \= (?P<modelid>[0-9]+)/";
            $error === preg_match($pattern, $query_rw, $matches);

            $mo_id        = $matches['modelid'];
            $mo_path      = $matches['path'];
            $mo_author    = get_authors_name_from_authors_id($matches['author']);
            $mo_author_email = get_authors_email_from_authors_id($matches['author']);
            $mo_name      = $matches['name'];
            $mo_notes     = $matches['notes'];
            $mo_thumbfile = $matches['thumbfile'];
            $mo_modelfile = $matches['modelfile'];
            $mo_shared    = $matches['shared'];
            
            // Retrieve old model
            $result = pg_query("SELECT mo_author, mo_id, mo_modified, mo_name, mo_notes, mo_path, mo_shared, to_char(mo_modified,'YYYY-mm-dd (HH24:MI)') AS mo_datedisplay FROM fgs_models WHERE mo_id=$mo_id;");
            $old_model = pg_fetch_assoc($result);
            
            $mo_contri_email = htmlentities($_GET["email"]);
            $old_mo_author_email = get_authors_email_from_authors_id($old_model['mo_author']);
        }
    }

include '../../inc/header.php';

?>

<p class="center">Hi, this is the update model submission form at http://<?php echo $_SERVER['SERVER_NAME'];?>/submission/model/index_model_update.php.</p>
<p class="center">The following model has passed all (numerous) verifications by the forementionned script. It should be fine to validate it. However, it's always sane to eye-check it.</p>

<form id="validation" method="post" action="model_update_submission.php" onsubmit="return validateForm();">
<table>
    <tr>
        <th>Data</th>
        <th>Old Value</th>
        <th>New Value</th>
    </tr>
    <tr>
        <td>Author (Email)</td>
        <td>
            <?php
            $result = pg_query("SELECT au_name FROM fgs_authors WHERE au_id = '$old_model[mo_author]';");
            $row = pg_fetch_assoc($result);
            echo $row["au_name"] . "(".$old_mo_author_email.")";
            ?>
        </td>
        <td>
            <?php echo $mo_author."(".$mo_author_email.")"; ?>
            <input type="hidden" name="email" value="<?php echo $mo_author_email; ?>" />
        </td>
    </tr>
    <tr>
        <td>Contributor's email</td>
        <td></td>
        <td>
            <?php echo $mo_contri_email; ?>
            <input type="hidden" name="contrib_email" value="<?php echo $mo_contri_email; ?>" />    
        </td>
    </tr>
    <tr>
        <td>Family</td>
        <td>
        <?php
            $result = pg_query("SELECT mg_id, mg_name FROM fgs_modelgroups WHERE mg_id = '$old_model[mo_shared]';");
            $row = pg_fetch_assoc($result);
            print $row["mg_name"];
        ?>
        </td>
        <td><?php echo family_name($mo_shared); ?></td>
    </tr>
    <tr>
        <td>Proposed Path Name</td>
        <td><?php echo $old_model["mo_path"]; ?></td>
        <td><?php echo $mo_path; ?></td>
    </tr>
    <tr>
        <td>Full Name</td>
        <td><?php echo $old_model["mo_name"]; ?></td>
        <td><?php echo $mo_name; ?></td>
        <input type="hidden" name="mo_name" value="<?php echo $mo_name; ?>" />
    </tr>
    <tr>
        <td>Notes</td>
        <td><?php echo $old_model["mo_notes"]; ?></td>
        <td><?php echo $mo_notes; ?></td>
    </tr>
    <tr>
        <td>Corresponding Thumbnail</td>
        <td><img src="../../modelthumb.php?id=<?php echo $mo_id ?>" alt="Thumbnail"/></td>
        <td><img src="get_thumbnail_from_mo_sig_update.php?mo_sig=<?php echo $_GET["mo_sig"] ?>" alt="Thumbnail"/></td>
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
        <td colspan="2"><center><a href="get_targz_from_mo_sig.php?mo_sig=<?php echo $_GET['mo_sig']; ?>">Download the NEW MODEL as .tar.gz for external viewing.</a></center></td>
    </tr>
    <tr>
        <td>Corresponding AC3D File</td>
        <td colspan="2">
            <h3>Original model:</h3>
            <object data="../../viewer.php?id=<?php echo $mo_id; ?>" type="text/html" width="720px" height="620px"></object>
            <br/>
<?php
            $based64_target_path = base64_encode($target_path);
            $encoded_target_path = rawurlencode($based64_target_path);
?>
            <h3>New model:</h3>
            <object data="model/index_update.php?mo_sig=<?php echo $_GET['mo_sig']; ?>" type="text/html" width="720px" height="620px"></object>
        </td>
    </tr>
    <tr>
        <td>Corresponding XML File</td>
        <td colspan="2">
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
        <td colspan="2">
            <center>
<?php
            if ($png_file_number <= 1)
                echo $png_file_number." texture file has been submitted:<br/>\n"; // Some eye caviar for the poor scenery maintainers.
            else
                echo $png_file_number." texture files have been submitted:<br/>\n";

            // Sending the directory as parameter. This is no user input, so low risk. Needs to be urlencoded.
            $based64_target_path = base64_encode($target_path);
            $encoded_target_path = rawurlencode($based64_target_path);
            for ($j=0; $j<$png_file_number; $j++) {
                $texture_file = "http://".$_SERVER['SERVER_NAME'] ."/submission/model/model/get_texture_by_filename_update.php?mo_sig=".$_GET["mo_sig"]."&name=".$png_file_name[$j];
                $texture_file_tn = "http://".$_SERVER['SERVER_NAME'] ."/submission/model/model/get_texture_tn_by_filename_update.php?mo_sig=".$_GET["mo_sig"]."&name=".$png_file_name[$j];

                $tmp = getimagesize($texture_file);
                $width  = $tmp[0];
                $height = $tmp[1];
?>
                <a href="<?php echo $texture_file; ?>" rel="lightbox[submission]" />
                <?php //imagethumb($texture_file) ?>
                <img src="<?php echo $texture_file_tn; ?>" alt="Texture #<?php echo $j; ?>" />
<?php
                echo $png_file_name[$j]." (Original size: ".$width."x".$height.")</a><br/>";
            }
?>
            </center>
        </td>
    </tr>
    <tr>
        <td>Leave a comment to the submitter</td>
        <td colspan="2"><input type="text" name="maintainer_comment" size="85" placeholder="Drop a comment to the submitter" /></td>
    </tr>
    <tr>
        <td>Action</td>
        <td colspan="2" class="submit">
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
require '../../inc/footer.php';
?>
