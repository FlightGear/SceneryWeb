<?php
require_once '../../inc/form_checks.php';
require_once "../../classes/DAOFactory.php";
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();

if (isset($_REQUEST["mo_sig"]) && is_sig($_REQUEST["mo_sig"])) {
    $sig = $_REQUEST["mo_sig"];
} else {
    exit;
}

if (isset($_POST["action"])) {
    // Inserting libs
    include_once '../../inc/functions.inc.php';
    include_once '../../classes/EmailContentFactory.php';
    $page_title = "Automated Models Submission Form";

    // Prepare a generic mail
    // If $action=reject
        // - Drop fgs_position_requests;
        // - Send 2 mails

    if ($_POST["action"] == "Reject model") {
        $requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();
        
        try {
            $resultDel = $requestDaoRW->deleteRequest($sig);
        } catch(RequestNotFoundException $e) {
            $process_text = "Deleting corresponding pending query.";
            $error_text   = "Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?";
            $advise_text  = "Else, please report to fg-devel ML or FG Scenery forum.";
            include '../inc/error_page.php';
            exit;
        }

        if (!$resultDel) {
            $process_text = "Deleting corresponding pending query.<br/>Signature found.<br /> Now deleting request with number ". $sig;
            $error_text   = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
            include '../inc/error_page.php';
            exit;
        }

        include '../../inc/header.php';
        echo "<p class=\"center\">Deleting corresponding pending query.</p>";
        echo "<p class=\"center\">";
        echo "Signature found.<br />Now deleting request with number ". $sig." with comment \"". $_POST["maintainer_comment"] ."\".</p>";
        echo "<p class=\"center ok\">Entries have correctly been deleted from the pending requests table.";
        echo "</p>";

        include '../../inc/footer.php';


        // Sending mail if entry was correctly deleted.
        // Sets the time to UTC.

        date_default_timezone_set('UTC');
        $dtg = date('l jS \of F Y h:i:s A');
        $name = $_POST["mo_name"];
        $comment = $_POST["maintainer_comment"];

        $to = (isset($_POST['contrib_email']))?$_POST["contrib_email"]:"";

        // Email to contributor
        $emailSubmit = EmailContentFactory::getModelUpdateRequestRejectedEmailContent($dtg, $sig, $name, $comment);
        $emailSubmit->sendEmail($to, true);

        exit;
    }

    // If $action=accept
        // - Execute both requests
        // - Send 2 mails

    if ($_POST["action"] == "Submit model") {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != '0') {

            // Checking the presence of sigs into the database
            $mo_result = pg_query($resource_rw, "SELECT spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $sig ."';");

            if (pg_num_rows($mo_result) != 1) {
                $error_text = "Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?";
                $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../inc/error_page.php';
                pg_close($resource_rw);
                exit;
            }

            while ($row_mo = pg_fetch_row ($mo_result)) {
                $sqlzbase64_mo = $row_mo[0];

                // Base64 decode the query
                $sqlz_mo = base64_decode ($sqlzbase64_mo);

                // Gzuncompress the query
                $query_rw_mo = gzuncompress ($sqlz_mo);

                // Sending the requests...
                $result_rw_mo = pg_query ($resource_rw, $query_rw_mo);

                $pattern = "/UPDATE fgs_models SET mo_path \= '(?P<path>[a-zA-Z0-9_.-]+)', mo_author \= (?P<author>[0-9]+), mo_name \= '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', mo_notes \= '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', mo_thumbfile \= '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', mo_modelfile \= '(?P<modelfile>[a-zA-Z0-9=+\/]+)', mo_shared \= (?P<shared>[0-9]+) WHERE mo_id \= (?P<modelid>[0-9]+)/";
                preg_match($pattern, $query_rw_mo, $matches);

                $mo_id = $matches['modelid'];

                if (!$result_rw_mo) {
                    $process_text = "Signatures found.<br /> Now processing query with request number ". $sig;
                    $error_text = "Sorry, but the UPDATE queries could not be processed.";
                    $advise_text = "Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                    include '../inc/error_page.php';

                    // Closing the rw connection.
                    pg_close ($resource_rw);
                    exit;
                }

                include '../../inc/header.php';
                echo "<p class=\"center\">";
                echo "Signatures found.<br /> Now processing UPDATE query of model with number ". $sig.".</p>";
                echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

                // Delete the entries from the pending query table.
                $delete_request_mo = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $sig ."';";
                $resultdel_mo = pg_query ($resource_rw, $delete_request_mo);

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
                $name = $_POST["mo_name"];
                $comment = $_POST["maintainer_comment"];
                $model_id = $mo_id;

                // OK, let's start with the mail redaction.
                // Who will receive it ?
                $to = (isset($_POST["contrib_email"]))?$_POST["contrib_email"]:"";

                // Email to contributor
                $emailSubmit = EmailContentFactory::getModelUpdateRequestAcceptedEmailContent($dtg, $sig, $name, $comment, $model_id);
                $emailSubmit->sendEmail($to, true);

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


    $page_title = "Automated Models Submission Form";

    // Working on the model, now
    try {
        $requestModelUpd = $requestDaoRO->getRequest($sig);
        $newModel = $requestModelUpd->getNewModel();
        $newModelMD = $newModel->getMetadata();
        $oldModel = $requestModelUpd->getOldModel();
        $oldModelMD = $oldModel->getMetadata();
    } catch(RequestNotFoundException $e) {
        $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
        $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
        include '../../inc/error_page.php';
        exit;
    }

    $mo_contri_email = htmlentities($_GET["email"]);

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
            echo $oldModelMD->getAuthor()->getName() . "(".$oldModelMD->getAuthor()->getEmail().")";
            ?>
        </td>
        <td>
            <?php echo $newModelMD->getAuthor()->getName()."(".$newModelMD->getAuthor()->getEmail().")"; ?>
            <input type="hidden" name="email" value="<?php echo $newModelMD->getAuthor()->getEmail(); ?>" />
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
        <td><?php echo $oldModelMD->getModelsGroup()->getName(); ?></td>
        <td><?php echo $newModelMD->getModelsGroup()->getName(); ?></td>
    </tr>
    <tr>
        <td>Proposed Path Name</td>
        <td><?php echo $oldModelMD->getFilename(); ?></td>
        <td><?php echo $newModelMD->getFilename(); ?></td>
    </tr>
    <tr>
        <td>Full Name</td>
        <td><?php echo $oldModelMD->getName(); ?></td>
        <td><?php echo $newModelMD->getName(); ?></td>
        <input type="hidden" name="mo_name" value="<?php echo $mo_name; ?>" />
    </tr>
    <tr>
        <td>Notes</td>
        <td><?php echo $oldModelMD->getDescription(); ?></td>
        <td><?php echo $newModelMD->getDescription(); ?></td>
    </tr>
    <tr>
        <td>Corresponding Thumbnail</td>
        <td><img src="../../modelthumb.php?id=<?php echo $oldModelMD->getId() ?>" alt="Thumbnail"/></td>
        <td><img src="get_thumbnail_from_mo_sig.php?mo_sig=<?php echo $sig ?>" alt="Thumbnail"/></td>
    </tr>
<?php
    // Now (hopefully) trying to manage the AC3D + XML + PNG texture files stuff
    $newModelFiles = $newModel->getModelFiles();
?>
    <tr>
        <td>Download</td>
        <td colspan="2"><center><a href="model/inc_getfile.php?type=pack&mo_sig=<?php echo $sig; ?>">Download the NEW MODEL as .tar.gz for external viewing.</a></center></td>
    </tr>
    <tr>
        <td>Corresponding AC3D File</td>
        <td colspan="2">
            <h3>Original model:</h3>
            <object data="../../viewer.php?id=<?php echo $oldModelMD->getId(); ?>" type="text/html" width="720px" height="620px"></object>
            <br/>
            <h3>New model:</h3>
            <object data="model/index.php?mo_sig=<?php echo $sig; ?>" type="text/html" width="720px" height="620px"></object>
        </td>
    </tr>
    <tr>
        <td>Corresponding XML File</td>
        <td colspan="2">
<?php
            $xmlContent = $newModelFiles->getXMLFile();
            // Geshi stuff
            if (!empty($xmlContent)) {
                $language = 'xml';
                $geshi = new GeSHi($xmlContent, $language);
                $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
                $geshi->set_line_style('background: #fcfcfc;');
                echo $geshi->parse_code();
            } else {
                echo "No XML file submitted.";
            }
?>
        </td>
    </tr>
    <tr>
        <td>Corresponding PNG Texture Files<br />(click on the pictures to get them bigger)</td>
        <td colspan="2">
            <center>
<?php
            $texturesNames = $newModelFiles->getTexturesNames();
            $png_file_number = count($texturesNames);
            if ($png_file_number <= 1) {
                echo $png_file_number." texture file has been submitted:<br/>\n"; // Some eye caviar for the poor scenery maintainers.
            } else {
                echo $png_file_number." texture files have been submitted:<br/>\n";
            }

            // Sending the directory as parameter. This is no user input, so low risk. Needs to be urlencoded.
            foreach ($texturesNames as $textureName) {
                $texture_file = "http://".$_SERVER['SERVER_NAME'] ."/submission/model/model/inc_getfile.php?type=texture&mo_sig=".$sig."&name=".$textureName;
                $texture_file_tn = "http://".$_SERVER['SERVER_NAME'] ."/submission/model/model/get_texture_tn_by_filename.php?mo_sig=".$sig."&name=".$textureName;

                $tmp = getimagesize($texture_file);
                $width  = $tmp[0];
                $height = $tmp[1];
?>
                <a href="<?php echo $texture_file; ?>" rel="lightbox[submission]" />
                <?php //imagethumb($texture_file) ?>
                <img src="<?php echo $texture_file_tn; ?>" alt="Texture <?php echo $textureName; ?>" />
<?php
                echo $textureName." (Original size: ".$width."x".$height.")</a><br/>";
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
            <input type="hidden" name="mo_sig" value="<?php echo $sig; ?>" />
            <input type="submit" name="action" value="Submit model" />
            <input type="submit" name="action" value="Reject model" />
        </td>
    </tr>
</table>
</form>
<p class="center">This tool uses part of the following software: gl-matrix, by Brandon Jones, and Hangar, by Juan Mellado.</p>
<?php
}
require '../../inc/footer.php';
?>