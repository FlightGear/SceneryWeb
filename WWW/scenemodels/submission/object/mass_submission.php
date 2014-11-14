<?php
require_once '../../autoload.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();
$requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();

// Inserting libs
require_once '../../inc/functions.inc.php';

if (!FormChecker::isSig($_REQUEST["sig"])) {
    header("Location: /submission/object/");
    exit;
}
$sig = $_REQUEST["sig"];


try {
    $request = $requestDaoRO->getRequest($sig);
} catch(RequestNotFoundException $e) {
    $page_title = "Automated Objects Addition Request Form";
    $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?<br/>";
    $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
    include '../../inc/error_page.php';
    exit;
}

// Check the presence of "action", the presence of "signature", its 
// length (64) and its content.
if (isset($_GET["action"]) && $_GET["action"] == "check") {
    $page_title = "Automated Objects Addition Requests Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">Signature found.<br /> Now processing request #". $request->getId().".\n</p>\n";

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
            <input type="submit" name="accept" value="Accept object(s)" />
            <input type="submit" name="cancel" value="Reject!" />
        </td>
    </tr>
    </table>
<?php
    include '../../inc/footer.php';
}

// Managing the cancellation of a mass import by DB maintainer.
if (isset($_POST["cancel"])) {
    $requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();
    $resultDel = $requestDaoRW->deleteRequest($sig);

    if (!$resultDel) {
        $page_title = "Automated Objects Addition Request Form";
        $process_text = "Signature found.<br /> Now deleting request #". $request->getId().".";
        $error_text = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Objects Addition Request Form";
    include '../../inc/header.php';
    echo "<center>Signature found.<br />Now deleting request #". $request->getId().".</center><br />";
    echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.</p>";

    // Sending mail if entry was correctly deleted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');
    $comment = $_POST["maintainer_comment"];

    // email destination
    $to = (isset($_POST['email'])) ? $_POST["email"] : '';

    $emailSubmit = EmailContentFactory::getMassImportRequestRejectedEmailContent($dtg, $request, $comment);
    $emailSubmit->sendEmail($to, true);

    include '../../inc/footer.php';
    exit;
}

// Now managing the insertion
if (isset($_POST["accept"])) {
    $objectDaoRW = DAOFactory::getInstance()->getObjectDaoRW();
    $requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();
    $reqExecutor = new RequestExecutor(null, $objectDaoRW);

    // Executes request
    try {
        $objsWithId = $reqExecutor->executeRequest($request);
    } catch (Exception $ex) {
        $page_title = "Automated Objects Addition Request Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">Signature found.<br /> Now processing request #". $request->getId().".</p><br />";
        echo "<p class=\"warning\">Sorry, but the INSERT query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";
        include '../../inc/footer.php';
        exit;
    }

    $page_title = "Automated Objects Addition Request Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">Signature found.<br /> Now processing massive add objects request #". $request->getId().".</p><br />\n";
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

    $emailSubmit = EmailContentFactory::getObjectsAddRequestAcceptedEmailContent($dtg, $request, $comment);
    $emailSubmit->sendEmail($to, true);

    include '../../inc/footer.php';
    exit;
}
?>