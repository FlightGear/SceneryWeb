<?php
require_once '../../autoload.php';
$modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = \dao\DAOFactory::getInstance()->getObjectDaoRO();
$requestDaoRO = \dao\DAOFactory::getInstance()->getRequestDaoRO();


if (!FormChecker::isSig($_REQUEST["sig"])) {
    header("Location: /submission/object/");
    exit;
}
$sig = $_REQUEST["sig"];


try {
    $request = $requestDaoRO->getRequest($sig);
} catch(\dao\RequestNotFoundException $e) {
    $pageTitle = "Automated Objects Addition Request Form";
    $errorText = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?<br/>";
    $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
    include '../../inc/error_page.php';
    exit;
}

// Check the presence of "action", the presence of "signature", its 
// length (64) and its content.
if (isset($_GET["action"]) && $_GET["action"] == "check") {
    $pageTitle = "Automated Objects Addition Requests Form";
    include '../../view/header.php';
    echo "<p class=\"center\">Request #". $request->getId()."</p>";
    echo "<p class=\"center\">Email: ".$request->getContributorEmail()."</p>";
    echo "<p class=\"center\">Comment: ".$request->getComment()."</p>";

    echo "<form id=\"check_mass\" method=\"post\" action=\"mass_submission.php\">";
    echo "<table><tr><th>Line #</th><th>Longitude</th><th>Latitude</th><th>Country</th><th>Elevation</th><th>Elev. offset</th><th>True orientation</th><th>Model</th><th>Map</th></tr>";
    $i = 1;
    foreach ($request->getNewObjects() as $newObj) {
        $modelMD = $modelDaoRO->getModelMetadata($newObj->getModelId());
        $newObjPos = $newObj->getPosition();

        echo "<tr>" .
             "<td><center>".$i."</center></td>" .
             "<td><center>".$newObjPos->getLongitude()."</center></td>" .
             "<td><center>".$newObjPos->getLatitude()."</center></td>" .
             "<td><center>".$newObj->getCountry()->getName()."</center></td>" .
             "<td><center>".$newObj->getGroundElevation()."</center></td>" .
             "<td><center>".$newObj->getElevationOffset()."</center></td>" .
             "<td><center>".$newObj->getOrientation()."</center></td>" .
             "<td><center><a href='http://".$_SERVER['SERVER_NAME']."/app.php?c=Models&a=view&id=".$newObj->getModelId()."' target='_blank'>".$modelMD->getName()."</a></center></td>" .
             "<td><center><a href=\"http://mapserver.flightgear.org/popmap/?lon=".$newObjPos->getLongitude()."&amp;lat=".$newObjPos->getLatitude()."&amp;zoom=14\">Map</a></center></td>" .
             "</tr>";

        $i++;
    }
?>
    <tr>
        <td colspan="3">Leave a comment to the submitter</td>
        <td colspan="6"><input type="text" name="maintainer_comment" size="85" placeholder="Drop a comment to the submitter"/></td>
    </tr>
    <tr>
        <td colspan="9" class="submit">
            <input type="hidden" name="sig" value="<?php echo $sig;?>" />
            <input type="submit" name="accept" value="Accept object(s)" />
            <input type="submit" name="cancel" value="Reject!" />
        </td>
    </tr>
    </table>
<?php
    include '../../view/footer.php';
}

// Managing the cancellation of a mass import by DB maintainer.
if (isset($_POST["cancel"])) {
    $requestDaoRW = \dao\DAOFactory::getInstance()->getRequestDaoRW();
    $resultDel = $requestDaoRW->deleteRequest($sig);

    if (!$resultDel) {
        $pageTitle = "Automated Objects Addition Request Form";
        $process_text = "Now deleting request #". $request->getId().".";
        $errorText = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
        include '../../inc/error_page.php';
        exit;
    }

    $pageTitle = "Automated Objects Addition Request Form";
    include '../../view/header.php';
    echo "<center>Now deleting request #". $request->getId().".</center><br />";
    echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.</p>";

    // Sending mail if entry was correctly deleted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');
    $comment = $_POST["maintainer_comment"];

    // email destination
    $to = $request->getContributorEmail();
    $to = (isset($to)) ? $to : '';

    $emailSubmit = EmailContentFactory::getObjectsAddRequestRejectedEmailContent($dtg, $request, $comment);
    $emailSubmit->sendEmail($to, true);

    include '../../view/footer.php';
    exit;
}

// Now managing the insertion
if (isset($_POST["accept"])) {
    $objectDaoRW = \dao\DAOFactory::getInstance()->getObjectDaoRW();
    $requestDaoRW = \dao\DAOFactory::getInstance()->getRequestDaoRW();
    $reqExecutor = new \submission\RequestExecutor(null, $objectDaoRW);

    // Executes request
    try {
        $objsWithId = $reqExecutor->executeRequest($request);
    } catch (Exception $ex) {
        $pageTitle = "Automated Objects Addition Request Form";
        include '../../view/header.php';
        echo "<p class=\"center\">Now processing request #". $request->getId().".</p><br />";
        echo "<p class=\"warning\">Sorry, but the INSERT query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";
        include '../../view/footer.php';
        exit;
    }

    $pageTitle = "Automated Objects Addition Request Form";
    include '../../view/header.php';
    echo "<p class=\"center\">Now processing massive add objects request #". $request->getId().".</p><br />";
    echo "<p class=\"center ok\">".count($objsWithId)." objects were added to the database!</p>";
    echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";


    // Delete the entry from the pending query table.
    try {
        $resultDel = $requestDaoRW->deleteRequest($sig);
    } catch(\dao\RequestNotFoundException $e) {
        echo "<p class=\"warning\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";
        include '../../view/footer.php';
        exit;
    }

    echo "<p class=\"center ok\">Entry correctly deleted from the pending request table.</p>";

    // Sending mail if SQL was correctly inserted and entry deleted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');
    $comment = $_POST["maintainer_comment"];

    // email destination
    $to = $request->getContributorEmail();
    $to = (isset($to)) ? $to : '';

    $emailSubmit = EmailContentFactory::getObjectsAddRequestAcceptedEmailContent($dtg, $request, $comment);
    $emailSubmit->sendEmail($to, true);

    include '../../view/footer.php';
    exit;
}
?>