<?php
require_once "../../classes/RequestExecutor.php";
require_once "../../classes/DAOFactory.php";
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
require_once '../../classes/EmailContentFactory.php';

if (!is_sig($_REQUEST["sig"])) {
    header("Location: /submission/object/");
    exit;
}
$sig = $_REQUEST["sig"];

// Check the presence of "action", the presence of "signature", its 
// length (64) and its content.
if (isset($_GET["action"]) && $_GET["action"] == "check") {
    $requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();
    
    // Checking the presence of sig into the database
    try {
        $request = $requestDaoRO->getRequest($sig);
    } catch (RequestNotFoundException $e) {
        $page_title = "Automated Objects Massive Import Request Form";
        $error_text = "Sorry but the request you are asking for does " .
                      "not exist into the database. Maybe it has " .
                      "already been validated by someone else?<br/>";
        $advise_text = "Else, please report to devel ML or FG Scenery forum.";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Objects Massive Import Requests Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $sig.".\n</p>\n";

    echo "<form id=\"check_mass\" method=\"post\" action=\"mass_submission.php\">";
    echo "<table>\n<tr>\n<th>Line #</th>\n<th>Longitude</th>\n<th>Latitude</th>\n<th>Country</th>\n<th>Elevation</th>\n<th>Elev. offset</th>\n<th>True orientation</th>\n<th>Model</th>\n<th>Map</th>\n</tr>\n";
    $i = 1;
    foreach ($request->getNewObjects() as $newObj) {
        $modelMD = $modelDaoRO->getModelMetadata($newObj->getModelId());

        echo "<tr>\n" .
             "<td><center>".$i."</center></td>\n" .
             "<td><center>".$newObj->getLongitude()."</center></td>\n" .
             "<td><center>".$newObj->getLatitude()."</center></td>\n" .
             "<td><center>".$newObj->getCountry()->getName()."</center></td>\n" .
             "<td><center>".$newObj->getGroundElevation()."</center></td>\n" .
             "<td><center>".$newObj->getElevationOffset()."</center></td>\n" .
             "<td><center>".$newObj->getOrientation()."</center></td>\n" .
             "<td><center><a href='http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$newObj->getModelId()."' target='_blank'>".$modelMD->getName()."</a></center></td>\n" .
             "<td><center><a href=\"http://mapserver.flightgear.org/popmap/?lon=".$newObj->getLongitude()."&amp;lat=".$newObj->getLatitude()."&amp;zoom=14\">Map</a></center></td>\n" .
             "</tr>\n";

        $i++;
    }
?>
    <tr>
        <td colspan="3">Leave a comment to the submitter</td>
        <td colspan="6"><input type="text" name="maintainer_comment" size="85" placeholder="Drop a comment to the submitter"/></td>
    </tr>
    <tr>
        <td colspan="9" class="submit">
            <?php echo "<input type=\"hidden\" name=\"email\" value=\"".$_GET["email"]."\" />"; ?>
            <?php echo "<input type=\"hidden\" name=\"sig\" value=\"".$sig."\" />"; ?>
            <input type="submit" name="submit" value="Submit the mass import!" />
            <input type="submit" name="cancel" value="Reject - Do not import!" />
        </td>
    </tr>
    </table>
<?php
    include '../../inc/footer.php';
}

// Managing the cancellation of a mass import by DB maintainer.
if (isset($_POST["cancel"]) && ($_POST["cancel"] == "Reject - Do not import!")) {
    $requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();

    try {
        $resultDel = $requestDaoRW->deleteRequest($sig);
    } catch(RequestNotFoundException $e) {
        $page_title = "Automated Objects Massive Import Request Form";
        $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?<br/>";
        $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
        include '../../inc/error_page.php';
        exit;
    }

    if (!$resultDel) {
        $page_title = "Automated Objects Massive Import Request Form";
        $process_text = "Signature found.<br /> Now deleting request with number ". $sig.".";
        $error_text = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Objects Massive Import Request Form";
    include '../../inc/header.php';
    echo "<center>Signature found.<br />Now deleting request with number ". $sig.".</center><br />";
    echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.</p>";

    // Sending mail if entry was correctly deleted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');
    $comment = $_POST["maintainer_comment"];

    // email destination
    $to = (isset($_POST['email'])) ? $_POST["email"] : '';

    $emailSubmit = EmailContentFactory::getMassImportRequestRejectedEmailContent($dtg, $sig, $comment);
    $emailSubmit->sendEmail($to, true);

    include '../../inc/footer.php';
    exit;
}

// Now managing the insertion
if (isset($_POST["submit"]) && $_POST["submit"] == "Submit the mass import!") {
    $objectDaoRW = DAOFactory::getInstance()->getObjectDaoRW();
    $requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();
    $requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();
    $reqExecutor = new RequestExecutor(null, $objectDaoRW);
    
    // Checking the presence of sig into the database
    try {
        $massObjReq = $requestDaoRO->getRequest($sig);
    } catch (RequestNotFoundException $e) {
        $page_title = "Automated Objects Massive Import Request Form";
        $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
        $advise_text = "Else, please report to devel ML or FG Scenery forum";
        include '../../inc/error_page.php';
        exit;
    }
    
     // Executes request
    try {
        $objsWithId = $reqExecutor->executeRequest($massObjReq);
    } catch (Exception $ex) {
        $page_title = "Automated Objects Massive Insertion Request Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $sig.".</p><br />";
        echo "<p class=\"warning\">Sorry, but the INSERT query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";
        include '../../inc/footer.php';
        exit;
    }

    $page_title = "Automated Objects Massive Insertion Request Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">Signature found.<br /> Now processing INSERT position query with number ". $sig.".</p><br />\n";
    echo "<p class=\"center ok\">".count($objsWithId)." objects were added to the database!</p>\n";
    echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";


    // Delete the entry from the pending query table.
    try {
        $resultDel = $requestDaoRW->deleteRequest($sig);
    } catch(RequestNotFoundException $e) {
        echo "<p class=\"warning\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";
        include '../../inc/footer.php';
        exit;
    }

    echo "<p class=\"center ok\">Entry correctly deleted from the pending request table.</p>";

    // Sending mail if SQL was correctly inserted and entry deleted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');
    $comment = $_POST["maintainer_comment"];

    // email destination
    $to = (isset($_POST['email'])) ? $_POST["email"] : '';

    $emailSubmit = EmailContentFactory::getMassImportRequestAcceptedEmailContent($dtg, $sig, $comment);
    $emailSubmit->sendEmail($to, true);

    include '../../inc/footer.php';
    exit;
}
?>