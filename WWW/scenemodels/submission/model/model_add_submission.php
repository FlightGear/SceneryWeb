<?php
require_once "../../classes/DAOFactory.php";
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();
$requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();

require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';

if (isset($_REQUEST["mo_sig"]) && is_sig($_REQUEST["mo_sig"])) {
    $sig = $_REQUEST["mo_sig"];
} else {
    exit;
}

if (isset($_POST["action"])) {
    // Inserting libs
    include_once '../../classes/EmailContentFactory.php';
    $page_title = "Automated Models Submission Form";

    // Prepare a generic mail
    // If $action=reject
        // - Drop fgs_position_requests;
        // - Send 2 mails

    if ($_POST["action"] == "Reject model") {

        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != '0') {

            // Checking the presence of sig into the database
            $mo_result = pg_query($resource_rw, "SELECT spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $sig ."';");
            if (pg_num_rows($mo_result) != 1) {
                $process_text = "Deleting corresponding pending query.";
                $error_text   = "Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?";
                $advise_text  = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../../inc/error_page.php';
                pg_close($resource_rw);
                exit;
            }

            // Delete the entry from the pending query table.
            $mo_delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $sig ."';";
            $mo_resultdel = pg_query($resource_rw, $mo_delete_request);

            if (!$mo_resultdel) {
                $process_text = "Deleting corresponding pending query.<br/>Signature found.<br /> Now deleting request with number ". $sig;
                $error_text   = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                include '../../inc/error_page.php';

                // Closing the rw connection.
                pg_close($resource_rw);
                exit;
            }

            include '../../inc/header.php';
            echo "<p class=\"center\">Deleting corresponding pending query.</p>";
            echo "<p class=\"center\">";
            echo "Signature found.<br />Now deleting request with number ". $sig." with comment \"". $_POST["maintainer_comment"] ."\".</p>";
            echo "<p class=\"center ok\">Entries have correctly been deleted from the pending requests table.";
            echo "</p>";

            // Closing the rw connection.
            include '../../inc/footer.php';
            pg_close($resource_rw);

            // Sending mail if entry was correctly deleted.
            // Sets the time to UTC.

            date_default_timezone_set('UTC');
            $dtg = date('l jS \of F Y h:i:s A');
            $name = $_POST["mo_name"];
            $comment = $_POST["maintainer_comment"];

            $to = (isset($_POST['email']))?$_POST["email"]:"";

            $emailSubmit = EmailContentFactory::getAddModelRequestRejectedEmailContent($dtg, $sig, $name, $comment);
            $emailSubmit->sendEmail($to, true);
            exit;

            /*echo "The user submission has been rejected with the following warning: ".$_POST["maintainer_comment"].". User has been informed by mail.";
            exit;*/
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
            $mo_result = pg_query($resource_rw, "SELECT spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $sig ."';");
            
            if (pg_num_rows($mo_result) != 1) {
                $error_text = "Sorry but the requests you are asking for do not exist into the database. Maybe they have already been validated by someone else?";
                $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../../inc/error_page.php';
                pg_close($resource_rw);
                exit;
            }

            $row_mo = pg_fetch_row($mo_result);
            $sqlzbase64_mo = $row_mo[0];

            // Base64 decode the query
            $sqlz_mo = base64_decode($sqlzbase64_mo);

            // Gzuncompress the query
            $query = gzuncompress($sqlz_mo);
            $query_rw_mo = substr($query, 0, strpos($query, "INSERT INTO fgs_objects"));
            $query_rw_ob = strstr($query, "INSERT INTO fgs_objects");

            // Sending the requests...
            $result_rw_mo = pg_query($resource_rw, $query_rw_mo);
            $mo_id = pg_fetch_row($result_rw_mo);
            $modelMD = $modelDaoRO->getModelMetadata($mo_id[0]);
            $query_rw_ob_with_mo_id = str_replace("Thisisthevalueformo_id", $mo_id[0], $query_rw_ob); // Adding mo_id in the object request... sorry didn't find a shorter way.
            $query_rw_ob_with_mo_id = $query_rw_ob_with_mo_id." RETURNING ob_id;";

            $result_rw_ob = pg_query($resource_rw, $query_rw_ob_with_mo_id);
            $ret_ob_id = pg_fetch_row($result_rw_ob);
            $query_ob_text = "UPDATE fgs_objects SET ob_text = $$". $modelMD->getName() ."$$ WHERE ob_id = '".$ret_ob_id[0]."';"; // Adding ob_text;
            $result_obtext_update = pg_query($resource_rw, $query_ob_text);

            if (!$result_rw_mo || !$result_rw_ob) {
                $process_text = "Signatures found.<br /> Now processing query with request number ". $sig;
                $error_text = "Sorry, but the INSERT queries could not be processed.";
                $advise_text = "Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                include '../../inc/error_page.php';

                // Closing the rw connection.
                pg_close ($resource_rw);
                exit;
            }

            include '../../inc/header.php';
            echo "<p class=\"center\">";
            echo "Signatures found.<br /> Now processing INSERT queries of model and object with number ".$sig.".</p>";
            echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

            // Delete the entries from the pending query table.
            $delete_request_mo = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $sig ."';";
            $resultdel_mo = pg_query($resource_rw, $delete_request_mo);

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
            $model_id = $mo_id[0];

            // OK, let's start with the mail redaction.
            // Who will receive it ?
            $to = (isset($_POST["email"]))?$_POST["email"]:"";

            $emailSubmit = EmailContentFactory::getAddModelRequestAcceptedEmailContent($dtg, $model_id, $sig, $name, $comment);
            $emailSubmit->sendEmail($to, true);

            include '../../inc/footer.php';
            exit;
        }
    }
    include '../../inc/footer.php';
}

if (!isset($_POST["action"])) {

    // Inserting libs
    include_once '../../inc/geshi/geshi.php';
    $page_title = "Model Submission Form";

    // Checking the presence of sig into the database
    try {
        $requestModelAdd = $requestDaoRO->getRequest($sig);
        $newModel = $requestModelAdd->getNewModel();
        $newModelMD = $newModel->getMetadata();
        $newObj = $requestModelAdd->getNewObject();
    } catch(RequestNotFoundException $e) {
        $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
        $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
        include '../../inc/error_page.php';
        exit;
    }

include '../../inc/header.php';

?>

<p class="center">Hi, this is the static submission form at http://<?php echo $_SERVER['SERVER_NAME'];?>/submission/model.</p>
<p class="center">The following model has passed all (numerous) verifications by the forementionned script. It should be fine to validate it. However, it's always sane to eye-check it.</p>

<form id="validation" method="post" action="model_add_submission.php" onsubmit="return validateForm();">
<table>
    <tr>
        <th>Data</th>
        <th>Value</th>
    </tr>
    <tr>
        <td>Author</td>
        <td><?php echo $newModelMD->getAuthor()->getName(); ?></td>
    </tr>
    <tr>
        <td>Email</td>
        <td><?php echo $_GET["email"]; ?></td>
        <input type="hidden" name="email" value="<?php echo htmlentities($_GET["email"]); ?>" />
    </tr>
    <tr>
        <td>Family</td>
        <td><?php echo $newModelMD->getModelsGroup()->getName(); ?></td>
    </tr>
    <tr>
        <td>Proposed Path Name</td>
        <td><?php echo $newModelMD->getFilename(); ?></td>
    </tr>
    <tr>
        <td>Full Name</td>
        <td><?php echo $newModelMD->getName(); ?></td>
        <input type="hidden" name="mo_name" value="<?php echo $newModelMD->getName(); ?>" />
    </tr>
    <tr>
        <td>Notes</td>
        <td><?php echo $newModelMD->getDescription(); ?></td>
    </tr>
    <tr>
        <td>Latitude</td>
        <td><?php echo $newObj->getLatitude(); ?></td>
    </tr>
    <tr>
        <td>Longitude</td>
        <td><?php echo $newObj->getLongitude(); ?></td>
    </tr>
    <tr>
        <td>Map</td>
        <td>
        <center>
        <object data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $newObj->getLongitude(); ?>&amp;lat=<?php echo $newObj->getLatitude(); ?>&amp;zoom=14" type="text/html" width="320" height="240"/>
        </center>
        </td>
    </tr>
    <tr>
        <td>Country</td>
        <td><?php echo $newObj->getCountry()->getName(); ?></td>
    </tr>
    <tr>
        <td>Ground Elevation</td>
        <td><?php echo $newObj->getGroundElevation(); ?></td>
    </tr>
    <tr>
        <td>Elevation offset</td>
        <td><?php echo $newObj->getElevationOffset(); ?></td>
    </tr>
    <tr>
        <td>True DB orientation</td>
        <td><?php echo $newObj->getOrientation(); ?></td>
    </tr>
    <tr>
        <td>Corresponding Thumbnail</td>
        <td><center><img src="get_thumbnail_from_mo_sig.php?mo_sig=<?php echo $sig ?>" alt="Thumbnail"/></center></td>
    </tr>
<?php
    // Now (hopefully) trying to manage the AC3D + XML + PNG texture files stuff
    $modelFiles = $newModel->getModelFiles();
?>
    <tr>
        <td>Download</td>
        <td><center><a href="get_targz_from_mo_sig.php?mo_sig=<?php echo $sig; ?>">Download the submission as .tar.gz for external viewing.</a></center></td>
    </tr>
    <tr>
        <td>Corresponding AC3D File</td>
        <td>
            <center>
                <object data="model/index.php?mo_sig=<?php echo $sig; ?>" type="text/html" width="720px" height="620px"/>
            </center>
        </td>
    </tr>
    <tr>
        <td>Corresponding XML File</td>
        <td>
<?php
            $xmlContent = $modelFiles->getXMLFile();
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
        <td>
            <center>
<?php
            $texturesNames = $modelFiles->getTexturesNames();
            $png_file_number = count($texturesNames);
            if ($png_file_number <= 1) {
                echo $png_file_number." texture file has been submitted:<br/>\n"; // Some eye caviar for the poor scenery maintainers.
            } else {
                echo $png_file_number." texture files have been submitted:<br/>\n";
            }

            // Sending the directory as parameter. This is no user input, so low risk. Needs to be urlencoded.
            foreach ($texturesNames as $textureName) {
                $texture_file = "http://".$_SERVER['SERVER_NAME'] ."/submission/model/model/get_texture_by_filename.php?mo_sig=".$sig."&name=".$textureName;
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
        <td><input type="text" name="maintainer_comment" size="85" placeholder="Drop a comment to the submitter"/></td>
    </tr>
    <tr>
        <td>Action</td>
        <td class="submit">
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