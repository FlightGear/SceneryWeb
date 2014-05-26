<?php
require_once "../../classes/DAOFactory.php";
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
require_once '../../classes/EmailContentFactory.php';

if (!is_sig($_GET["sig"]) || !isset($_GET["action"])) {
    header("Location: /submission/object/");
    exit;
}

// Common code, to be performed for both types of checks
if ($_GET["action"] == "check" || $_GET["action"] == "check_update") {
    $resource_rw = connect_sphere_rw();

    // If connection is OK
    if ($resource_rw != '0') {

        // Checking the presence of sig into the database
        $result = pg_query($resource_rw, "SELECT spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';");
        if (pg_num_rows($result) != 1) {
            $page_title = "Automated Objects Pending Requests Form";
            $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?<br/>";
            $advise_text = "Else, please report to devel ML or FG Scenery forum.";
            include '../../inc/error_page.php';
            pg_close($resource_rw);
            exit;
        }

        // Show results
        $row = pg_fetch_row($result);
        $sqlzbase64 = $row[0];

        // Base64 decode the query
        $sqlz = base64_decode($sqlzbase64);

        // Gzuncompress the query
        $query_rw = gzuncompress($sqlz);
        $page_title = "Automated Objects Pending Requests Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $_GET["sig"].".\n</p>\n";

        if ($_GET["action"] == "check") {
            // Removing the start of the query from the data
            $trigged_query_rw = strstr($query_rw, 'ST_PointFromText');

            echo "<table>\n<tr>\n<th>Longitude</th>\n<th>Latitude</th>\n<th>Country</th>\n<th>Elevation</th>\n<th>Elev. offset</th>\n<th>True orientation</th>\n<th>Model</th>\n<th>Map</th>\n</tr>\n";

            $pattern = "/ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>(([0-9.-]+)|NULL)), (?P<orientation>[0-9.-]+), '(?P<country>[a-z]+)', (?P<model_id>[0-9]+), 1\)/";

            preg_match($pattern, $trigged_query_rw, $matches);

            $long = $matches['long'];
            $lat = $matches['lat'];
            $elev = $matches['elev'];
            $elevoffset = $matches['elevoffset'];
            $orientation = $matches['orientation'];
            $country = $objectDaoRO->getCountry($matches['country']);
            $modelMD = $modelDaoRO->getModelMetadata($matches['model_id']);

            echo "<tr>\n" .
                 "<td><center>".$long."</center></td>\n" .
                 "<td><center>".$lat."</center></td>\n" .
                 "<td><center>".$country->getName()."</center></td>\n" .
                 "<td><center>".$elev."</center></td>\n" .
                 "<td><center>".$elevoffset."</center></td>\n" .
                 "<td><center>".$orientation."</center></td>\n" .
                 "<td><center><a href=\"http://scenemodels.flightgear.org/modelview.php?id=".$modelMD->getId()."\">".$modelMD->getName()."</a></center></td>\n" .
                 "<td><center><a href=\"http://mapserver.flightgear.org/popmap/?lon=".$long."&amp;lat=".$lat."&amp;zoom=14\">Map</a></center></td>\n" .
                 "</tr>\n";
        } elseif ($_GET["action"] == "check_update") {
            // Removing the start of the query from the data
            $trigged_query_rw = strstr($query_rw, 'SET');
            $trigged_query_rw = str_replace('$','',$trigged_query_rw);

            echo "<table>\n<tr>\n<th></th>\n<th>Old/current</th>\n<th>New</th>\n</tr>\n";

            $pattern = "/SET ob_text\=(?P<notes>[a-zA-Z0-9 +,!_.;\(\)\[\]\/-]*), wkb_geometry\=ST_PointFromText\('POINT\((?P<lon>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), ob_gndelev\=(?P<elev>[0-9.-]+), ob_elevoffset\=(?P<elevoffset>(([0-9.-]+)|NULL)), ob_heading\=(?P<orientation>[0-9.-]+), ob_model\=(?P<model_id>[0-9]+), ob_group\=1 WHERE ob_id\=(?P<object_id>[0-9]+)/";

            preg_match($pattern, $trigged_query_rw, $matches);

            $notes = $matches['notes'];
            $lon = $matches['lon'];
            $lat = $matches['lat'];
            $elev = $matches['elev'];
            $elevoffset = $matches['elevoffset'];
            $orientation = $matches['orientation'];
            // $country = $matches['country'];
            $newModelMD = $modelDaoRO->getModelMetadata($matches['model_id']);
            $object_id = $matches['object_id'];
            $oldObject = $objectDaoRO->getObject($object_id);
            $oldModelMD = $modelDaoRO->getModelMetadata($oldObject->getModelId());

            // Obtain old/current values

            echo "<tr";
            if ($oldObject->getDescription() != $notes) {
                echo " style=\"background-color: rgb(255, 200, 0)\"";
            }
            echo "><td>Description</td><td>".$oldObject->getDescription()."</td><td>".$notes."</td></tr>\n";
            echo "<tr";
            if ($oldObject->getLongitude() != $lon) {
                echo " style=\"background-color: rgb(255, 200, 0)\"";
            }
            echo "><td>Longitude</td><td>".$oldObject->getLongitude()."</td><td>".$lon."</td></tr>\n";
            echo "<tr";
            if ($oldObject->getLatitude() != $lat) {
                echo " style=\"background-color: rgb(255, 200, 0)\"";
            }
            echo "><td>Latitude</td><td>".$oldObject->getLatitude()."</td><td>".$lat."</td></tr>\n";

            echo "<tr style=\"background-color: rgb(255, 200, 0)\">";
            echo "<td>Elevation</td><td>".$oldObject->getGroundElevation()."</td><td>".$elev."</td></tr>\n";
            echo "<tr";
            if ($oldObject->getElevationOffset() != $elevoffset) {
                echo " style=\"background-color: rgb(255, 200, 0)\"";
            }
            echo "><td>Elevation offset</td><td>".$oldObject->getElevationOffset()."</td><td>".$elevoffset."</td></tr>\n";
            echo "<tr";
            if ($oldObject->getOrientation() != $orientation) {
                echo " style=\"background-color: rgb(255, 200, 0)\"";
            }
            echo "><td>Heading (STG)</td><td>".heading_true_to_stg($oldObject->getOrientation())." (STG) - ".$oldObject->getOrientation()."(true)</td><td>".heading_true_to_stg($orientation)." (STG) - ".$orientation." (true)</td></tr>\n";
            echo "<tr";
            if ($oldModelMD->getId() != $newModelMD->getId()) {
                echo " style=\"background-color: rgb(255, 200, 0)\"";
            }
            echo "><td>Object's model</td><td>".$oldModelMD->getName()."</td><td>".$newModelMD->getName()."</td></tr>\n";
            echo "<tr><td>Map</td><td><object data=\"http://mapserver.flightgear.org/popmap/?lon=".$oldObject->getLongitude()."&amp;lat=".$oldObject->getLatitude()."&amp;zoom=14\" type=\"text/html\" width=\"100%\" height=\"240\"></object></td><td><object data=\"http://mapserver.flightgear.org/popmap/?lon=".$lon."&amp;lat=".$lat."&amp;zoom=14\" type=\"text/html\" width=\"100%\" height=\"240\"></object></td></tr>\n" .
                 "</tr>\n";
        }
?>

        <tr>
            <td colspan="8" class="submit">
                <?php echo "<a href=\"submission.php?action=accept&amp;sig=".$_GET["sig"]."&amp;email=".$_GET["email"]."\" />Accept</a> | ";?>
                <?php echo "<a href=\"submission.php?action=reject&amp;sig=".$_GET["sig"]."&amp;email=".$_GET["email"]."\" />Reject</a>";?>
            </td>
        </tr>
        </table>
<?php
        include '../../inc/footer.php';
        exit;
    }
}

// Check the presence of "action", the presence of "signature", its length (64) and its content.
if ($_GET["action"] == 'accept') {
    $resource_rw = connect_sphere_rw();

    // If connection is OK
    if ($resource_rw != '0') {

    // Checking the presence of sig into the database
        $result = pg_query($resource_rw,"SELECT spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';");
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
            echo "Signature found.<br /> Now processing query with request number ". $_GET[sig].".</p><br />";
            echo "<p class=\"center warning\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

            // Closing the rw connection.
            include '../../inc/footer.php';
            pg_close($resource_rw);
            exit;
        }

        $page_title = "Automated Objects Pending Requests Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">Signature found.<br /> Now processing INSERT or DELETE or UPDATE position query with number ". $_GET[sig].".</p><br />";
        echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

        // Delete the entry from the pending query table.
        $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';";
        $resultdel = pg_query($resource_rw,$delete_request);

        if(!resultdel) {
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
        $comment = $_GET['maintainer_comment'];

        // email destination
        $to = (isset($_GET['email'])) ? $_GET['email'] : '';

        $sig = $_GET['sig'];

        $emailSubmit = EmailContentFactory::getPendingRequestProcessConfirmationEmailContent($sig);
        $emailSubmit->sendEmail($to, true);

        exit;
    }
}

// If it's not to validate the submission... it's to delete it... check the presence of "action", the presence of "signature", its length (64), its content.
else if ($_GET["action"] == "reject") {
    $resource_rw = connect_sphere_rw();

    // If connection is OK
    if ($resource_rw != '0') {

        // Checking the presence of sig into the database
        $delete_query = "SELECT 1 FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';";
        $result = pg_query($delete_query);

        // If not ok...

        if (pg_num_rows($result) != 1) {
            $page_title = "Automated Objects Pending Requests Form";
            $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?";
            $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
            include '../../inc/error_page.php';

            // Closing the rw connection.
            pg_close($resource_rw);
            exit;
        }

        // Delete the entry from the pending query table.
        $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';";
        $resultdel = pg_query($resource_rw, $delete_request);

        if (!$resultdel) {
            $page_title = "Automated Objects Pending Requests Form";
            include '../../inc/header.php';
            echo "<p class=\"center\">\n".
                 "Signature found.<br /> Now deleting request with number ". $_GET["sig"].".</p>".
                 "<p class=\"center warning\">Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br/>\n";

            // Closing the rw connection.
            include '../../inc/footer.php';
            pg_close($resource_rw);
            exit;
        }

        $page_title = "Automated Objects Pending Requests Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">";
        echo "Signature found.<br />Now deleting request with number ". $_GET["sig"].".</p>";
        echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.";
        echo "</p>";

        // Closing the rw connection.
        include '../../inc/footer.php';
        pg_close($resource_rw);

        // Sending mail if entry was correctly deleted.
        // Sets the time to UTC.

        date_default_timezone_set('UTC');
        $dtg = date('l jS \of F Y h:i:s A');
        $comment = $_GET['maintainer_comment'];

        // email destination
        $to = (isset($_GET['email'])) ? $_GET['email'] : '';

        $sig = $_GET['sig'];

        $emailSubmit = EmailContentFactory::getRejectAndDeletionConfirmationEmailContent($sig, $comment);
        $emailSubmit->sendEmail($to, true);

        exit;
    }
}
// Sending the visitor elsewhere if he has no idea what he's doing here.
else {
    header("Location: /submission/object/");
}
?>