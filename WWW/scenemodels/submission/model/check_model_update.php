<?php
require_once '../../autoload.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$authorDaoRO = DAOFactory::getInstance()->getAuthorDaoRO();
$requestDaoRW = DAOFactory::getInstance()->getRequestDaoRW();

# Inserting libs
require_once '../../inc/captcha/recaptchalib.php';
require_once '../../inc/functions.inc.php';

$fatalerror = false;
$errormsg   = "";

// Private key is needed for the server-to-Google auth.
$privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";

if (isset($_POST['recaptcha_challenge_field']) && isset($_POST['recaptcha_response_field'])) {
    $resp = recaptcha_check_answer ($privatekey,
            $_SERVER["REMOTE_ADDR"],
            $_POST["recaptcha_challenge_field"],
            $_POST["recaptcha_response_field"]);
}

// What happens when the CAPTCHA was entered incorrectly
if (!isset($resp) || !$resp->is_valid) {
    $page_title = "Automated Models Submission Form";
    $error_text = "<br/>Sorry but the reCAPTCHA wasn't entered correctly. <a href='javascript:history.go(-1)'>Go back and try it again</a>.<br />";
    if (isset($resp)) {
        $error_text .= "(reCAPTCHA complained: " . $resp->error . ")<br />";
    }
    $error_text .= "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
    include '../../inc/error_page.php';
    exit;
}

$modelChecker = new ModelChecker();

$page_title = "Automated Models Submission Form";
require '../../inc/header.php';

################################################
################################################
#                                              #
# STEP 1 : CHECK IF ALL FILES WERE RECEIVED    #
#                                              #
################################################
################################################

if ($_FILES["mo_thumbfile"]['name'] != "" && ($_FILES["ac3d_file"]['name'] != "" || $_FILES["xml_file"]['name'] != "")) {
    $thumbName = $_FILES["mo_thumbfile"]['name'];
    $ac3dName  = $_FILES["ac3d_file"]['name'];
    $xmlName   = $_FILES["xml_file"]['name'];
}
else {
    if ($_FILES["mo_thumbfile"]["name"] == "") {
        $fatalerror = true;
        $errormsg .= "<li>You must provide a thumbnail.</li>";
    }
    if ($_FILES["ac3d_file"]['name'] == "" && $_FILES["xml_file"]['name'] == "") {
        $fatalerror = true;
        $errormsg .= "<li>You must provide at least one model (AC or XML) file.</li>";
    }
}

###########################################################
###########################################################
#                                                         #
# STEP 2 : CHECK IF ALL FILES MATCH THE NAMING CONVENTION #
#                                                         #
###########################################################
###########################################################
$exceptions = $modelChecker->checkFilesNames($ac3dName, $xmlName, $thumbName, $_FILES["png_file"]["name"]);
if (count($exceptions) > 0) {
    $fatalerror = true;
    foreach ($exceptions as $ex) {
        $errormsg .= "<li>".$ex->getMessage()."</li>";
    }
}

// Open working directory and set paths
$tmp_dir = sys_get_temp_dir();

try {
    $targetPath = $modelChecker->openWorkingDirectory($tmp_dir);
} catch (Exception $ex) {
    $fatalerror = true;
    $errormsg .= "<li>".$ex->getMessage()."</li>";
}

if ($xmlName != "") {
    $xmlPath = $targetPath.$xmlName;
}
$thumbPath = $targetPath.$thumbName;
$ac3dPath  = $targetPath.$ac3dName;


###############################################
###############################################
#                                             #
# STEP 3 : UPLOAD ALL FILES IN TMP DIRECTORY  #
#                                             #
###############################################
###############################################

# STEP 3.1 : UPLOAD THUMBNAIL FILE IN TMP DIRECTORY (Will be removed later on)
##############################################################################

if ($_FILES['mo_thumbfile']['size'] < 2000000) { // check file size
    if ($_FILES['mo_thumbfile']['type'] == "image/jpeg" && (show_file_extension(basename($thumbName)) == "jpeg") || (show_file_extension(basename($thumbName)) == "JPEG") || (show_file_extension(basename($thumbName)) == "JPG") || (show_file_extension(basename($thumbName)) == "jpg")) { // check type & extension file
        if ($_FILES['mo_thumbfile']['error'] != 0) { // If an error is detected
            $fatalerror = true;
            $errormsg .= "<li>There has been an error while uploading the file \"".$thumbName."\".</li>";
            switch ($_FILES['mo_thumbfile']['error']) {
                case 1:
                    $errormsg .= "<li>The file \"".$thumbName."\" is bigger than this server installation allows.</li>";
                    break;
                case 2:
                    $errormsg .= "<li>The file \"".$thumbName."\" is bigger than this form allows.</li>";
                    break;
                case 3:
                    $errormsg .= "<li>Only part of the file \"".$thumbName."\" was uploaded.</li>";
                    break;
                case 4:
                    $errormsg .= "<li>No file \"".$thumbName."\" was uploaded.</li>";
                    break;
            }
        }
        else {
            if (!move_uploaded_file($_FILES['mo_thumbfile']['tmp_name'], $thumbPath)) { // check uploaded file
                $fatalerror = true;
                $errormsg .= "<li>There has been an error while moving the file \"".$thumbName."\" on the server.</li>";
            }
        }
    }
    else {
        $fatalerror = true;
        $errormsg .= "<li>The file format or extention of your thumbnail file \"".$thumbName."\" seems to be wrong. Thumbnail needs to be a JPEG file.</li>";
    }
} else {
    $fatalerror = true;
    $errormsg .= "<li>Sorry, but the size of your thumbnail file \"".$thumbName."\" exceeds 2Mb (current size: ".$_FILES['mo_thumbfile']['size']." bytes).</li>";
}

# STEP 3.2 : UPLOAD AC3D FILE IN TMP DIRECTORY
##############################################

if ($_FILES['ac3d_file']['size'] < 2000000) { // check size file

    // check type & extension file
    if (($_FILES['ac3d_file']['type'] == "application/octet-stream" || $_FILES['ac3d_file']['type'] == "application/pkix-attr-cert")
            && strtolower(show_file_extension(basename($ac3dName))) == "ac") {

        if ($_FILES['ac3d_file']['error'] != 0) { // If error is detected
            $fatalerror = true;
            $errormsg .= "<li>There has been an error while uploading the file \"".$ac3dName."\".</li>";
            switch ($_FILES['ac3d_file']['error']){
                case 1:
                    $errormsg .= "<li>The file \"".$ac3dName."\" is bigger than this server installation allows.</li>";
                    break;
                case 2:
                    $errormsg .= "<li>The file \"".$ac3dName."\" is bigger than this form allows.</li>";
                    break;
                case 3:
                    $errormsg .= "<li>Only part of the file \"".$ac3dName."\" was uploaded.</li>";
                    break;
                case 4:
                    $errormsg .= "<li>No file \"".$ac3dName."\" was uploaded.</li>";
                    break;
            }
        }
        else if (!move_uploaded_file($_FILES['ac3d_file']['tmp_name'], $ac3dPath)) { // check upload file
            $fatalerror = true;
            $errormsg .= "<li>There has been an error while moving the file \"".$ac3dName."\" on the server.</li>";
        }
    }
    else {
        $fatalerror = true;
        $errormsg .= "<li>The format or the extention seems to be wrong for your AC3D file \"".$ac3dName."\". AC file needs to be a AC3D file.</li>";
    }
}
else {
    $fatalerror = true;
    $errormsg .= "<li>Sorry, but the size of your AC3D file \"".$ac3dName."\" is over 2Mb (current size: ".$_FILES['ac3d_file']['size']." bytes).</li>";
}

# STEP 3.3 : UPLOAD XML FILE IN TMP DIRECTORY
#############################################

if ($_FILES['xml_file']['name'] != "") { // if file exists
    if ($_FILES['xml_file']['size'] < 2000000) { // check size file
        if ($_FILES['xml_file']['type'] == "text/xml" && strtolower(show_file_extension(basename($xmlName))) == "xml") { // check type & extension file
            if ($_FILES['xml_file']['error'] != 0) { // If error is detected
                $fatalerror = true;
                $errormsg .= "<li>There has been an error while uploading the file \"".$xmlName."\".</li>";
                switch ($_FILES['xml_file']['error']) {
                    case 1:
                        $errormsg .= "<li>The file \"".$xmlName."\" is bigger than this server installation allows.</li>";
                        break;
                    case 2:
                        $errormsg .= "<li>The file \"".$xmlName."\" is bigger than this form allows.</li>";
                        break;
                    case 3:
                        $errormsg .= "<li>Only part of the file \"".$xmlName."\" was uploaded.</li>";
                        break;
                    case 4:
                        $errormsg .= "<li>No file \"".$xmlName."\" was uploaded.</li>";
                        break;
                }
            }
            else if(!move_uploaded_file($_FILES['xml_file']['tmp_name'], $xmlPath)) { // check uploaded file
                $fatalerror = true;
                $errormsg .= "<li>There has been an error while moving the file \"".$xmlName."\" on the server.</li>";
            }
        }
        else {
            $fatalerror = true;
            $errormsg .= "<li>The format or extension of your XML file \"".$xmlName."\"seems to be wrong. XML file needs to be an XML file.</li>";
        }
    }
    else {
        $fatalerror = true;
        $errormsg .= "<li>Sorry, but the size of your XML file \"".$xmlName."\" exceeds 2Mb (current size: ".$_FILES['xml_file']['size']." bytes).</li>";
    }
}

# STEP 3.4 : UPLOAD PNG FILE IN TMP DIRECTORY
#############################################

for ($i=0; $i<count($_FILES['png_file']['name']); $i++) {
    if (isset($_FILES['png_file']['name'][$i]) && ($_FILES['png_file']['name'][$i] != '')) {
        $pngName  = $_FILES['png_file']['name'][$i];
        $pngType  = $_FILES['png_file']['type'][$i];
        $pngsize  = $_FILES['png_file']['size'][$i];
        $pngError = $_FILES['png_file']['error'][$i];
        $pngTmp   = $_FILES['png_file']['tmp_name'][$i];

        if ($pngsize < 2000000) { // check size file

            if ($pngType == 'image/png' && strtolower(show_file_extension(basename($pngName))) == "png") { // check type & extension file

                if ($pngError != 0) { // If error is detected
                    $fatalerror = true;
                    $errormsg .= "<li>There has been an error while uploading the file \"".$pngName."\".</li>";
                    switch ($pngError) {
                        case 1:
                            $errormsg .= "<li>The file \"".$pngName."\" is bigger than this server installation allows.</li>";
                            break;
                        case 2:
                            $errormsg .= "<li>The file \"".$pngName."\" is bigger than this form allows.</li>";
                            break;
                        case 3:
                            $errormsg .= "<li>Only part of the file \"".$pngName."\" was uploaded.</li>";
                            break;
                        case 4:
                            $errormsg .= "<li>No file \"".$pngName."\" was uploaded.</li>";
                            break;
                    }
                }
                else if (!move_uploaded_file($pngTmp, $targetPath.$pngName)){ // check uploaded file
                    $fatalerror = true;
                    $errormsg .= "<li>There has been an error while moving the file \"".$pngName."\" on the server.</li>";
                }
            }
            else {
                $fatalerror = true;
                $errormsg .= "<li>The format or extension of your texture file \"".$pngName."\" seems to be wrong. Texture file needs to be a PNG file.</li>";
            }
        }
        else {
            $fatalerror = true;
            $errormsg .= "<li>Sorry, but the size of your texture file \"".$pngName."\" exceeds 2Mb (current size: ".$pngsize." bytes).</li>";
        }
    }
}

######################################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS #
######################################################

if ($fatalerror) {
    echo "<h2>Oops, something went wrong</h2>" .
         "Error message(s)  : <br/>" .
         "<ul>".$errormsg."</ul><br/>" .
         "<a href='javascript:history.go(-1)'>Go back and correct your mistakes</a>.<br/><br/>" .
         "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> " .
         "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";
    if (isset($targetPath)) {
        clear_dir($targetPath);
    }
    include '../../inc/footer.php';
    exit;
}

###############################################
###############################################
#                                             #
# STEP 4 : CHECK FILES                        #
#                                             #
###############################################
###############################################

$validatorsSet = new ValidatorsSet();
if ($xmlName != "") {
    $modelFilesValidator = ModelFilesValidator::instanceWithXML($targetPath, $xmlName, $ac3dName, $_FILES["png_file"]["name"]);
} else {
    $modelFilesValidator = ModelFilesValidator::instanceWithAC3DOnly($targetPath, $ac3dName, $_FILES["png_file"]["name"]);
}
$thumbValidator = new ThumbValidator($thumbPath);
$validatorsSet->addValidator($modelFilesValidator);
$validatorsSet->addValidator($thumbValidator);

$exceptions = $validatorsSet->validate();


$path_to_use = $ac3dName;
if (isset($xmlPath) && file_exists($xmlPath)) {
    # If an XML file is used for the model, the mo_path has to point to it, or
    # FG will not render it correctly. Else the .ac file will be used as mo_path.
    $path_to_use = $xmlName;
}

// Check if path is already used
if (isset($_POST["modelId"])) {
    $modelToUpdateOld = $modelDaoRO->getModelMetadata($_POST["modelId"]);
    if ($path_to_use != $modelToUpdateOld->getFilename() && path_exists($path_to_use)) {
        $exceptions[] = new Exception("Filename \"".$path_to_use."\" is already used by another model");
    } else {
        echo "<p class=\"center\">Your model named ".$path_to_use."\n";
    }
}

####################################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS
####################################################

if (count($exceptions) > 0) {
    foreach ($exceptions as $ex) {
        $errormsg .= "<li>".$ex->getMessage()."</li>";
    }
    
    echo "<h2>Oops, something went wrong</h2>" .
         "Error message(s)  : <br/>" .
         "<ul>".$errormsg."</ul><br/>" .
         "<a href='javascript:history.go(-1)'>Go back and correct your mistakes</a>.<br/><br/>" .
         "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> " .
         "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";

    include '../../inc/footer.php';
    clear_dir($targetPath);
    exit;
}

###############################################
###############################################
#                                             #
# STEP 8 : ARCHIVE AND COMPRESS FILES         #
#                                             #
###############################################
###############################################

if (file_exists($targetPath) && is_dir($targetPath)) {
    $handle    = fopen($thumbPath, "r");
    $contents  = fread($handle, filesize($thumbPath));
    fclose($handle);
    $thumbFile = base64_encode($contents);             // Dump & encode the file
    unlink($thumbPath);                               // Has to be deleted, because it's not put into the .tar.gz

    // Dos2unix on XML
    if (isset($xmlPath)) {
        $d2u_xml_command  = 'dos2unix '.$xmlPath;
        system($d2u_xml_command);
    }

    // Dos2Unix on AC3D
    $d2u_ac3d_command = 'dos2unix '.$ac3dPath;
    system($d2u_ac3d_command);

    $phar = new PharData($tmp_dir . '/static.tar');                // Create archive file
    $phar->buildFromDirectory($targetPath);                        // Fills archive file
    $phar->compress(Phar::GZ);                                     // Convert archive file to compress file
    unlink($tmp_dir . '/static.tar');                              // Delete archive file
    rename($tmp_dir . '/static.tar.gz', $tmp_dir.'/static.tgz');   // Rename compress file

    $handle    = fopen($tmp_dir."/static.tgz", "r");
    $contents  = fread($handle, filesize($tmp_dir."/static.tgz"));
    fclose($handle);
    $modelFile = base64_encode($contents);                    // Dump & encode the file

    unlink($tmp_dir . '/static.tgz');                         // Delete compress file
    clear_dir($targetPath);                                   // Delete temporary static directory
}

###############################################
###############################################
#                                             #
# STEP 9 : CHECK MODEL INFORMATION            #
#                                             #
###############################################
###############################################

if (isset($_POST["model_group_id"]) && isset($_POST["modelId"])
        && isset($_POST["mo_name"]) && isset($_POST["mo_author"])) {

    $name        = addslashes(htmlentities(strip_tags($_POST["mo_name"]), ENT_QUOTES));
    
    if (isset($_POST['notes']) && FormChecker::isComment($_POST['notes'])) {
        $notes   = addslashes(htmlentities(strip_tags($_POST["notes"]), ENT_QUOTES));
    } else {
        $notes   = "";
    }
    
    $authorId    = $_POST["mo_author"];
    $mo_shared   = $_POST["model_group_id"];
    $modelId     = $_POST["modelId"];
    
    if (!FormChecker::isModelId($modelId)) {
        $fatalerror = true;
        $errormsg .= "<li>Please check the original model selected.</li>";
    }

    if (!FormChecker::isModelName($name)) {
        $fatalerror = true;
        $errormsg .= "<li>Please check the model name.</li>";
    }
    
    if (!FormChecker::isModelGroupId($mo_shared)) {
        $fatalerror = true;
        $errormsg .= "<li>Please check the model group.</li>";
    }
    
    if (!FormChecker::isAuthorId($authorId)) {
        $fatalerror = true;
        $errormsg .= "<li>Please check the author value.</li>";
    }
}
else {
    $fatalerror = true;
    $errormsg .= "<li>Please fill in all required fields.</li>";
}

if (!isset($_POST["gpl"])) {
    $fatalerror = true;
    $errormsg .= "<li>You did not accept the GNU GENERAL PUBLIC LICENSE Version 2, June 1991. As all the models shipped with FlightGear must wear this license, your contribution can't be accepted in our database. Please try to find GPLed textures and/or data.</li>";
}

// Checking that comment exists. Just a small verification as it's not going into DB.
if (FormChecker::isComment($_POST['comment'])) {
    $sent_comment = htmlentities(stripslashes($_POST['comment']));
}
else {
    $fatalerror = true;
    $errormsg .= "<li>Please add a comment to the maintainer.</li>";
}

###############################################
###############################################
#                                             #
# FINAL STEP : RESUME AND SUBMIT VALIDATION   #
#                                             #
###############################################
###############################################

if ($fatalerror) {
    echo "<h2>Oops, something went wrong</h2>" .
         "Error message(s)  : <br/>" .
         "<ul>".$errormsg."</ul><br/>" .
         "<a href='javascript:history.go(-1)'>Go back and correct your mistakes</a>.<br/><br/>" .
         "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> " .
         "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";
    include '../../inc/footer.php';
    clear_dir($targetPath);
    exit;
}
else {
    $modelFactory = new ModelFactory($modelDaoRO, $authorDaoRO);
    $newModel = new Model();
    $newModelMD = $modelFactory->createModelMetadata($modelId, $authorId, $path_to_use,
            $name, $notes, $mo_shared);
    $newModel->setMetadata($newModelMD);
    $newModel->setModelFiles($modelFile);
    $newModel->setThumbnail($thumbFile);
    
    $failed_mail = false;
    $au_email = $newModelMD->getAuthor()->getEmail();
    if ($au_email != '' && strlen($au_email) > 0) {
        $safe_au_email = htmlentities(stripslashes($au_email));
    } else {
        $failed_mail = true;
    }
    
    if (FormChecker::isEmail($_POST["email"])) {
        $contr_email = htmlentities(stripslashes($_POST["email"]));
    } else {
        $failed_mail = true;
    }
    
    $request = new RequestModelUpdate();
    $request->setNewModel($newModel);
    $request->setContributorEmail($contr_email);
    $request->setComment($sent_comment);
    
    try {
        $updatedReq = $requestDaoRW->saveRequest($request);
        
        echo "has been successfully queued into the FG scenery database model update requests!<br />";
        echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
        echo "The FG community would like to thank you for your contribution!<br />";
        echo "Want to submit another model or position?<br /> <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/\">Click here to go back to the submission page.</a></p>";

        // Sending mail if there is no false and SQL was correctly inserted.
        date_default_timezone_set('UTC');                                // Sets the time to UTC.
        $dtg = date('l jS \of F Y h:i:s A');
        $ipaddr = htmlentities(stripslashes($_SERVER["REMOTE_ADDR"]));   // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
        $host = gethostbyaddr($ipaddr);

        $emailSubmit = EmailContentFactory::getModelUpdateRequestPendingEmailContent($dtg, $ipaddr, $host, $updatedReq);
        $emailSubmit->sendEmail("", true);
        
        if (!$failed_mail) {
            // Mailing the submitter to tell him that his submission has been sent for validation
            $emailSubmit = EmailContentFactory::getModelUpdateRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $updatedReq);
            $emailSubmit->sendEmail($contr_email, false);
                    
            // If the author's email is different from the subbmitter's, an email is also sent to the author
            if ($safe_au_email != $contr_email) {
                $emailSubmit = EmailContentFactory::getModelUpdateRequestSentForValidationAuthorEmailContent($dtg, $ipaddr, $host, $updatedReq);
                $emailSubmit->sendEmail($safe_au_email, false);
            }
        }
    } catch (Exception $ex) {
        echo "<p class=\"center\">Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.</p><br />";
    }
}
require '../../inc/footer.php';
?>