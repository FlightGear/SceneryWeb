<?php
require_once '../../autoload.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();
$requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';


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
    $page_title = "Automated Objects Submission Form";

    $error_text = "<br />Sorry but the reCAPTCHA wasn't entered correctly. <a href='javascript:history.go(-1)'>Go back and try it again</a>" .
         "<br />(reCAPTCHA complained: " . $resp->error . ")<br />".
         "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
    include '../../inc/error_page.php';
    exit;
}

$page_title = "Automated Objects Submission Form";
require '../../inc/header.php';
echo "<br />";
$error = false;
global $error;

echo "<center>";

// Checking that model_id exists and is containing only figures and with correct decimal format.
if (FormChecker::isModelId($_POST['modelId'])) {
    $model_id = pg_escape_string(stripslashes($_POST['modelId']));
    $modelMD = $modelDaoRO->getModelMetadata($model_id);
    echo "<p class=\"ok\">Model Name: ".$modelMD->getName()."</p>";
    echo "<p class=\"ok\">Family Name: ".$modelMD->getModelsGroup()->getName()."</p>";
}
else {
    echo "<p class=\"warning\">Model Name mismatch!</p>";
    $error = true;
}

// Checking that latitude exists and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.
if (FormChecker::isLatitude($_POST['latitude'])) {
    $lat = number_format(htmlentities(stripslashes($_POST['latitude'])),7,'.','');
    echo "<p class=\"ok\">Latitude: ".$lat."</p>";
}
else {
    echo "<p class=\"warning\">Latitude mismatch!</p>";
    $error = true;
}

// Checking that longitude exists and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
if (FormChecker::isLongitude($_POST['longitude'])) {
    $long = number_format(htmlentities(stripslashes($_POST['longitude'])),7,'.','');
    echo "<p class=\"ok\">Longitude: ".$long."</p>";
}
else {
    echo "<p class=\"warning\">Longitude mismatch!</p>";
    $error = true;
}

// Country.
if (FormChecker::isCountryId($_POST['ob_country'])) {
    $ob_country = $_POST["ob_country"];
    $country = $objectDaoRO->getCountry($ob_country);
    echo "<p class=\"ok\">Country: ".$country->getName()."</p>";
}
else {
    echo "<p class=\"warning\">Country error!</p>";
    $error = true;
}


// Checking that offset exists and is containing only digits, - or ., is >=-10000 and <=10000 and with correct decimal format.
if (FormChecker::isOffset($_POST['offset'])) {
    $offset = number_format(pg_escape_string(stripslashes($_POST['offset'])),2,'.','');
    echo "<p class=\"ok\">Offset: ".$offset."</p>";
}
else {
    echo "<p class=\"warning\">Offset mismatch!</p>";
    $error = true;
}

// Checking that orientation exists and is containing only digits, and is >=0 and <=359
// Then converting the STG orientation into the future DB (true) orientation and with correct decimal format.
if (FormChecker::isHeading($_POST['heading'])) {
    $heading = number_format(pg_escape_string(stripslashes($_POST['heading'])),1,'.','');
    echo "<p class=\"ok\">STG Orientation: ".$heading.", DB (true) orientation: ".number_format(heading_stg_to_true($heading),1,'.','')."</p>";
}
else {
    echo "<p class=\"warning\">Orientation mismatch!</p>";
    $error = true;
}

// Checking that comment exists. Just a small verification as it's not going into DB.
if ($_POST['comment'] != '' && FormChecker::isComment($_POST['comment'])) {
    $sent_comment = htmlentities(stripslashes($_POST['comment']));
    echo "<p class=\"ok\">Comment: ".$sent_comment."</p>";
}
else {
    echo "<p class=\"warning\">Comment mismatch!</p>";
    $error = true;
}

// Checking that email is valid (if it exists).
//(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
$failed_mail = false;
if (FormChecker::isEmail($_POST['email'])) {
    $safe_email = htmlentities(stripslashes($_POST['email']));
    echo "<p class=\"ok\">Email: ".$safe_email."</p>";
}
else {
    echo "<p class=\"warning\">No email was given (not mandatory) or email mismatch!</p>";
    $failed_mail = true;
}

// If there is no error, generating SQL to be inserted into the database pending requests table.
if (!$error) {
    echo "<p class=\"ok\">Data seems to be OK to be inserted in the database</p>";

    $objectFactory = new ObjectFactory($objectDaoRO);
    $newObject = $objectFactory->createObject(-1, $model_id, $long, $lat, $ob_country, 
            $offset, heading_stg_to_true($heading), 1, $modelMD->getName());
    
    // Detect if the object is already in the database
    if ($objectDaoRO->checkObjectAlreadyExists($newObject)) {
        echo "<p class=\"warning\">The object already exists in the database!</p>";
        include '../../inc/footer.php';
    }
    
    
    $newObjects = array();
    $newObjects[] = $newObject;
    $request = new RequestMassiveObjectsAdd();
    $request->setNewObjects($newObjects);
    $request->setContributorEmail($safe_email);
    $request->setComment($sent_comment);
    
    try {
        $updatedReq = $requestDaoRW->saveRequest($request);
        
        echo "<br />Your object request has been successfully queued into the FG scenery database update requests!<br />";
        echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
        echo "The FG community would like to thank you for your contribution!<br />";
        echo "Want to submit another object? <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/object/\">Click here to go back to the submission page.</a><br />";
        echo "Please remember to use the <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/object/index_mass_import.php\">massive insertion script</a> should you have many objects to add.";
        echo "</center>";

        // Sending mail if there is no false and SQL was correctly inserted.
        // Sets the time to UTC.
        date_default_timezone_set('UTC');
        $dtg = date('l jS \of F Y h:i:s A');

        // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
        $ipaddr = htmlentities(stripslashes($_SERVER["REMOTE_ADDR"]));
        $host = gethostbyaddr($ipaddr);

        $emailSubmit = EmailContentFactory::getObjectAddRequestPendingEmailContent($dtg, $ipaddr, $host, $modelMD, $updatedReq);
        $emailSubmit->sendEmail("", true);

        // Mailing the submitter to tell him that his submission has been sent for validation
        if (!$failed_mail) {
            $emailSubmit = EmailContentFactory::getObjectAddRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $updatedReq, $modelMD);
            $emailSubmit->sendEmail($safe_email, false);
        }
    } catch(Exception $e) {
        echo "Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.<br />";
    }
}

require '../../inc/footer.php';

?>