<?php
require_once '../../autoload.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();
$requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();

// Inserting libs
require_once '../../inc/functions.inc.php';


// Checks all variables if exist
if (isset($_POST['step']) && preg_match('/^[0-9]+$/u', $_POST['step'])) {
    $step = $_POST['step'];
}

if (isset($_REQUEST['delete_choice']) && FormChecker::isObjectId($_REQUEST['delete_choice'])) {
    $id_to_delete = stripslashes($_REQUEST['delete_choice']);
}

if (isset($_POST['email']) && FormChecker::isEmail($_POST['email'])) {
    $safe_email = stripslashes($_POST['email']);
}

if (!empty($_POST['comment']) && FormChecker::isComment($_POST['comment'])) {
    $comment = strip_tags($_POST['comment']);
}

// Final step to deletion
if (isset($step) && $step == 3 && isset($id_to_delete)) {

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
    
    $objectToDel = $objectDaoRO->getObject($id_to_delete);
    $modelMD = $modelDaoRO->getModelMetadata($objectToDel->getModelId());
    
    $request = new RequestObjectDelete();
    $request->setObjectToDelete($objectToDel);
    $request->setComment($comment);
    
    // Should in fact be somewhere like here. Checking that comment exists. Just a small verification as it's not going into DB.
    $failed_mail = false;
    if (isset($safe_email)) {
        $request->setContributorEmail($safe_email);
        echo "<p class=\"center ok\">Email: ".$safe_email."</p>";
    } else {
        echo "<p class=\"center warning\">No email was given (not mandatory) or email mismatch!</p>";
        $failed_mail = true;
    }

    try {
        $updatedReq = $requestDaoRW->saveRequest($request);
    } catch (Exception $ex) {
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
    $ipaddr = stripslashes($_SERVER['REMOTE_ADDR']);
    $host   = gethostbyaddr($ipaddr);
    
    $emailSubmit = EmailContentFactory::getObjectDeleteRequestPendingEmailContent($dtg, $ipaddr, $host, $modelMD, $updatedReq);
    $emailSubmit->sendEmail("", true);

    // Mailing the submitter and tell him that his submission has been sent for validation.
    if (!$failed_mail) {
        $emailSubmit = EmailContentFactory::getObjectDeleteRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $updatedReq, $modelMD);
        $emailSubmit->sendEmail($safe_email, false);
    }
    include '../../inc/footer.php';
    exit;
}


$error = false;
global $error;

if (!isset($id_to_delete)) {
    $error_text = "";

    // Checking that latitude exists, is of good length and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.
    if (FormChecker::isLatitude($_POST['latitude'])) {
        $lat = number_format(htmlentities(stripslashes($_POST['latitude'])),7,'.','');
    }
    else {
        $error_text .= "Latitude mismatch!<br/>";
        $error = true;
    }

    // Checking that longitude exists, if of good length and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
    if (FormChecker::isLongitude($_POST['longitude'])) {
        $long = number_format(htmlentities(stripslashes($_POST['longitude'])),7,'.','');
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


// If the delete_choice is sent directly to us from a webform "outside" the submission world
if (isset($id_to_delete)) {
    // Let's grab the information about this object from the database
    try {
        $objectToDel = $objectDaoRO->getObject($id_to_delete);
    } catch (Exception $e) {
        $page_title = "Automated Objects Deletion Form";
        $error_text = "Sorry, but no object with id $id_to_delete was found.";
        include '../../inc/error_page.php';
        exit;
    }
}
else {
    // Let's see in the database if something exists at this position
    $candidateObjects = $objectDaoRO->getObjectsAt($long, $lat);
    
    // We have no result
    if (count($candidateObjects) == 0) {
        $page_title = "Automated Objects Deletion Form";
        $error_text = "Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.". Please <a href='javascript:history.go(-1)'>go back and check your position</a> (see in the relevant STG file).";
        include '../../inc/error_page.php';
        exit;
    } else if (count($candidateObjects) == 1) {
        $objectToDel = $candidateObjects[0];
    }
}

$page_title = "Automated Objects Deletion Form";
require '../../inc/header.php';

// We have only one result

if (isset($objectToDel)) {
    $modelMDToDel = $modelDaoRO->getModelMetadata($objectToDel->getModelId());
    echo "<p class=\"center\">You have asked to delete object <a href=\"/objectview.php?id=".$objectToDel->getId()."\">#".$objectToDel->getId()."</a>.</p>";
?>
<script src="/inc/js/check_form.js" type="text/javascript"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("delete_position");

    if (!checkStringNotDefault(form["comment"], "") || !checkComment(form['comment']) ||
            (form['email'].value!=="" && !checkEmail(form['email'])))
        return false;

}
/*]]>*/
</script>

    <ul class="warning">If you want to replace an object which is set as an "OBSTRUCTION" (see the object's family hereunder) by a 3D model, please consider adding the 3D model <b>first</b> - ie before deleting the shared object.</ul>

    <form id="delete_position" method="post" action="check_delete_shared.php" onsubmit="return validateForm();">
    <table>
        <tr>
            <td><span title="This is the family name of the model's object you want to delete."><label>Object's model family</label></span></td>
            <td colspan="4"><?php echo $modelMDToDel->getModelsGroup()->getName(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the model name of the object you want to delete, ie the name as it's supposed to appear in the .stg file."><label>Model name</label></span></td>
            <td colspan="4"><?php echo $modelMDToDel->getName(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the WGS84 longitude of the object you want to delete. Has to be between -180.000000 and +180.000000."><label>Longitude</label></span></td>
            <td colspan="4"><?php echo $objectToDel->getLongitude(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the WGS84 latitude of the object you want to delete. Has to be between -90.000000 and +90.000000."><label>Latitude</label></span></td>
            <td colspan="4"><?php echo $objectToDel->getLatitude(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the last update or submission date/time of the corresponding object."><label>Date/Time of last update</label></span></td>
            <td colspan="4"><?php echo $objectToDel->getLastUpdated()->format("Y-m-d (H:i)"); ?></td>
        </tr>
        <tr>
            <td><span title="This is the ground elevation (in meters) of the position where the object you want to delete is located. Warning: if your model is sunk into the ground, the Elevation offset field is set below."><label>Elevation</label></span></td>
            <td colspan="4"><?php echo $objectToDel->getGroundElevation(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><label>Elevation Offset</label></span></td>
            <td colspan="4"><?php echo $objectToDel->getElevationOffset(); ?></td>
        </tr>
        <tr>
            <td><span title="The orientation of the object you want to delete - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label>Orientation</label></span></td>
            <td colspan="4"><?php echo heading_true_to_stg($objectToDel->getOrientation()); ?></td>
        </tr>
        <tr>
            <td><span title="Object's family (obstruction, ...)."><label>Object's family</label></span></td>
            <td colspan="4"><?php echo $objectDaoRO->getObjectsGroup($objectToDel->getGroupId())->getName(); ?></td>
        </tr>
        <tr>
            <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description</label></span></td>
            <td colspan="4"><?php echo $objectToDel->getDescription(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the picture of the object you want to delete"><label>Picture</label></span></td>
            <td><center><a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelview.php?id=<?php $model_id = $objectToDel->getModelId(); echo $model_id; ?>"><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $model_id; ?>" alt="Thumbnail"/></a></center></td>
            <td><center><span title="This is the map around the object you want to delete"><label>Map</label></span></center></td>
            <td><center><object data="http://mapserver.flightgear.org/popmap/?lon=<?php echo $objectToDel->getLongitude(); ?>&amp;lat=<?php echo $objectToDel->getLatitude(); ?>&amp;zoom=14" type="text/html" width="300" height="225"></object></center></td>
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
            <input name="delete_choice" type="hidden" value="<?php echo $objectToDel->getId(); ?>" />
            <input name="step" type="hidden" value="3" />

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
else if (count($candidateObjects) > 1) {

?>
<script src="/inc/js/check_form.js" type="text/javascript"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("delete_position");

    if (!checkStringNotDefault(form["comment"], "") || !checkComment(form['comment']) ||
        (form['email'].value!=="" && !checkEmail(form['email'])))
        return false;

}
/*]]>*/
</script>

    <p class="center"><?php echo count($candidateObjects);?> objects with WGS84 coordinates longitude: <?php echo $long;?>, latitude: <?php echo $lat;?> have been found in the database.<br />Please select with the left radio button the one you want to delete.</p>

    <ul class="warning">If you want to replace an object which is set as an "OBSTRUCTION" (see the object's family hereunder) by a 3D model, please consider adding the 3D model <b>first</b> - ie before deleting the shared object.</ul>

    <form id="delete_position" method="post" action="check_delete_shared.php" onsubmit="return validateForm();">
    <table>

<?php

    // Starting multi-solutions form
    $is_first = true; // Just used to put the selected button on the first entry
    foreach ($candidateObjects as $candidateObj) {
        $candidateModelMD = $modelDaoRO->getModelMetadata($candidateObj->getModelId());
?>
        <tr>
            <th colspan="5">Object number #<?php echo $candidateObj->getId(); ?></th>
        </tr>
        <tr>
            <th rowspan="10">
                <input type="radio" name="delete_choice" value="<?php echo $candidateObj->getId();?>" <?php echo ($is_first)?"checked=\"checked\"":"";?> />
            </th>
            <td><span title="This is the family name of the object you want to delete."><label>Object's family</label></span></td>
            <td colspan="4"><?php echo $candidateModelMD->getModelsGroup()->getName(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the model name of the object you want to delete, ie the name as it's supposed to appear in the .stg file."><label>Model name</label></span></td>
            <td colspan="4"><?php echo $candidateModelMD->getName(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the WGS84 longitude of the object you want to update. Has to be between -180.000000 and +180.000000."><label>Longitude</label></span></td>
            <td colspan="4"><?php $longitude = $candidateObj->getLongitude(); echo $longitude; ?></td>
        </tr>
        <tr>
            <td><span title="This is the WGS84 latitude of the object you want to update. Has to be between -90.000000 and +90.000000."><label>Latitude</label></span></td>
            <td colspan="4"><?php $latitude = $candidateObj->getLatitude(); echo $latitude; ?></td>
        </tr>
        <tr>
            <td><span title="This is the last update or submission date/time of the corresponding object."><label>Date/Time of last update</label></span></td>
            <td colspan="4"><?php echo $candidateObj->getLastUpdated()->format("Y-m-d (H:i)"); ?></td>
        </tr>
        <tr>
            <td><span title="This is the ground elevation (in meters) of the position where the object you want to delete is located. Warning: if your model is sunk into the ground, the Elevation offset field is set below."><label>Elevation</label></span></td>
            <td colspan="4"><?php echo $candidateObj->getGroundElevation(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><label>Elevation Offset</label></span></td>
            <td colspan="4"><?php echo $candidateObj->getElevationOffset(); ?></td>
        </tr>
        <tr>
            <td><span title="The orientation of the object you want to delete - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label>Orientation</label></span></td>
            <td colspan="4"><?php echo heading_true_to_stg($candidateObj->getOrientation()); ?></td>
        </tr>
        <tr>
            <td><span title="Object's family (OBSTRUCTION, LANDMARK, ...)."><label>Object's family</label></span></td>
            <td colspan="4"><?php echo $objectDaoRO->getObjectsGroup($candidateObj->getGroupId())->getName(); ?></td>
        </tr>
        <tr>
            <td><span title="The current text (metadata) shipped with the object. Can be generic, or specific (obstruction, for instance)."><label>Description</label></span></td>
            <td colspan="4"><?php echo $candidateObj->getDescription(); ?></td>
        </tr>
        <tr>
            <td><span title="This is the picture of the object you want to delete"><label>Picture</label></span></td>
            <td><center><a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelview.php?id=<?php $model_id = $candidateObj->getModelId(); echo $model_id; ?>"><img src="http://<?php echo $_SERVER['SERVER_NAME'];?>/modelthumb.php?id=<?php echo $model_id; ?>" alt="Thumbnail"/></a></center></td>
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