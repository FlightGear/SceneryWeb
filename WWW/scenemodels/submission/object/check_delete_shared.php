<?php

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/email.php';


// Checks all variables if exist
if (isset($_POST['step']) && preg_match('/^[0-9]+$/u',$_POST['step'])) {
    $step = $_POST['step'];
}

if (isset($_REQUEST['delete_choice']) && preg_match('/^[0-9]+$/u',$_REQUEST['delete_choice'])) {
    $id_to_delete = pg_escape_string(stripslashes($_REQUEST['delete_choice']));
}

if (isset($_POST['delete_choice']) && preg_match('/^[0-9]+$/u',$_POST['delete_choice'])) {
    $id_to_delete = pg_escape_string(stripslashes($_POST['delete_choice']));
}

if (isset($_POST['email'])
    && (strlen($_POST['email']) > 0)
    && (strlen($_POST['email']) <= 50)
    && preg_match('/^[0-9a-zA-Z_\-.]+@[0-9a-z_\-]+\.[0-9a-zA-Z_\-.]+$/u',$_POST['email']) ) {
    $safe_email = pg_escape_string(stripslashes($_POST['email']));
}

if (isset($_POST['comment']) && preg_match('/^[0-9a-z-A-Z\';:!?@-_\. ]+$/u',$_POST['comment'])) {
    $comment = strip_tags($_POST['comment']);
}

// Final step to deletion
if (isset($step) && ($step == 3) && isset($id_to_delete)) {

    // Captacha stuff
    require_once '../../inc/captcha/recaptchalib.php';

    // Private key is needed for the server-to-Google auth.
    $privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
    $resp = recaptcha_check_answer ($privatekey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);

    // What happens when the CAPTCHA was entered incorrectly
    if (!$resp->is_valid) {
        $page_title = "Automated Objects Deletion Form";
        $error_text = "Sorry but the reCAPTCHA wasn't entered correctly. <a href='javascript:history.go(-1)'>Go back and try it again</a>" .
             "<br />(reCAPTCHA complained: " . $resp->error . ")<br />" .
             "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
        include '../../inc/error_page.php';
        exit;
    }

    $page_title = "Automated Objects Deletion Form";
    include '../../inc/header.php';

    echo "<br /><p class=\"center ok\">You have asked to delete object #".$id_to_delete."</p>";

    // Should in fact be somewhere like here. Checking that comment exists. Just a small verification as it's not going into DB.
    $failed_mail = false;
    if (isset($safe_email)) {
        echo "<p class=\"center ok\">Email: ".$safe_email."</p>";
    }
    else {
        echo "<p class=\"center warning\">No email was given (not mandatory) or email mismatch!</p>";
        $failed_mail = true;
    }

    // Preparing the deletion request
    $query_delete = "DELETE FROM fgs_objects WHERE ob_id=".$id_to_delete.";";

    // Generating the SHA-256 hash based on the data we've received + microtime (ms) + IP + request. Should hopefully be enough ;-)
    $sha_to_compute = "<".microtime()."><".$_POST['IPAddr']."><".$query_delete.">";
    $sha_hash = hash('sha256', $sha_to_compute);

    // Zipping the Base64'd request.
    $zipped_base64_delete_query = gzcompress($query_delete,8);

    // Coding in Base64.
    $base64_delete_query = base64_encode($zipped_base64_delete_query);

    // Opening database connection...
    $resource_rw = connect_sphere_rw();

    // Sending the request...
    $query_rw_pending_request = "INSERT INTO fgs_position_requests (spr_hash, spr_base64_sqlz) VALUES ('".$sha_hash."', '".$base64_delete_query."');";
    $resultrw = @pg_query($resource_rw, $query_rw_pending_request);

    // Closing the connection.
    @pg_close($resource_rw);

    // Talking back to submitter.
    if (!$resultrw) {
        echo "<p class=\"center\">Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.</p><br />";
        include '../../inc/footer.php';
        exit;
    }
    echo "<p class=\"center\">Your object has been successfully queued into the deletion requests!<br />";
    echo "Unless it's rejected, the object should be dropped in Terrasync within a few days.<br />";
    echo "The FG community would like to thank you for your contribution!<br />";
    echo "Want to delete or submit another position ?<br /> <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/\">Click here to go back to the submission page.</a></p>";

    // Sending mail if there is no false and SQL was correctly inserted.
    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');

    // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
    $ipaddr = pg_escape_string(stripslashes($_POST['IPAddr']));
    $host   = gethostbyaddr($ipaddr);
    
    email("shared_delete_request_pending");

    // Mailing the submitter and tell him that his submission has been sent for validation.
    if (!$failed_mail) {
        $to = $safe_email;
        email("shared_delete_request_sent_for_validation");
    }
    include '../../inc/footer.php';
    exit;
}

// Checking DB availability before all
$ok = check_availability();

if (!$ok) {
    $page_title = "Automated Objects Deletion Form";
    $error_text = "Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.";
    include '../../inc/error_page.php';
    exit;
}

$error = false;
global $error;

// We can directly retrieve the object ID through the other forms, therefore no test is needed.
if (isset($_POST['delete_choice']) && preg_match('/^[0-9]+$/u',$_POST['delete_choice']))
    $id_to_delete = pg_escape_string($_POST['delete_choice']);

if (isset($_REQUEST['delete_choice'])
    && $_REQUEST['delete_choice']>'0'
    && preg_match('/^[0-9]+$/u',$_REQUEST['delete_choice']))
    $id_to_delete = pg_escape_string(stripslashes($_REQUEST['delete_choice']));

if (isset($id_to_delete)) {
    $error = false;
}
else {
    $error_text = "";

    // Checking that latitude exists, is of good length and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.
    if (isset($_POST['latitude'])
        && strlen($_POST['latitude']) <= 20
        && $_POST['latitude'] <= 90
        && $_POST['latitude'] >= -90
        && preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',$_POST['latitude'])) {
        $lat = number_format(pg_escape_string(stripslashes($_POST['latitude'])),7,'.','');
    }
    else {
        $error_text .= "Latitude mismatch!<br/>";
        $error = true;
    }

    // Checking that longitude exists, if of good length and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
    if (isset($_POST['longitude'])
        && strlen($_POST['longitude']) <= 20
        && $_POST['longitude'] <= 180
        && $_POST['longitude'] >= -180
        && preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',$_POST['longitude'])) {
        $long = number_format(pg_escape_string(stripslashes($_POST['longitude'])),7,'.','');
    }
    else {
        $error_text .= "Longitude mismatch!<br/>";
        $error = true;
    }
}

// If there is no error, generating SQL to be inserted into the database pending requests table.
if ($error) {
    $page_title = "Automated Objects Deletion Form";
    // $error_text is defined above
    include '../../inc/error_page.php';
    exit;
}

// Opening database connection...
$resource_r_deletion = connect_sphere_r();

// If the delete_choice is sent directly to us from a webform "outside" the submission world
if (isset($id_to_delete)) {
    // Let's grab the information about this object from the database
    $query_pos = "SELECT ob_id, to_char(ob_modified,'YYYY-mm-dd (HH24:MI)') AS ob_datedisplay, ob_group FROM fgs_objects WHERE ob_id = ".$id_to_delete.";";
    $result = @pg_query ($resource_r_deletion, $query_pos);
    $returned_rows = pg_num_rows ($result);
}
else {
    // Let's see in the database if something exists at this position
    $query_pos = "SELECT ob_id, to_char(ob_modified,'YYYY-mm-dd (HH24:MI)') AS ob_datedisplay, ob_group FROM fgs_objects WHERE wkb_geometry = ST_PointFromText('POINT(".$long." ".$lat.")', 4326);";
    $result = @pg_query ($resource_r_deletion, $query_pos);
    $returned_rows = pg_num_rows ($result);
}

// We have no result
if ($returned_rows == 0) {
    $page_title = "Automated Objects Deletion Form";
    $error_text = "Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.". Please <a href='javascript:history.go(-1)'>go back and check your position</a> (see in the relevant STG file).";
    include '../../inc/error_page.php';
    exit;
}

$page_title = "Automated Objects Deletion Form";
require '../../inc/header.php';

// We have only one result

if ($returned_rows == 1) {
    $row = pg_fetch_row($result);
    echo "<p class=\"center\">You have asked to delete object <a href=\"/objectview.php?id=".$row[0]."\">#".$row[0]."</a>.</p>";
?>
<script src="/inc/js/check_form.js" type="text/javascript"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("delete_position");

    if (!checkStringNotDefault(form["comment"], "") || !checkComment(form['comment']) ||
        (form['email'].value!="" && !checkEmail(form['email'])))
        return false;

}
/*]]>*/
</script>

    <ul class="warning">If you want to replace an object which is set as an "OBSTRUCTION" (see the object's family hereunder) by a 3D model, please consider adding the 3D model <b>first</b> - ie before deleting the shared object.</ul>

    <form id="delete_position" method="post" action="check_delete_shared.php" onsubmit="return validateForm();">
    <table>
        <tr>
            <td><span title="This is the family name of the object you want to delete."><label>Object's family</label></span></td>
            <td colspan="4"><?php $family_name = get_object_family_from_id($row[0]); echo $family_name; ?></td>
        </tr>
        <tr>
            <td><span title="This is the model name of the object you want to delete, ie the name as it's supposed to appear in the .stg file."><label>Model name</label></span></td>
            <td colspan="4"><?php $model_name = object_name(get_object_model_from_id($row[0]));  echo $model_name; ?></td>
        </tr>
        <tr>
            <td><span title="This is the WGS84 longitude of the object you want to update. Has to be between -180.000000 and +180.000000."><label>Longitude</label></span></td>
            <td colspan="4"><?php $longitude = get_object_longitude_from_id($row[0]); echo $longitude; ?></td>
        </tr>
        <tr>
            <td><span title="This is the WGS84 latitude of the object you want to update. Has to be between -90.000000 and +90.000000."><label>Latitude</label></span></td>
            <td colspan="4"><?php $latitude = get_object_latitude_from_id($row[0]); echo $latitude; ?></td>
        </tr>
        <tr>
            <td><span title="This is the last update or submission date/time of the corresponding object."><label>Date/Time of last update</label></span></td>
            <td colspan="4"><?php echo $row[1]; ?></td>
        </tr>
        <tr>
            <td><span title="This is the ground elevation (in meters) of the position where the object you want to delete is located. Warning: if your model is sunk into the ground, the Elevation offset field is set below."><label>Elevation</label></span></td>
            <td colspan="4"><?php $elevation = get_object_elevation_from_id($row[0]); echo $elevation; ?></td>
        </tr>
        <tr>
            <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><label>Elevation Offset</label></span></td>
            <td colspan="4"><?php $offset = get_object_offset_from_id($row[0]); echo $offset; ?></td>
        </tr>
        <tr>
            <td><span title="The orientation of the object you want to delete - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label>Orientation</label></span></td>
            <td colspan="4"><?php $orientation = heading_true_to_stg(get_object_true_orientation_from_id($row[0])); echo $orientation; ?></td>
        </tr>
        <tr>
            <td><span title="Object's family (obstruction, ...)."><label>Object's family</label></span></td>
            <td colspan="4"><?php $ob_text = get_group_name_from_id($row[2]); echo $ob_text; ?></td>
        </tr>
        <tr>
            <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description</label></span></td>
            <td colspan="4"><?php $ob_text = get_object_text_from_id($row[0]); echo $ob_text; ?></td>
        </tr>
        <tr>
            <td><span title="This is the picture of the object you want to delete"><label>Picture</label></span></td>
            <td><center><a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelview.php?id=<?php $model_id = get_object_model_from_id($row[0]); echo $model_id; ?>"><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $model_id; ?>" alt="Thumbnail"/></a></center></td>
            <td><center><span title="This is the map around the object you want to delete"><label>Map</label></span></center></td>
            <td><center><object data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $longitude; ?>&amp;lat=<?php echo $latitude; ?>&amp;zoom=14" type="text/html" width="300" height="225"></object></center></td>
        </tr>
        <tr>
            <td><span title="Please add a short (max 100 letters) statement why you are deleting this data. This will help the maintainers understand what you are doing. eg: 'I added a static model in replacement, so please delete it'. Only alphanumerical, colon, semi colon, question and exclamation mark, arobace, minus, underscore, antislash and point are granted."><label for="comment">Comment<em>*</em></label></span></td>
            <td colspan="4"><input type="text" id="comment" name="comment" maxlength="100" size="40" value="" onchange="checkComment(this);"/></td>
        </tr>
        <tr>
            <td><span title="Please live YOUR VALID email address over here. This will help you be informed of your submission process."><label for="email">Email address (not mandatory)</label></span></td>
            <td colspan="4"><input type="text" id="email" name="email" maxlength="50" size="40" value="" onchange="checkEmail(this);"/></td>
        </tr>
        <tr>
            <td colspan="4" class="submit">
<?php
    // Google Captcha stuff
    require_once '../../inc/captcha/recaptchalib.php';
    $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
    echo recaptcha_get_html($publickey);
?>
            <br />
            <input name="delete_choice" type="hidden" value="<?php echo $row[0]; ?>" />
            <input name="step" type="hidden" value="3" />
            <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" />

            <input type="submit" name="submit" value="Forward for deletion!" />
            <input type="button" name="cancel" value="Cancel this deletion!" onclick="history.go(-1)"/>
            </td>
        </tr>
    </table>
    </form>
<?php
    include '../../inc/footer.php';

    exit;
}

// If we have more than one solution
else if ($returned_rows > 1) {

?>
<script src="/inc/js/check_form.js" type="text/javascript"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("delete_position");

    if (!checkStringNotDefault(form["comment"], "") || !checkComment(form['comment']) ||
        (form['email'].value!="" && !checkEmail(form['email'])))
        return false;

}
/*]]>*/
</script>

    <p class="center"><?php echo $returned_rows;?> objects with WGS84 coordinates longitude: <?php echo $long;?>, latitude: <?php echo $lat;?> have been found in the database.<br />Please select with the left radio button the one you want to delete.</p>

    <ul class="warning">If you want to replace an object which is set as an "OBSTRUCTION" (see the object's family hereunder) by a 3D model, please consider adding the 3D model <b>first</b> - ie before deleting the shared object.</ul>

    <form id="delete_position" method="post" action="check_delete_shared.php" onsubmit="return validateForm();">
    <table>

<?php

    // Starting multi-solutions form
    $is_first = true; // Just used to put the selected button on the first entry
    while ($row = pg_fetch_row($result)) {
?>
        <tr>
            <th colspan="5">Object number #<?php echo $row[0]; ?></th>
        </tr>
        <tr>
            <th rowspan="10">
                <input type="radio" name="delete_choice" value="<?php echo $row[0];?>" <?php echo ($is_first)?"checked=\"checked\"":"";?> />
            </th>
            <td><span title="This is the family name of the object you want to delete."><label>Object's family</label></span></td>
            <td colspan="4"><?php $family_name = get_object_family_from_id($row[0]); echo $family_name; ?></td>
        </tr>
        <tr>
            <td><span title="This is the model name of the object you want to delete, ie the name as it's supposed to appear in the .stg file."><label>Model name</label></span></td>
            <td colspan="4"><?php $model_name = object_name(get_object_model_from_id($row[0]));  echo $model_name; ?></td>
        </tr>
        <tr>
            <td><span title="This is the WGS84 longitude of the object you want to update. Has to be between -180.000000 and +180.000000."><label>Longitude</label></span></td>
            <td colspan="4"><?php $longitude = get_object_longitude_from_id($row[0]); echo $longitude; ?></td>
        </tr>
        <tr>
            <td><span title="This is the WGS84 latitude of the object you want to update. Has to be between -90.000000 and +90.000000."><label>Latitude</label></span></td>
            <td colspan="4"><?php $latitude = get_object_latitude_from_id($row[0]); echo $latitude; ?></td>
        </tr>
        <tr>
            <td><span title="This is the last update or submission date/time of the corresponding object."><label>Date/Time of last update</label></span></td>
            <td colspan="4"><?php echo $row[1]; ?></td>
        </tr>
        <tr>
            <td><span title="This is the ground elevation (in meters) of the position where the object you want to delete is located. Warning: if your model is sunk into the ground, the Elevation offset field is set below."><label>Elevation</label></span></td>
            <td colspan="4"><?php $elevation = get_object_elevation_from_id($row[0]); echo $elevation; ?></td>
        </tr>
        <tr>
            <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><label>Elevation Offset</label></span></td>
            <td colspan="4"><?php $offset = get_object_offset_from_id($row[0]); echo $offset; ?></td>
        </tr>
        <tr>
            <td><span title="The orientation of the object you want to delete - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label>Orientation</label></span></td>
            <td colspan="4"><?php $orientation = heading_true_to_stg(get_object_true_orientation_from_id($row[0])); echo $orientation; ?></td>
        </tr>
        <tr>
            <td><span title="Object's family (OBSTRUCTION, LANDMARK, ...)."><label>Object's family</label></span></td>
            <td colspan="4"><?php $ob_text = get_group_name_from_id($row[2]); echo $ob_text; ?></td>
        </tr>
        <tr>
            <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description</label></span></td>
            <td colspan="4"><?php $ob_text = get_object_text_from_id($row[0]); echo $ob_text; ?></td>
        </tr>
        <tr>
            <td><span title="This is the picture of the object you want to delete"><label>Picture</label></span></td>
            <td><center><a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelview.php?id=<?php $model_id = get_object_model_from_id($row[0]); echo $model_id; ?>"><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $model_id; ?>" alt="Thumbnail"/></a></center></td>
            <td><center><span title="This is the map around the object you want to delete"><label>Map</label></span></center></td>
            <td><center><object data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $longitude; ?>&amp;lat=<?php echo $latitude; ?>&amp;zoom=14" type="text/html" width="300" height="225"></object></center></td>
        </tr>
<?php
        $is_first = false;
    }
?>
        <tr>
            <td><span title="Please add a short (max 100 letters) statement why you are deleting this data. This will help the maintainers understand what you are doing. eg: this model is in a river, so please delete it"><label for="comment">Comment<em>*</em></label></span></td>
            <td colspan="4"><input type="text" id="comment" name="comment" maxlength="100" size="100" value="" onchange="checkComment(this);"/></td>
        </tr>
        <tr>
            <td><span title="Please leave YOUR VALID email address over here. This will help you be informed of your submission process."><label for="email">Email address</label></span></td>
            <td colspan="4"><input type="text" id="email" name="email" maxlength="50" size="50" value="" onchange="checkEmail(this);"/></td>
        </tr>
        <tr>
            <td colspan="5" class="submit">
            <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" />
            <input name="step" type="hidden" value="3" />
<?php
        // Google Captcha stuff
        require_once '../../inc/captcha/recaptchalib.php';
        $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
        echo recaptcha_get_html($publickey);
?>
            <br />
            <input type="submit" name="submit" value="Forward for deletion!" />
            <input type="button" name="cancel" value="Cancel this deletion!" onclick="history.go(-1)"/>
            </td>
        </tr>
    </table>
    </form>
<?php
    include '../../inc/footer.php';
    exit;
}
require '../../inc/footer.php';
?>