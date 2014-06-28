<?php
require_once "../../classes/DAOFactory.php";
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();
$requestDaoRO = DAOFactory::getInstance()->getRequestDaoRO();

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
require_once '../../classes/EmailContentFactory.php';

// Check the presence of "action", the presence of "signature", its 
// length (64) and its content.
if (isset($_GET["action"]) && is_sig($_GET["sig"]) && $_GET["action"] == "check") {
    $resource_rw = connect_sphere_rw();

    // If connection is OK
    if ($resource_rw != '0') {

        // Checking the presence of sig into the database
        $request = $requestDaoRO->getRequest($_GET["sig"]);
        if (!$request) {
            $page_title = "Automated Objects Massive Import Request Form";
            $error_text = "Sorry but the request you are asking for does " .
                          "not exist into the database. Maybe it has " .
                          "already been validated by someone else?<br/>";
            $advise_text = "Else, please report to devel ML or FG Scenery forum.";
            include '../../inc/error_page.php';
            pg_close($resource_rw);
            exit;
        }

        $page_title = "Automated Objects Massive Import Requests Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $_GET["sig"].".\n</p>\n";

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
                <?php echo "<input type=\"hidden\" name=\"hsig\" value=\"".$_GET["sig"]."\" />"; ?>
                <input type="submit" name="submit" value="Submit the mass import!" />
                <input type="submit" name="cancel" value="Reject - Do not import!" />
            </td>
        </tr>
        </table>
<?php
        include '../../inc/footer.php';
    }
}

// Managing the cancellation of a mass import by DB maintainer.
if (isset($_POST["cancel"]) && is_sig($_POST["hsig"]) && ($_POST["cancel"] == "Reject - Do not import!")) {

     $resource_rw = connect_sphere_rw();

    // If connection is OK
    if ($resource_rw != 0) {

        // Checking the presence of sig into the database
        $delete_query = "SELECT 1 FROM fgs_position_requests WHERE spr_hash = '". $_POST["hsig"] ."';";
        $result = pg_query($delete_query);

        // If not ok...
        if (pg_num_rows($result) != 1) {
            $page_title = "Automated Objects Massive Import Request Form";
            $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?<br/>";
            $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
            include '../../inc/error_page.php';
            pg_close($resource_rw);
            exit;
        }
        else {
            // Delete the entry from the pending query table.
            $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["hsig"] ."';";
            $resultdel = pg_query($resource_rw,$delete_request);

            if(!$resultdel) {
                $page_title = "Automated Objects Massive Import Request Form";
                $process_text = "Signature found.<br /> Now deleting request with number ". $_POST["hsig"].".";
                $error_text = "Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.";
                include '../../inc/error_page.php';

                // Closing the rw connection.
                pg_close($resource_rw);
                exit;
            }

            $page_title = "Automated Objects Massive Import Request Form";
            include '../../inc/header.php';
            echo "<center>Signature found.<br />Now deleting request with number ". $_POST["hsig"].".</center><br />";
            echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.</p>";

            // Closing the rw connection.
            pg_close($resource_rw);

            // Sending mail if entry was correctly deleted.
            // Sets the time to UTC.
            date_default_timezone_set('UTC');
            $dtg = date('l jS \of F Y h:i:s A');
            $comment = $_POST["maintainer_comment"];
            $hsig = $_POST["hsig"];

            // email destination
            $to = (isset($_POST['email'])) ? $_POST["email"] : '';

            $emailSubmit = EmailContentFactory::getMassImportRequestRejectedEmailContent($dtg, $hsig, $comment);
            $emailSubmit->sendEmail($to, true);

            include '../../inc/footer.php';
            exit;
        }
    }
}

// Now managing the insertion
if (isset($_POST["submit"]) && is_sig($_POST["hsig"]) && $_POST["submit"] == "Submit the mass import!") {

    $resource_rw = connect_sphere_rw();

    // If connection is OK
    if ($resource_rw != 0) {

        // Checking the presence of sig into the database
        $result = pg_query($resource_rw,"SELECT spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_POST["hsig"] ."';");
        if (pg_num_rows($result) != 1) {
            $page_title = "Automated Objects Massive Import Request Form";
            $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
            $advise_text = "Else, please report to devel ML or FG Scenery forum";
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

        // ####################################################################################################################################################
        // We have to work on the ob_text field here (working on it before make ob_text hard to parse to show to the maintainer).
        // Sorry, this means we have to explode the request once more and, line per line, find the model name and set ob_text='".object_name($model_name)."'
        // and rebuild the query, taking care of the presence of " or , in the ob_text field (while I did not do it in the single addition.
        // ####################################################################################################################################################

        $trigged_query_rw = str_replace("INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_country, ob_group)","",$query_rw); // Removing the start of the query from the data;
        $tab_tags = explode(", (",$trigged_query_rw); // Separating the data based on the ST_PointFromText existence
        $i = 1;
        foreach ($tab_tags as $value_tag) {
            $pattern = "/'', ST_PointFromText\('POINT\((?P<long>[0-9.-]+) (?P<lat>[0-9.-]+)\)', 4326\), (?P<elev>[0-9.-]+), (?P<elevoffset>[0-9.-]+), (?P<orientation>[0-9.-]+), (?P<model_id>[0-9]+), '(?P<country>[a-z]+)', 1\)/";

            preg_match($pattern, $value_tag, $matches);

            $long = $matches['long'];
            $lat = $matches['lat'];
            $elevation = $matches['elev'];
            $country = $matches['country'];
            $elevoffset = $matches['elevoffset'];
            $orientation = $matches['orientation'];
            $model = $matches['model_id'];
            $modelMD = $modelDaoRO->getModelMetadata($model);
            $ob_text = $modelMD->getName();

            // Avoiding "0" data to be inserted for ob_elevoffset. Should be NULL. This avoids later computation delays on exports
            $data_rw[$i] = "('".pg_escape_string($ob_text)."', ST_PointFromText('POINT(".$long." ".$lat.")', 4326), ".$elevation.", ";

            $data_rw[$i] .= ($elevoffset == 0)?"NULL":$elevoffset;
            $data_rw[$i] .= ", ".$orientation.", ".$model.", ";
            $data_rw[$i] .= ($country == "unknown")?"NULL":"'".$country."'";
            $data_rw[$i] .= ", 1)";

            $i++;
        }

        $query_rw = "INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_country, ob_group) VALUES ";
        $data_query_rw = "";
        for ($j = 1; $j<$i; $j++) { // For each line, add the data content to the request
            if ($j == ($i-1)) {
                $data_query_rw = $data_query_rw.$data_rw[$j].";";
            }
            else {
                $data_query_rw = $data_query_rw.$data_rw[$j].", ";
            }
        }
        $mass_rw_query = $query_rw.$data_query_rw;

        // ###########################################################################################################################

        // Sending the request...
        $result_rw = pg_query($resource_rw, $mass_rw_query);

        if (!$result_rw) {
            $page_title = "Automated Objects Massive Insertion Request Form";
            include '../../inc/header.php';
            echo "<p class=\"center\">Signature found.<br /> Now processing query with request number ". $_POST["hsig"].".</p><br />";
            echo "<p class=\"warning\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

            // Closing the rw connection.
            include '../../inc/footer.php';
            pg_close($resource_rw);
            exit;
        }

        $page_title = "Automated Objects Massive Insertion Request Form";
        include '../../inc/header.php';
        echo "<p class=\"center\">Signature found.<br /> Now processing INSERT or DELETE or UPDATE position query with number ". $_POST["hsig"].".</p><br />\n";
        echo "<p class=\"center ok\">".pg_affected_rows($result_rw)." objects were added to the database!</p>\n";
        echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

        // Delete the entry from the pending query table.
        $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_POST["hsig"] ."';";
        $resultdel = pg_query($resource_rw, $delete_request);

        if (!$resultdel) {
            echo "<p class=\"warning\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

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
        $comment = $_POST["maintainer_comment"];
        $hsig = $_POST['hsig'];

        // email destination
        $to = (isset($_POST['email'])) ? $_POST["email"] : '';

        $emailSubmit = EmailContentFactory::getMassImportRequestAcceptedEmailContent($dtg, $hsig, $comment);
        $emailSubmit->sendEmail($to, true);

        include '../../inc/footer.php';
        exit;
    }
}
?>