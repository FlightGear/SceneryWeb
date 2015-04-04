<?php
require_once '../../autoload.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();
$requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();

// Inserting libs
require_once '../../inc/functions.inc.php';

if (!FormChecker::isSig($_REQUEST["sig"]) || !isset($_REQUEST["action"])) {
    header("Location: /submission/object/");
    exit;
}

$action = $_REQUEST["action"];
$sig = htmlentities($_REQUEST["sig"]);

// Checking the presence of sig into the database
try {
    $request = $requestDaoRO->getRequest($sig);
} catch (RequestNotFoundException $e) {
    $page_title = "Automated Objects Pending Requests Form";
    $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
    $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
    include '../../inc/error_page.php';
    exit;
}

// Common code, to be performed for both types of checks
if ($action == "check") {
    $page_title = "Automated Objects Pending Requests Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">Request #". $request->getId()."</p>" .
         "<p class=\"center\">Email: ".$request->getContributorEmail()."</p>".
         "<p class=\"center\">Comment: ".$request->getComment()."</p>";
    
    switch (get_class($request)) {
    case "RequestObjectUpdate":
        $oldObject = $request->getOldObject();
        $newObject = $request->getNewObject();

        $newModelMD = $modelDaoRO->getModelMetadata($newObject->getModelId());
        $oldModelMD = $modelDaoRO->getModelMetadata($oldObject->getModelId());

        // Obtain old/current values
        echo "<table><tr><th></th><th>Old/current</th><th>New</th></tr>";

        echo "<tr";
        if ($oldObject->getDescription() != $newObject->getDescription()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Description</td><td>".$oldObject->getDescription()."</td><td>".$newObject->getDescription()."</td></tr>";
        echo "<tr";
        if ($oldModelMD->getId() != $newModelMD->getId()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Object's model</td><td>".$oldModelMD->getName()."</td><td>".$newModelMD->getName()."</td></tr>";
        echo "<tr><td>Thumbnail</td><td><img src='../../modelthumb.php?id=".$oldModelMD->getId()."' alt=''/></td>".
                "<td><img src='../../modelthumb.php?id=".$newModelMD->getId()."' alt=''/></td></tr>";
        echo "<tr";
        if ($oldObject->getLongitude() != $newObject->getLongitude()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Longitude</td><td>".$oldObject->getLongitude()."</td><td>".$newObject->getLongitude()."</td></tr>";
        echo "<tr";
        if ($oldObject->getLatitude() != $newObject->getLatitude()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Latitude</td><td>".$oldObject->getLatitude()."</td><td>".$newObject->getLatitude()."</td></tr>";
        echo "<tr";
        if ($oldObject->getCountry()->getCode() != $newObject->getCountry()->getCode()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Country</td><td>".$oldObject->getCountry()->getName()."</td><td>".$newObject->getCountry()->getName()."</td></tr>";
        
        echo "<tr style=\"background-color: rgb(255, 200, 0)\">";
        echo "<td>Elevation</td><td>".$oldObject->getGroundElevation()."</td><td>Will be recalculated</td></tr>";
        echo "<tr";
        if ($oldObject->getElevationOffset() != $newObject->getElevationOffset()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Elevation offset</td><td>".$oldObject->getElevationOffset()."</td><td>".$newObject->getElevationOffset()."</td></tr>";
        
        echo "<tr";
        if ($oldObject->getOrientation() != $newObject->getOrientation()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Heading (STG)</td><td>".heading_true_to_stg($oldObject->getOrientation())." (STG) - ".$oldObject->getOrientation()."(true)</td>".
             "<td>".heading_true_to_stg($newObject->getOrientation())." (STG) - ".$newObject->getOrientation()." (true)</td></tr>";
        
        echo "<tr><td>Map</td><td><object data=\"http://mapserver.flightgear.org/popmap/?lon=".$oldObject->getLongitude()."&amp;lat=".$oldObject->getLatitude()."&amp;zoom=14\" type=\"text/html\" width=\"100%\" height=\"240\"></object></td>".
             "<td><object data=\"http://mapserver.flightgear.org/popmap/?lon=".$newObject->getLongitude()."&amp;lat=".$newObject->getLatitude()."&amp;zoom=14\" type=\"text/html\" width=\"100%\" height=\"240\"></object></td></tr>" .
             "</tr>";
        break;
        
    case "RequestObjectDelete":

        $objectToDel = $request->getObjectToDelete();
        $modelMD = $modelDaoRO->getModelMetadata($objectToDel->getModelId());

        echo "<table><tr><th>Obj. ID</th><th>Longitude</th><th>Latitude</th><th>Country</th><th>Elevation</th><th>Elev. offset</th><th>True orientation</th><th>Model</th><th>Map</th></tr>";
        echo "<tr>" .
             "<td><center>".$objectToDel->getId()."</center></td>" .
             "<td><center>".$objectToDel->getLongitude()."</center></td>" .
             "<td><center>".$objectToDel->getLatitude()."</center></td>" .
             "<td><center>".$objectToDel->getCountry()->getName()."</center></td>" .
             "<td><center>".$objectToDel->getGroundElevation()."</center></td>" .
             "<td><center>".$objectToDel->getElevationOffset()."</center></td>" .
             "<td><center>".$objectToDel->getOrientation()."</center></td>" .
             "<td><center><a href=\"http://scenemodels.flightgear.org/modelview.php?id=".$modelMD->getId()."\">".$modelMD->getName()."</a></center></td>" .
             "<td><center><a href=\"http://mapserver.flightgear.org/popmap/?lon=".$objectToDel->getLongitude()."&amp;lat=".$objectToDel->getLatitude()."&amp;zoom=14\">Map</a></center></td>" .
             "</tr>";
        break;
    }
?>

    <tr>
        <td colspan="8" class="submit">
            <form action="submission.php" method="POST">
                <input type="hidden" name="sig" value="<?php echo $sig;?>"/>
                Comment : <input type="text" name="maintainer_comment" size="85" placeholder="Drop a comment to the submitter"/><br/>

                <input type="submit" name="action" value="Accept" />
                <input type="submit" name="action" value="Reject" />
            </form>
        </td>
    </tr>
    </table>
<?php
    include '../../inc/footer.php';
    exit;
}

// Check the presence of "action", the presence of "signature", its length (64) and its content.
if ($action == 'Accept') {
    $objectDaoRW = DAOFactory::getInstance()->getObjectDaoRW();
    $requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();
    $reqExecutor = new RequestExecutor(null, $objectDaoRW);

    // Executes request
    try {
        $reqExecutor->executeRequest($request);
    } catch (Exception $ex) {
        $page_title = "Automated Objects Pending Requests Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">";
        echo "Now processing request #".$request->getId().".</p><br />";
        echo "<p class=\"center warning\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";
        include '../../inc/footer.php';
        exit;
    }

    $page_title = "Automated Objects Pending Requests Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">Now processing add/update/delete object request #".$request->getId().".</p><br />";
    echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

    // Delete the entry from the pending query table.
    try {
        $resultDel = $requestDaoRW->deleteRequest($sig);
    } catch(RequestNotFoundException $e) {
        echo "<p class=\"center warning\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";
        include '../../inc/footer.php';
        exit;
    }

    echo "<p class=\"center ok\">Entry correctly deleted from the pending request table.</p>";

    // Sending mail if SQL was correctly inserted and entry deleted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');
    $comment = $_REQUEST['maintainer_comment'];

    // email destination
    $to = $request->getContributorEmail();
    $to = (isset($to)) ? $to : '';

    $emailSubmit = EmailContentFactory::getObjectRequestAcceptedEmailContent($request, $comment);
    $emailSubmit->sendEmail($to, true);

    exit;
}

// If it's not to validate the submission... it's to delete it... check the presence of "action", the presence of "signature", its length (64), its content.
else if ($action == "Reject") {
    $requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();

    try {
        $resultDel = $requestDaoRW->deleteRequest($sig);
    } catch(RequestNotFoundException $e) {
        $page_title = "Automated Objects Pending Requests Form";
        $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?";
        $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
        include '../../inc/error_page.php';
        exit;
    }

    if (!$resultDel) {
        $page_title = "Automated Objects Pending Requests Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">".
             "Now deleting request #".$request->getId().".</p>".
             "<p class=\"center warning\">Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br/>";
        include '../../inc/footer.php';
        exit;
    }

    $page_title = "Automated Objects Pending Requests Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">";
    echo "Now deleting request #".$request->getId().".</p>";
    echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.";
    echo "</p>";
    include '../../inc/footer.php';

    // Sending mail if entry was correctly deleted.
    // Sets the time to UTC.

    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');
    $comment = $_REQUEST['maintainer_comment'];

    // email destination
    $to = $request->getContributorEmail();
    $to = (isset($to)) ? $to : '';

    $emailSubmit = EmailContentFactory::getObjectRejectedEmailContent($request, $comment);
    $emailSubmit->sendEmail($to, true);
}
// Sending the visitor elsewhere if he has no idea what he's doing here.
else {
    header("Location: /submission/object/");
}
?>