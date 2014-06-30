<?php
require_once "../../classes/DAOFactory.php";
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
require_once '../../classes/EmailContentFactory.php';

if (!is_sig($_REQUEST["sig"]) || !isset($_REQUEST["action"])) {
    header("Location: /submission/object/");
    exit;
}

$action = $_REQUEST["action"];
$sig = htmlentities($_REQUEST["sig"]);

// Common code, to be performed for both types of checks
if ($action == "check" || $action == "check_update" || $action == "check_delete") {
    $requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();
    
    // Checking the presence of sig into the database
    try {
        $request = $requestDaoRO->getRequest($sig);
    } catch (RequestNotFoundException $e) {
        $page_title = "Automated Objects Pending Requests Form";
        $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?<br/>";
        $advise_text = "Else, please report to devel ML or FG Scenery forum.";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Objects Pending Requests Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $sig.".\n</p>\n";

    switch (get_class($request)) {
    case "RequestObjectAdd":
        $newObj = $request->getNewObject();
        $modelMD = $modelDaoRO->getModelMetadata($newObj->getModelId());

        echo "<table>\n<tr>\n<th>Longitude</th>\n<th>Latitude</th>\n<th>Country</th>\n<th>Elevation</th>\n<th>Elev. offset</th>\n<th>True orientation</th>\n<th>Model</th>\n<th>Map</th>\n</tr>\n";
        echo "<tr>\n" .
             "<td><center>".$newObj->getLongitude()."</center></td>\n" .
             "<td><center>".$newObj->getLatitude()."</center></td>\n" .
             "<td><center>".$newObj->getCountry()->getName()."</center></td>\n" .
             "<td><center>".$newObj->getGroundElevation()."</center></td>\n" .
             "<td><center>".$newObj->getElevationOffset()."</center></td>\n" .
             "<td><center>".$newObj->getOrientation()."</center></td>\n" .
             "<td><center><a href=\"http://scenemodels.flightgear.org/modelview.php?id=".$modelMD->getId()."\">".$modelMD->getName()."</a></center></td>\n" .
             "<td><center><a href=\"http://mapserver.flightgear.org/popmap/?lon=".$newObj->getLongitude()."&amp;lat=".$newObj->getLatitude()."&amp;zoom=14\">Map</a></center></td>\n" .
             "</tr>\n";
        break;
    
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
        echo "><td>Description</td><td>".$oldObject->getDescription()."</td><td>".$newObject->getDescription()."</td></tr>\n";
        echo "<tr";
        if ($oldObject->getLongitude() != $newObject->getLongitude()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Longitude</td><td>".$oldObject->getLongitude()."</td><td>".$newObject->getLongitude()."</td></tr>\n";
        echo "<tr";
        if ($oldObject->getLatitude() != $newObject->getLatitude()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Latitude</td><td>".$oldObject->getLatitude()."</td><td>".$newObject->getLatitude()."</td></tr>\n";

        echo "<tr style=\"background-color: rgb(255, 200, 0)\">";
        echo "<td>Elevation</td><td>".$oldObject->getGroundElevation()."</td><td>".$newObject->getGroundElevation()."</td></tr>\n";
        echo "<tr";
        if ($oldObject->getElevationOffset() != $newObject->getElevationOffset()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Elevation offset</td><td>".$oldObject->getElevationOffset()."</td><td>".$newObject->getElevationOffset()."</td></tr>\n";
        echo "<tr";
        if ($oldObject->getOrientation() != $newObject->getOrientation()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Heading (STG)</td><td>".heading_true_to_stg($oldObject->getOrientation())." (STG) - ".$oldObject->getOrientation()."(true)</td>".
             "<td>".heading_true_to_stg($newObject->getOrientation())." (STG) - ".$newObject->getOrientation()." (true)</td></tr>\n";
        echo "<tr";
        if ($oldModelMD->getId() != $newModelMD->getId()) {
            echo " style=\"background-color: rgb(255, 200, 0)\"";
        }
        echo "><td>Object's model</td><td>".$oldModelMD->getName()."</td><td>".$newModelMD->getName()."</td></tr>\n";
        echo "<tr><td>Map</td><td><object data=\"http://mapserver.flightgear.org/popmap/?lon=".$oldObject->getLongitude()."&amp;lat=".$oldObject->getLatitude()."&amp;zoom=14\" type=\"text/html\" width=\"100%\" height=\"240\"></object></td>".
             "<td><object data=\"http://mapserver.flightgear.org/popmap/?lon=".$newObject->getLongitude()."&amp;lat=".$newObject->getLatitude()."&amp;zoom=14\" type=\"text/html\" width=\"100%\" height=\"240\"></object></td></tr>\n" .
             "</tr>\n";
        break;
        
    case "RequestObjectDelete":

        $objectToDel = $request->getObjectToDelete();
        $modelMD = $modelDaoRO->getModelMetadata($objectToDel->getModelId());

        echo "<table>\n<tr>\n<th>Longitude</th>\n<th>Latitude</th>\n<th>Country</th>\n<th>Elevation</th>\n<th>Elev. offset</th>\n<th>True orientation</th>\n<th>Model</th>\n<th>Map</th>\n</tr>\n";
        echo "<tr>\n" .
             "<td><center>".$objectToDel->getLongitude()."</center></td>\n" .
             "<td><center>".$objectToDel->getLatitude()."</center></td>\n" .
             "<td><center>".$objectToDel->getCountry()->getName()."</center></td>\n" .
             "<td><center>".$objectToDel->getGroundElevation()."</center></td>\n" .
             "<td><center>".$objectToDel->getElevationOffset()."</center></td>\n" .
             "<td><center>".$objectToDel->getOrientation()."</center></td>\n" .
             "<td><center><a href=\"http://scenemodels.flightgear.org/modelview.php?id=".$modelMD->getId()."\">".$modelMD->getName()."</a></center></td>\n" .
             "<td><center><a href=\"http://mapserver.flightgear.org/popmap/?lon=".$objectToDel->getLongitude()."&amp;lat=".$objectToDel->getLatitude()."&amp;zoom=14\">Map</a></center></td>\n" .
             "</tr>\n";
        break;
    }
?>

    <tr>
        <td colspan="8" class="submit">
            <form action="submission.php" method="POST">
                <input type="hidden" name="sig" value="<?php echo $sig;?>"/>
                <input type="hidden" name="email" value="<?php echo $_GET["email"];?>"/>
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
    $resource_rw = connect_sphere_rw();

    // If connection is OK
    if ($resource_rw != '0') {

    // Checking the presence of sig into the database
        $result = pg_query($resource_rw,"SELECT spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $sig ."';");
        if (pg_num_rows($result) != 1) {
            $page_title = "Automated Objects Pending Requests Form";
            $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
            $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
            include '../../inc/error_page.php';
            pg_close($resource_rw);
            exit;
        }

        $row = pg_fetch_row($result);
        $sqlzbase64 = $row[0];

        // Base64 decode the query
        $sqlz = base64_decode($sqlzbase64);

        // Gzuncompress the query
        $query_rw = gzuncompress($sqlz);

        // Sending the request...
        $resultrw = pg_query($resource_rw, $query_rw);

        if (!$resultrw) {
            $page_title = "Automated Objects Pending Requests Form";
            include '../../inc/header.php';
            echo "<p class=\"center\">";
            echo "Signature found.<br /> Now processing query with request number ".$sig.".</p><br />";
            echo "<p class=\"center warning\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

            // Closing the rw connection.
            include '../../inc/footer.php';
            pg_close($resource_rw);
            exit;
        }

        $page_title = "Automated Objects Pending Requests Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">Signature found.<br /> Now processing INSERT or DELETE or UPDATE position query with number ".$sig.".</p><br />";
        echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

        // Delete the entry from the pending query table.
        $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $sig ."';";
        $resultdel = pg_query($resource_rw,$delete_request);

        if(!$resultdel) {
            echo "<p class=\"center warning\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

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
        $comment = $_REQUEST['maintainer_comment'];

        // email destination
        $to = (isset($_REQUEST['email'])) ? $_REQUEST['email'] : '';

        $emailSubmit = EmailContentFactory::getPendingRequestProcessConfirmationEmailContent($sig, $comment);
        $emailSubmit->sendEmail($to, true);

        exit;
    }
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
        echo "<p class=\"center\">\n".
             "Signature found.<br /> Now deleting request with number ".$sig.".</p>".
             "<p class=\"center warning\">Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br/>\n";

        // Closing the rw connection.
        include '../../inc/footer.php';
        exit;
    }

    $page_title = "Automated Objects Pending Requests Form";
    include '../../inc/header.php';
    echo "<p class=\"center\">";
    echo "Signature found.<br />Now deleting request with number ".$sig.".</p>";
    echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.";
    echo "</p>";

    // Closing the rw connection.
    include '../../inc/footer.php';

    // Sending mail if entry was correctly deleted.
    // Sets the time to UTC.

    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');
    $comment = $_REQUEST['maintainer_comment'];

    // email destination
    $to = (isset($_REQUEST['email'])) ? $_REQUEST['email'] : '';

    $emailSubmit = EmailContentFactory::getRejectAndDeletionConfirmationEmailContent($sig, $comment);
    $emailSubmit->sendEmail($to, true);

    exit;
}
// Sending the visitor elsewhere if he has no idea what he's doing here.
else {
    header("Location: /submission/object/");
}
?>