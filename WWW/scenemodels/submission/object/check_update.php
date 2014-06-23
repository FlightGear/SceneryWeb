<?php
require_once "../../classes/DAOFactory.php";
require_once '../../classes/ObjectFactory.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
require_once '../../classes/EmailContentFactory.php';

// Checking all variables
if (isset($_POST['new_long']) && is_longitude($_POST['new_long'])) {
    $new_long = pg_escape_string($_POST['new_long']);
}

if (isset($_POST['new_lat']) && is_latitude($_POST['new_lat'])) {
    $new_lat = pg_escape_string($_POST['new_lat']);
}

if (isset($_POST['new_offset']) && is_offset($_POST['new_offset'])) {
    $new_offset = pg_escape_string($_POST['new_offset']);
    // Have to put quotes around NULL, else we're gonna have problems with the SQL query.
    if ($new_offset == '' || $new_offset == 0) {
        $new_offset = 'NULL';
    }
}

if (isset($_POST['new_heading']) && is_heading($_POST['new_heading'])) {
    $new_orientation = pg_escape_string($_POST['new_heading']);
}

if (isset($_POST['id_to_update']) && is_object_id($_POST['id_to_update'])) {
    $id_to_update = pg_escape_string($_POST['id_to_update']);
}

if (isset($_REQUEST['update_choice']) && is_object_id($_REQUEST['update_choice'])) {
    $id_to_update = pg_escape_string(stripslashes($_REQUEST['update_choice']));
}

if (isset($_POST['model_name']) && is_model_id($_POST['model_name'])) {
    $model_name = pg_escape_string($_POST['model_name']);
}

if (isset($_POST['email']) && is_email($_POST['email'])) {
    $safe_email = pg_escape_string(stripslashes($_POST['email']));
}

if (isset($_POST['new_ob_text'])
    && strlen($_POST['new_ob_text']) > 0
    && strlen($_POST['new_ob_text']) <= 100) {
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
    
    $objectFactory = new ObjectFactory($objectDaoRO);
    $oldObject = $objectDaoRO->getObject($id_to_update);
    $newObject = $objectFactory->createObject($id_to_update, $model_name,
            $new_long, $new_lat, $oldObject->getCountry()->getCode(), 
            $new_offset, heading_stg_to_true($new_orientation), 1, "");
    
    $oldModelMD = $modelDaoRO->getModelMetadata($oldObject->getModelId());
    $newModelMD = $modelDaoRO->getModelMetadata($model_name);

    // Preparing the update request: the quotes around NULL put above were tested OK.
    $query_update="UPDATE fgs_objects ".
                  "SET ob_text=$$".$safe_new_ob_text."$$, wkb_geometry=ST_PointFromText('POINT(".$new_long." ".$new_lat.")', 4326), ob_gndelev=-9999, ob_elevoffset=".$new_offset.", ob_heading=".heading_stg_to_true($new_orientation).", ob_model=".$model_name.", ob_group=1 ".
                  "WHERE ob_id=".$id_to_update.";";

    // Generating the SHA-256 hash based on the data we've received + microtime (ms) + IP + request. Should hopefully be enough ;-)
    $sha_to_compute = "<".microtime()."><".$_SERVER["REMOTE_ADDR"]."><".$query_update.">";
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
    $ipaddr = pg_escape_string(stripslashes($_SERVER["REMOTE_ADDR"]));
    $host = gethostbyaddr($ipaddr);
    $family_name = $_POST['family_name'];
    $comment = $_POST['comment'];

    $emailSubmit = EmailContentFactory::getSharedUpdateRequestPendingEmailContent($dtg, $ipaddr, $host, $safe_email, $oldObject, $oldModelMD, $newObject, $newModelMD, $comment, $sha_hash);
    $emailSubmit->sendEmail("", true);

    // Mailing the submitter to tell him that his submission has been sent for validation.
    if (!$failed_mail) {
        $emailSubmit = EmailContentFactory::getSharedUpdateRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $sha_hash, $oldObject, $oldModelMD, $newObject, $newModelMD, $comment);
        $emailSubmit->sendEmail($safe_email, false);
    }
    include '../../inc/footer.php';
    exit;
}

// Getting back the update_choice
if (isset($id_to_update)) {
    $page_title = "Automated Object Update Form";
    $body_onload = "update_objects();";
    include '../../inc/header.php';
    
    $objectToUp = $objectDaoRO->getObject($id_to_update);
    $modelMDToUp = $modelDaoRO->getModelMetadata($objectToUp->getModelId());

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
            <?php echo $modelMDToUp->getModelsGroup()->getName(); ?>
          </td>
          <td>
<?php
    $id_family = $modelMDToUp->getModelsGroup()->getId();

    if (!$modelMDToUp->getModelsGroup()->isStatic()) {
        // Show all the families other than the static family
        $modelsGroups = $modelDaoRO->getModelsGroups();

        // Start the select form
        echo "<select id=\"family_name\" name=\"family_name\" onchange=\"update_objects();\">\n";
        foreach ($modelsGroups as $modelsGroup) {
            $name = preg_replace('/ /',"&nbsp;",$modelsGroup->getName());
            if ($id_family == $modelsGroup->getId()) {
                echo "<option selected=\"selected\" value=\"".$modelsGroup->getId()."\">".$name."</option>\n";
            } else {
                echo "<option value=\"".$modelsGroup->getId()."\">".$name."</option>\n";
            }
        }
        echo "</select>";
    }
    else {
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
    echo $modelMDToUp->getName();
?>
          </td>
          <td>
<?php

    if (!$modelMDToUp->getModelsGroup()->isStatic()) {

        echo "<div id=\"form_objects\">";
        echo "    <select name='model_name' id='model_name' onchange='change_thumb()'>";

        $modelMetadatas = $modelDaoRO->getModelMetadatasByGroup($id_family, 0, "ALL");
        
        // Showing the results.
        foreach ($modelMetadatas as $modelMetadata) {
            $id   = $modelMetadata->getId();
            $path = $modelMetadata->getFilename();

            if ($modelMDToUp->getId() == $modelMetadata->getId()) {
                echo "<option selected=\"selected\" value='".$id."'>".$path."</option>\n";
            } else {
                echo "<option value='".$id."'>".$path."</option>\n";
            }
        }

        echo "</select>\n";
        echo "</div>\n";

    } else {
        echo "      <input name=\"model_name\" type=\"hidden\" value=\"".$objectToUp->getModelId()."\"></input>";
        echo $modelMDToUp->getName();
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
            <?php $actual_long = $objectToUp->getLongitude(); echo $actual_long; ?>
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
            <?php $actual_lat = $objectToUp->getLatitude(); echo $actual_lat; ?>
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
        $country = $objectToUp->getCountry()->getName();
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
            <?php $actual_elevation = $objectToUp->getGroundElevation(); echo $actual_elevation; ?>
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
            <?php $actual_offset = $objectToUp->getElevationOffset(); echo $actual_offset; ?>
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
            <?php $actual_orientation = heading_true_to_stg($objectToUp->getOrientation()); echo $actual_orientation; ?>
          </td>
          <td>
            <input type="text" name="new_heading" id="new_heading" maxlength="7" value="<?php echo $actual_orientation; ?>" onkeyup="checkNumeric(this,0,359.999);" />
          </td>
        </tr>
        <tr>
            <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description (Just for test now - don't use)</label></span></td>
            <td><?php $actual_ob_text = $objectToUp->getDescription(); echo $actual_ob_text; ?></td>
            <td>
                <input type="text" name="new_ob_text" id="new_ob_text" size="50" maxlength="100" value="<?php echo $actual_ob_text; ?>" onkeyup="checkComment(this);" />
            </td>
        </tr>
        <tr>
            <td><span title="This is the picture of the object you want to update"><label>Picture</label></span></td>
            <td><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php $model_id = $objectToUp->getModelId(); echo $model_id; ?>" alt="Actual thumbnail"/></td>
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

    // Let's see in the database if something exists at this position
    $objects = $objectDaoRO->getObjectsAt($long, $lat);

    if (count($objects) == 0) {
        $page_title  = "Automated Objects Update Form";
        $error_text  = "Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.".";
        $advise_text = "Please <a href='javascript:history.go(-1)'>go back and check your position</a> (see in the relevant STG file).";
        include '../../inc/error_page.php';
        exit;
    }
    else if (count($objects) == 1) { // If we have just one answer...
        $page_title = "Automated Objects Update Form";
        include '../../inc/header.php';
        
        $object = $objects[0];
        $modelMetadata = $modelDaoRO->getModelMetadata($object->getModelId());

        echo "<p class=\"center\">One object (#".$object->getId().") with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." has been found in the database.</p>";
?>
        <form id="update_position" method="post" action="check_update.php">
        <table>
            <tr>
                <td><span title="This is the family name of the object you want to update."><label>Object's family</label></span></td>
                <td colspan="4"><?php echo $modelMetadata->getModelsGroup()->getName(); ?></td>
            </tr>
            <tr>
                <td><span title="This is the model name of the object you want to update, ie the name as it's supposed to appear in the .stg file."><label>Model name</label></span></td>
                <td colspan="4"><?php echo $modelMetadata->getName(); ?></td>
            </tr>
            <tr>
                <td><span title="This is the last update or submission date/time of the corresponding object.">
                <label>Date/Time of last update</label></span></td>
                <td colspan="4"><?php echo $object->getLastUpdated()->format("Y-m-d (H:i)"); ?></td>
            </tr>
            <tr>
                <td><span title="This is the ground elevation (in meters) of the position where the object you want to update is located. Warning : if your model is sunk into the ground, the Elevation offset field is set below."><label>Elevation</label></span></td>
                <td colspan="4"><?php echo $object->getGroundElevation(); ?></td>
            </tr>
            <tr>
                <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><label>Elevation Offset</label></span> (see <a href="../../contribute.php#offset">here</a> for more help)</td>
                <td colspan="4"><?php echo $object->getElevationOffset(); ?></td>
            </tr>
            <tr>
                <td><span title="The orientation of the object you want to update - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label>Orientation</label></span></td>
                <td colspan="4"><?php echo heading_true_to_stg($object->getOrientation()); ?></td>
            </tr>
            <tr>
                <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description</label></span></td>
                <td colspan="4"><?php echo $object->getDescription(); ?></td>
            </tr>
            <tr>
                <td><span title="This is the picture of the object you want to update"><a style="cursor: help; ">Picture</a></span></td>
                <td><a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelview.php?id=<?php echo $object->getModelId(); ?>"><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $object->getModelId(); ?>"></a></td>
                <td><span title="This is the map around the object you want to update"><a style="cursor: help; ">Map</a></span></td>
                <td>
                <object data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $long; ?>&amp;lat=<?php echo $lat; ?>&amp;zoom=14" type="text/html" width="300" height="225"></object>
                </td>
            </tr>
            <input name="update_choice" type="hidden" value="<?php echo $object->getId(); ?>" />
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
        
        exit;
    }

    // If we have more than one, the user has to choose...
    else {
        $page_title = "Automated Objects Update Form";
        include '../../inc/header.php';
    
        echo "<p class=\"center\">".count($objects)." objects with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." were found in the database.<br />Please select with the left radio button the one you want to update.</p>";

        // Starting multi-solutions form
        echo "<form id=\"update_position\" method=\"post\" action=\"check_update.php\">";
        echo "<table>";

        $i = 1; // Just used to put the selected button on the first entry
        foreach ($objects as $object) {
            $modelMetadata = $modelDaoRO->getModelMetadata($object->getModelId());
?>
            <tr>
                <td colspan="5" background="white"><center><b>Object number #<?php echo $object->getId(); ?></b></center>
                </td>
            </tr>
            <tr>
                <th rowspan="7">
                    <input type="radio" name="update_choice" value="<?php echo $object->getId();?>" <?php echo ($i==1)?"checked=\"checked\"":""; ?> />
                </th>
                <td><span title="This is the family name of the object you want to update."><label>Object's family</label></span></td>
                <td colspan="4"><?php echo $modelMetadata->getModelsGroup()->getName(); ?></td>
            </tr>
            <tr>
                <td><span title="This is the model name of the object you want to update, ie the name as it's supposed to appear in the .stg file.">
                <label>Model name</label></span></td>
                <td colspan="4"><?php echo $modelMetadata->getName(); ?></td>
            </tr>
            <tr>
                <td><span title="This is the last update or submission date/time of the corresponding object.">
                <label>Date/Time of last update</label></span></td>
                <td colspan="4"><?php echo $object->getLastUpdated()->format("Y-m-d (H:i)"); ?></td>
            </tr>
            <tr>
                <td><span title="This is the ground elevation (in meters) of the position where the object you want to update is located. Warning : if your model is sunk into the ground, the Elevation offset field is set below.">
                <label>Elevation</label></span></td>
                <td colspan="4"><?php echo $object->getGroundElevation();?></td>
            </tr>
            <tr>
                <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><label>Elevation Offset</label></span></td>
                <td colspan="4"><?php echo $object->getElevationOffset();?></td>
            </tr>
            <tr>
                <td><span title="The orientation of the object you want to update - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label>Orientation</label></span></td>
                <td colspan="4"><?php echo $actual_orientation = heading_true_to_stg($object->getOrientation());?></td>
            </tr>
            <tr>
                <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description</label></span></td>
                <td colspan="4"><?php echo $object->getDescription();?></td>
            </tr>
            <tr>
                <td><span title="This is the picture of the object you want to update"><label>Picture</label></span></td>
                <td><a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelview.php?id=<?php echo $object->getModelId(); ?>"><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $object->getModelId(); ?>" alt="Thumbnail"/></a></td>
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
                <input name="comment" type="hidden" value="<?php echo $_POST['comment']; ?>" />
                <input type="submit" name="submit" value="I want to update the selected object!" />
                <input type="button" name="cancel" value="Cancel - I made a mistake!" onclick="history.go(-1)"/>
                </td>
            </tr>
        </table>
        </form>
<?php
        include '../../inc/footer.php';
        exit;
    }
    
}
?>
