<?php
require_once "../../classes/DAOFactory.php";
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
require_once '../../inc/email.php';

// Checking all variables
if (isset($_POST['new_long']) && is_longitude($_POST['new_long']))
    $new_long = pg_escape_string($_POST['new_long']);

if (isset($_POST['new_lat']) && is_latitude($_POST['new_lat']))
    $new_lat = pg_escape_string($_POST['new_lat']);

if (isset($_POST['new_offset']) && is_offset($_POST['new_offset'])) {
    $new_offset = pg_escape_string($_POST['new_offset']);
    // Have to put quotes around NULL, else we're gonna have problems with the SQL query.
    if ($new_offset == '' || $new_offset == 0) $new_offset = 'NULL';
}

if (isset($_POST['new_heading']) && is_heading($_POST['new_heading']))
    $new_orientation = pg_escape_string($_POST['new_heading']);

if (isset($_POST['id_to_update']) && is_object_id($_POST['id_to_update']))
    $id_to_update = pg_escape_string($_POST['id_to_update']);

if (isset($_REQUEST['update_choice']) && is_object_id($_REQUEST['update_choice']))
    $id_to_update = pg_escape_string(stripslashes($_REQUEST['update_choice']));

if (isset($_POST['model_name']) && is_model_id($_POST['model_name']))
    $model_name = pg_escape_string($_POST['model_name']);

if (isset($_POST['email']) && is_email($_POST['email']))
    $safe_email = pg_escape_string(stripslashes($_POST['email']));

if (isset($_POST['new_ob_text'])
    && (strlen($_POST['new_ob_text']) > 0)
    && (strlen($_POST['new_ob_text']) <= 100)) {
    // && preg_match($regex['obtext'], $_POST['new_ob_text']) )
    $safe_new_ob_text = pg_escape_string(stripslashes($_POST['new_ob_text']));
}

// Final step to edition
if (isset($model_name)
    && isset($new_long)
    && isset($new_lat)
    // Have to keep the NULL between quotes else we'll have a parse error of the SQL INSERT request
    && (isset($new_offset) || $new_offset == 'NULL')
    && isset($new_orientation)
    && isset($safe_new_ob_text)) {

    // Captcha stuff
    require_once '../../inc/captcha/recaptchalib.php';

    // Private key is needed for the server-to-Google auth.
    $privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
    $resp = recaptcha_check_answer ($privatekey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);

    // What happens when the CAPTCHA was entered incorrectly
    if (!$resp->is_valid) {
        $page_title = "Automated Objects Update Form";
        $error_text = "<br />Sorry but the reCAPTCHA wasn't entered correctly.".
                      " <a href='javascript:history.go(-1)'>Go back and try it again</a>" .
                      "<br />(reCAPTCHA complained: " . $resp->error . ")<br />" .
                      "Don't forget to feed the Captcha, it's a mandatory item as well.".
                      " Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
        include '../../inc/error_page.php';
        exit;
    }

    // Talking back to submitter.
    $page_title = "Automated Objects Update Form";
    include '../../inc/header.php';

    // Checking that email is valid (if it exists).
    $failed_mail = false;
    if ($safe_email) {
        echo "<p class=\"center ok\">Email: ".$safe_email."</p><br />";
    }
    else {
        echo "<p class=\"center warning\">No email was given (not mandatory) or email mismatch!</p><br />";
        $failed_mail = true;
    }

    // Preparing the update request: the quotes around NULL put above were tested OK.
    $query_update="UPDATE fgs_objects ".
                  "SET ob_text=$$".$safe_new_ob_text."$$, wkb_geometry=ST_PointFromText('POINT(".$new_long." ".$new_lat.")', 4326), ob_gndelev=-9999, ob_elevoffset=".$new_offset.", ob_heading=".heading_stg_to_true($new_orientation).", ob_model=".$model_name.", ob_group=1 ".
                  "WHERE ob_id=".$id_to_update.";";

    // Generating the SHA-256 hash based on the data we've received + microtime (ms) + IP + request. Should hopefully be enough ;-)
    $sha_to_compute = "<".microtime()."><".$_POST['IPAddr']."><".$query_update.">";
    $sha_hash = hash('sha256', $sha_to_compute);

    // Zipping the Base64'd request.
    $zipped_base64_update_query = gzcompress($query_update,8);

    // Coding in Base64.
    $base64_update_query = base64_encode($zipped_base64_update_query);

    // Opening database connection...
    $resource_rw = connect_sphere_rw();

    // Sending the request...
    $query_rw_pending_request = "INSERT INTO fgs_position_requests (spr_hash, spr_base64_sqlz) VALUES ('".$sha_hash."', '".$base64_update_query."');";
    $resultrw = pg_query($resource_rw, $query_rw_pending_request);

    // Closing the connection.
    pg_close($resource_rw);

    if (!$resultrw) {
        echo "<p class=\"center\">Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.<br /></p>";
        include '../../inc/footer.php';
        exit;
    }

    echo "<p class=\"center\">Your update request has been successfully queued into the FG scenery update requests!<br />";
    echo "Unless it's rejected, the object should be updated in Terrasync within a few days.<br />";
    echo "The FG community would like to thank you for your contribution!<br />";
    echo "Want to update, delete or submit another position?<br /> <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/\">Click here to go back to the submission page.</a></p>";

    // Sending mail if there is no false and SQL was correctly inserted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');

    // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
    $ipaddr = pg_escape_string(stripslashes($_POST['IPAddr']));
    $host = gethostbyaddr($ipaddr);
    $family_url = "http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$family_id;
    $object_url = "http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$model_id;
    $html_family_url = htmlspecialchars($family_url);
    $html_object_url = htmlspecialchars($object_url);
    $family_name = $_POST['family_name'];
    $comment = $_POST['comment'];

    email("shared_update_request_pending");

    // Mailing the submitter to tell him that his submission has been sent for validation.
    if (!$failed_mail) {
        $to = $safe_email;

        // Correctly set the object URL.
        $family_url = "http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$family_id;
        $object_url = "http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$model_id;
        $html_family_url = htmlspecialchars($family_url);
        $html_object_url = htmlspecialchars($object_url);
        $family_name = $_POST['family_name'];
        $comment = $_POST['comment'];

        email("shared_update_request_sent_for_validation");
    }
    include '../../inc/footer.php';
    exit;
}

// Getting back the update_choice
if (isset($id_to_update)) {
    $page_title = "Automated Object Update Form";
    $body_onload = "update_objects();";
    include '../../inc/header.php';

?>
<script src="/inc/js/update_objects.js" type ="text/javascript"></script>
<script src="/inc/js/check_form.js" type="text/javascript"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("update");

    if (!checkStringNotDefault(form["new_long"], "") || !checkNumeric(form["new_long"],-180,180) ||
        !checkStringNotDefault(form["new_lat"], "") || !checkNumeric(form["new_lat"],-90,90) ||
        !checkNumeric(form['new_offset'],-999,999) ||
        !checkStringNotDefault(form["new_heading"], "") || !checkNumeric(form['new_heading'],0,359.999) ||
        !checkStringNotDefault(form["comment"], "") || !checkComment(form['comment']) ||
        (form['email'].value!=="" && !checkEmail(form['email'])))
        return false;

}
/*]]>*/
</script>
    <p class="center">You have asked to update object <?php echo "<a href=\"/objectview.php?id=".$id_to_update."\">#".$id_to_update."</a>";?>.</p>

    <form id="update" method="post" action="check_update.php" onsubmit="return validateForm();">
      <table>
        <tr>
          <th></th>
          <th>Current value</th>
          <th>New value</th>
        </tr>
        <input type="hidden" name="id_to_update" value="<?php echo $id_to_update; ?>" />
        <tr>
          <td>
            <span title="This is the family name of the object you want to update."><label for="family_name">Object's family<em>*</em></label></span>
          </td>
          <td>
            <?php $actual_family = get_object_family_from_id($id_to_update); echo $actual_family; ?>
          </td>
          <td>
<?php

    if (is_shared($id_to_update)) {
        $resource_r = connect_sphere_r();

        // If connection is OK
        $id_family = 0;
        if ($resource_r != '0') {
            // Show all the families other than the static family
            $result = pg_query("SELECT mg_id, mg_name FROM fgs_modelgroups WHERE mg_id!='0' ORDER BY mg_name;");

            // Start the select form
            echo "<select id=\"family_name\" name=\"family_name\" onchange=\"update_objects();\">\n";
            while ($row = pg_fetch_assoc($result)) {
                $name = preg_replace('/ /',"&nbsp;",$row["mg_name"]);
                if ($actual_family == $row["mg_name"]) {
                    $id_family = $row["mg_id"];
                    echo "<option selected=\"selected\" value=\"".$row["mg_id"]."\">".$name."</option>\n";
                }
                else {
                    echo "<option value=\"".$row["mg_id"]."\">".$name."</option>\n";
                }
            }
            echo "</select>";

            // Close the database resource
            pg_close($resource_r);
        }

        // Else, write message.
        else {
            echo "<br /><p class=\"center warning\">Sorry but the database is currently unavailable, please come again soon.</p>";
        }
    }
    else {
        $id_family = 1;
        echo "Static";
        echo "      <input name=\"family_name\" type=\"hidden\" value=\"0\"></input>";
    }
?>
          </td>
        </tr>
        <tr>
          <td>
            <span title="This is the model name of the object you want to update, ie the name as it's supposed to appear in the .stg file.">
            <label for="model_name">Model name<em>*</em></label></span>
          </td>
          <td>
<?php
    $actual_model_name = object_name(get_object_model_from_id($id_to_update));
    echo $actual_model_name;
?>
          </td>
          <td>
<?php

    if (is_shared($id_to_update)) {

        echo "<div id=\"form_objects\">";
        echo "    <select name='model_name' id='model_name' onchange='change_thumb()'>";

        // Querying when the family is updated.
        $resource_r = connect_sphere_r();

        if ($resource_r != '0') {
            $query = "SELECT mo_id, mo_path, mo_name, mo_shared FROM fgs_models WHERE mo_shared=".$id_family." ORDER BY mo_path;";
            $result = pg_query($query);

            // Showing the results.
            while ($row = pg_fetch_assoc($result)) {
                $id   = $row["mo_id"];
                $name = preg_replace('/ /',"&nbsp;",$row["mo_path"]);

                if ($actual_model_name == $row["mo_name"]) {
                    echo "<option selected=\"selected\" value='".$id."'>".$name."</option>\n";
                } else {
                    echo "<option value='".$id."'>".$name."</option>\n";
                }
            }

            // Close the database resource
            pg_close($resource_r);
            echo "</select>\n";
            echo "</div>\n";
        }
    }
    else {
        echo "      <input name=\"model_name\" type=\"hidden\" value=\"".get_object_model_from_id($id_to_update)."\"></input>";
        echo $actual_model_name;
    }
?>
          </td>
        </tr>
        <tr>
          <td>
            <span title="This is the WGS84 longitude of the object you want to update. Has to be between -180.000000 and +180.000000.">
            <label for="new_long">Longitude<em>*</em></label></span>
          </td>
          <td>
            <?php $actual_long = get_object_longitude_from_id($id_to_update); echo $actual_long; ?>
          </td>
          <td>
            <input type="text" name="new_long" id="new_long" maxlength="13" value="<?php echo $actual_long; ?>" onchange="update_map('new_long','new_lat');" onkeyup="checkNumeric(this,-180,180);" />
          </td>
        </tr>
        <tr>
          <td>
            <span title="This is the WGS84 latitude of the object you want to update. Has to be between -90.000000 and +90.000000.">
            <label for="new_lat">Latitude<em>*</em></label></span>
          </td>
          <td>
            <?php $actual_lat = get_object_latitude_from_id($id_to_update); echo $actual_lat; ?>
          </td>
          <td>
            <input type="text" name="new_lat" id="new_lat" maxlength="13" value="<?php echo $actual_lat; ?>" onchange="update_map('new_long','new_lat');" onkeyup="checkNumeric(this,-90,90);" />
          </td>
        </tr>
        <tr>
            <td>
                <span title="This is the country of the object you want to update. Not editable, though, cause automatic procedures are doing it.">
                <label for="country">Country</label></span>
            </td>
            <td colspan="2">
<?php
        $country = get_country_name_from_country_code(get_object_country_from_id($id_to_update));
        echo ($country == '')?"Unknown!":$country;
?>
            </td>
        </tr>
        <tr>
          <td>
            <span title="This is the ground elevation (in meters) where the object you want to update is located. Warning: if your model is sunk into the ground, the Elevation offset field is set below.">
            <label for="new_gndelev">Ground elevation (will be recalculated)</label></span>
          </td>
          <td>
            <?php $actual_elevation = get_object_elevation_from_id($id_to_update); echo $actual_elevation; ?>
          </td>
          <td>
            <input type="text" name="new_gndelev" id="new_gndelev" maxlength="10" value="<?php echo $actual_elevation; ?>" readonly="readonly" />
          </td>
        </tr>
        <tr>
          <td>
            <span title="This is the vertical offset (in meters) between your model 'zero' (usually the bottom) and the terrain elevation at the specified coordinates. Use negative numbers to sink it into the ground, positive numbers to make it float, or 0 if there's no offset.">
            <label for="new_offset">Elevation Offset<em>*</em></label> (see <a href="../../contribute.php#offset">here</a> for more help)</span>
          </td>
          <td>
            <?php $actual_offset = get_object_offset_from_id($id_to_update); echo $actual_offset; ?>
          </td>
          <td>
            <input type="text" name="new_offset" id="new_offset" maxlength="10" value="<?php echo $actual_offset; ?>" onkeyup="checkNumeric(this,-10000,10000);" />
          </td>
        </tr>
        <tr>
          <td>
            <span title="The orientation of the object you want to update - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label for="new_heading">Orientation<em>*</em></label></span>
          </td>
          <td>
            <?php $actual_orientation = heading_true_to_stg(get_object_true_orientation_from_id($id_to_update)); echo $actual_orientation; ?>
          </td>
          <td>
            <input type="text" name="new_heading" id="new_heading" maxlength="7" value="<?php echo $actual_orientation; ?>" onkeyup="checkNumeric(this,0,359.999);" />
          </td>
        </tr>
        <tr>
            <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description (Just for test now - don't use)</label></span></td>
            <td><?php $actual_ob_text = get_object_text_from_id($id_to_update); echo $actual_ob_text; ?></td>
            <td>
                <input type="text" name="new_ob_text" id="new_ob_text" size="50" maxlength="100" value="<?php echo $actual_ob_text; ?>" onkeyup="checkComment(this);" />
            </td>
        </tr>
        <tr>
            <td><span title="This is the picture of the object you want to update"><label>Picture</label></span></td>
            <td><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php $model_id = get_object_model_from_id($id_to_update); echo $model_id; ?>" alt="Actual thumbnail"/></td>
            <td><img id="form_objects_thumb" src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $model_id; ?>" alt="New thumbnail"/></td>
        </tr>
        <tr>
            <td><span title="This is the map around the object you want to update"><label>Map</label></span></td>
            <td><object data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $actual_long; ?>&amp;lat=<?php echo $actual_lat; ?>&amp;zoom=14" type="text/html" width="100%" height="225"></object></td>
            <td><object id="map" data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $actual_long; ?>&amp;lat=<?php echo $actual_lat; ?>&amp;zoom=14" type="text/html" width="100%" height="225"></object></td>
        </tr>
        <tr>
          <td><span title="Please add a short (max 100 letters) statement why you are updating this data. This will help the maintainers understand what you are doing. eg: this model was misplaced, so I'm updating it. Only alphanumerical, colon, semi colon, question and exclamation mark, arobace, minus, underscore, antislash and point are granted.">
            <label for="comment">Comment<em>*</em></label></span>
          </td>
          <td colspan="2">
            <input type="text" name="comment" id="comment" maxlength="100" size="100" value="" onkeyup="checkComment(this)"/>
          </td>
        </tr>
        <tr>
          <td><span title="Please leave YOUR VALID email address over here. This will help you be informed of your submission process. EXPERIMENTAL">
            <label for="email">Email address</label></span>
          </td>
          <td colspan="2">
            <input type="text" name="email" id="email" maxlength="50" size="50" value="" onkeyup="checkEmail(this);"/>
          </td>
        </tr>
        <tr>
          <td colspan="3" class="submit">
<?php
    // Google Captcha stuff
    require_once '../../inc/captcha/recaptchalib.php';
    $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
    echo recaptcha_get_html($publickey);
?>
            <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']?>" />
            <input type="submit" name="submit" value="Update this object!" />
            <input type="button" name="cancel" value="Cancel - Do not update!" onclick="history.go(-1)"/>
          </td>
        </tr>
      </table>
    </form>
<?php
    include '../../inc/footer.php';
}
else {


?>
<br />
<?php
    global $error;
    $error = false;

    // Checking that latitude exists and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.
    $error_text = "";
    if (is_latitude($_POST['latitude'])) {
        $lat = number_format(pg_escape_string(stripslashes($_POST['latitude'])),7,'.','');
    }
    else {
        $error_text .= "Latitude mismatch!<br/>";
        $error = true;
    }

    // Checking that longitude exists and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
    if (is_longitude($_POST['longitude'])) {
        $long = number_format(pg_escape_string(stripslashes($_POST['longitude'])),7,'.','');
    }
    else {
        $error_text .= "Longitude mismatch!<br/>";
        $error = true;
    }


    if ($error) {
        $page_title = "Automated Objects Update Form";
        // $error_text is defined above
        include '../../inc/error_page.php';
        exit;
    }

    // If there is no error, generating SQL to check for object.

    // Opening database connection...
    $resource_r_update = connect_sphere_r();

    // Let's see in the database if something exists at this position
    $query_pos = "SELECT ob_id, to_char(ob_modified,'YYYY-mm-dd (HH24:MI)') AS ob_datedisplay, ob_gndelev, ob_elevoffset, ob_heading, ob_model FROM fgs_objects WHERE wkb_geometry = ST_PointFromText('POINT(".$long." ".$lat.")', 4326);";
    $result = pg_query ($resource_r_update, $query_pos);
    $returned_rows = pg_num_rows ($result);

    if ($returned_rows == 0) {
        $page_title  = "Automated Objects Update Form";
        $error_text  = "Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.".";
        $advise_text = "Please <a href='javascript:history.go(-1)'>go back and check your position</a> (see in the relevant STG file).";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Objects Update Form";
    include '../../inc/header.php';

    if ($returned_rows == 1) { // If we have just one answer...
        while ($row = pg_fetch_row($result)) {
            echo "<p class=\"center\">One object (#".$row[0].") with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." has been found in the database.</p>";
?>
            <form id="update_position" method="post" action="check_update.php">
            <table>
                <tr>
                    <td><span title="This is the family name of the object you want to update."><label>Object's family</label></span></td>
                    <td colspan="4"><?php $family_name = get_object_family_from_id($row[0]); echo $family_name; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the model name of the object you want to update, ie the name as it's supposed to appear in the .stg file."><label>Model name</label></span></td>
                    <td colspan="4"><?php $real_name = object_name($row[5]); echo $real_name; ?></td>
                    <input name="model_id" type="hidden" value="<?php echo $row[5]; ?>" />
                </tr>
                <tr>
                    <td><span title="This is the last update or submission date/time of the corresponding object.">
                    <label>Date/Time of last update</label></span></td>
                    <td colspan="4"><?php echo $row[1]; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the ground elevation (in meters) of the position where the object you want to update is located. Warning : if your model is sunk into the ground, the Elevation offset field is set below."><label>Elevation</label></span></td>
                    <td colspan="4"><?php $actual_elevation = get_object_elevation_from_id($row[0]); echo $actual_elevation; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><label>Elevation Offset</label></span> (see <a href="../../contribute.php#offset">here</a> for more help)</td>
                    <td colspan="4"><?php $actual_offset = get_object_offset_from_id($row[0]); echo $actual_offset; ?></td>
                </tr>
                <tr>
                    <td><span title="The orientation of the object you want to update - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label>Orientation</label></span></td>
                    <td colspan="4"><?php $actual_orientation = heading_true_to_stg(get_object_true_orientation_from_id($row[0])); echo $actual_orientation; ?></td>
                </tr>
                <tr>
                    <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description</label></span></td>
                    <td colspan="4"><?php $ob_text = get_object_text_from_id($row[0]); echo $ob_text; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the picture of the object you want to update"><a style="cursor: help; ">Picture</a></span></td>
                    <td><a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelview.php?id=<?php echo $row[5]; ?>"><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $row[5]; ?>"></a></td>
                    <td><span title="This is the map around the object you want to update"><a style="cursor: help; ">Map</a></span></td>
                    <td>
                    <object data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $long; ?>&amp;lat=<?php echo $lat; ?>&amp;zoom=14" type="text/html" width="300" height="225"></object>
                    </td>
                </tr>
                <input name="update_choice" type="hidden" value="<?php echo $row[0]; ?>" />
                <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" />
                <input name="comment" type="hidden" value="<?php echo $_POST['comment']; ?>" />
                <tr>
                    <td colspan="4" class="submit">
                    <input type="submit" name="submit" value="I want to update this object!" />
                    <input type="button" name="cancel" value="Cancel, I made a mistake!" onclick="history.go(-1)"/>
                    </td>
                </tr>
            </table>
            </form>
<?php
            include '../../inc/footer.php';
        }
        exit;
    }

    // If we have more than one, the user has to choose...
    if ($returned_rows > 1) {
        echo "<p class=\"center\">".$returned_rows." objects with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." were found in the database.<br />Please select with the left radio button the one you want to update.</p>";

        // Starting multi-solutions form
        echo "<form id=\"update_position\" method=\"post\" action=\"check_update.php\">";
        echo "<table>";

        $i = 1; // Just used to put the selected button on the first entry
        while ($row = pg_fetch_row($result)) {
?>
                <tr>
                    <td colspan="5" background="white"><center><b>Object number #<?php echo $row[0]; ?></b></center>
                    </td>
                </tr>
                <tr>
                    <th rowspan="7">
                        <input type="radio" name="update_choice" value="<?php echo $row[0];?>" <?php echo ($i==1)?"checked=\"checked\"":""; ?> />
                    </th>
                    <td><span title="This is the family name of the object you want to update."><label>Object's family</label></span></td>
                    <td colspan="4"><?php $family_name = get_object_family_from_id($row[0]); echo $family_name; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the model name of the object you want to update, ie the name as it's supposed to appear in the .stg file.">
                    <label>Model name</label></span></td>
                    <td colspan="4"><?php $real_name = object_name($row[5]); echo $real_name; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the last update or submission date/time of the corresponding object.">
                    <label>Date/Time of last update</label></span></td>
                    <td colspan="4"><?php echo $row[1]; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the ground elevation (in meters) of the position where the object you want to update is located. Warning : if your model is sunk into the ground, the Elevation offset field is set below.">
                    <label>Elevation</label></span></td>
                    <td colspan="4"><?php $actual_elevation = get_object_elevation_from_id($row[0]); echo $actual_elevation; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><label>Elevation Offset</label></span></td>
                    <td colspan="4"><?php $actual_offset = get_object_offset_from_id($row[0]); echo $actual_offset; ?></td>
                </tr>
                <tr>
                    <td><span title="The orientation of the object you want to update - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label>Orientation</label></span></td>
                    <td colspan="4"><?php $actual_orientation = heading_true_to_stg(get_object_true_orientation_from_id($row[0])); echo $actual_orientation; ?></td>
                </tr>
                <tr>
                    <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description</label></span></td>
                    <td colspan="4"><?php $ob_text = get_object_text_from_id($row[0]); echo $ob_text; ?></td>
                </tr>
                <tr>
                    <td><span title="This is the picture of the object you want to update"><label>Picture</label></span></td>
                    <td><a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelview.php?id=<?php echo $row[5]; ?>"><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $row[5]; ?>" alt="Thumbnail"/></a></td>
                    <td><span title="This is the map around the object you want to update"><a style="cursor: help; ">Map</a></span></td>
                    <td>
                    <object data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $long; ?>&amp;lat=<?php echo $lat; ?>&amp;zoom=14" type="text/html" width="300" height="225"></object>
                    </td>
                </tr>
<?php
            $i++;
        }
?>
                <tr>
                    <td colspan="5" class="submit">
                    <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" />
                    <input name="comment" type="hidden" value="<?php echo $_POST['comment']; ?>" />
                    <input type="submit" name="submit" value="I want to update the selected object!" />
                    <input type="button" name="cancel" value="Cancel - I made a mistake!" onclick="history.go(-1)"/>
                    </td>
                </tr>
            </table>
            </form>
<?php
        exit;
    }
    include '../../inc/footer.php';
}
?>
