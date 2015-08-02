<?php
require_once '../../autoload.php';
$modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();
$authorDaoRO = \dao\DAOFactory::getInstance()->getAuthorDaoRO();
$requestDaoRW = \dao\DAOFactory::getInstance()->getRequestDaoRW();

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
    $error_text = "Sorry but the reCAPTCHA wasn't entered correctly. <a href='javascript:history.go(-1)'>Go back and try it again</a>.<br />";
    if (isset($resp)) {
        $error_text .= "(reCAPTCHA complained: " . $resp->error . ")<br />";
    }
    $error_text .= "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
    include '../../inc/error_page.php';
    exit;
}

$modelChecker = new ModelChecker();

$page_title = "Automated Models Submission Form";
require '../../view/header.php';

################################################
################################################
#                                              #
# STEP 1 : CHECK IF ALL FILES WERE RECEIVED    #
#                                              #
################################################
################################################

$exceptions = array_merge($modelChecker->checkAC3DFileArray($_FILES['ac3d_file']),
        array_merge($modelChecker->checkXMLFileArray($_FILES['xml_file']),
        $modelChecker->checkThumbFileArray($_FILES['mo_thumbfile'])));

// PNG Files
for ($i=0; $i<count($_FILES['png_file']['name']); $i++) {
    if (!empty($_FILES['png_file']['name'][$i])) {
        $arrayPNG = array();
        $arrayPNG['name'] = $_FILES['png_file']['name'][$i];
        $arrayPNG['type'] = $_FILES['png_file']['type'][$i];
        $arrayPNG['size'] = $_FILES['png_file']['size'][$i];
        $arrayPNG['error'] = $_FILES['png_file']['error'][$i];
        $arrayPNG['tmp_name'] = $_FILES['png_file']['tmp_name'][$i];

        $exceptionsPNG = $modelChecker->checkPNGArray($arrayPNG);
        $exceptions = array_merge($exceptions, $exceptionsPNG);
    }
}

# STEP 2 : MOVE THUMBNAIL, AC3D, PNG AND XML FILES IN TMP DIRECTORY (Will be removed later on)
##############################################################################

if (empty($exceptions)) {
    $thumbName = $_FILES['mo_thumbfile']['name'];
    $ac3dName  = $_FILES['ac3d_file']['name'];
    $xmlName   = $_FILES['xml_file']['name'];

    // Open working directory and set paths
    try {
        $tmp_dir = sys_get_temp_dir();
        $targetPath = $modelChecker->openWorkingDirectory($tmp_dir);

        if (!empty($xmlName)) {
            $xmlPath = $targetPath.$xmlName;
            
            // move XML file to temp dir
            if (!move_uploaded_file($_FILES['xml_file']['tmp_name'], $xmlPath)) {
                $exceptions[] = new \Exception("There has been an error while moving the file \"".$xmlName."\" on the server.");
            }
        }
        $thumbPath = $targetPath.$thumbName;
        $ac3dPath  = $targetPath.$ac3dName;

        // move A3CD file to temp dir
        if (!move_uploaded_file($_FILES['ac3d_file']['tmp_name'], $ac3dPath)) {
            $exceptions[] = new \Exception("There has been an error while moving the file \"".$ac3dName."\" on the server.");
        }

        // move Thumbnail file to temp dir
        if (!move_uploaded_file($_FILES['mo_thumbfile']['tmp_name'], $thumbPath)) {
            $exceptions[] = new \Exception("There has been an error while moving the file \"".$thumbName."\" on the server.");
        }

        // move PNG files to temp dir
        for ($i=0; $i<count($_FILES['png_file']['name']); $i++) {
            if (!empty($_FILES['png_file']['name'][$i])
                    && !move_uploaded_file($_FILES['png_file']['tmp_name'][$i], $targetPath.$_FILES['png_file']['name'][$i])) {
                $exceptions[] = new \Exception("There has been an error while moving the file \"".$_FILES['png_file']['name'][$i]."\" on the server."); 
            }
        }
    } catch (Exception $ex) {
        $exceptions[] = $ex;
    }
}

######################################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS #
######################################################

if (!empty($exceptions)) {
    foreach ($exceptions as $ex) {
        $errormsg .= "<li>".$ex->getMessage()."</li>";
    }
    
    echo "<h2>Oops, something went wrong</h2>" .
         "Error message(s)  : <br/>" .
         "<ul>".$errormsg."</ul><br/>" .
         "<a href='javascript:history.go(-1)'>Go back and correct your mistakes</a>.<br/><br/>" .
         "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> " .
         "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";
    if (isset($targetPath)) {
        clear_dir($targetPath);
    }
    include '../../view/footer.php';
    exit;
}

###############################################
###############################################
#                                             #
# STEP 4 : CHECK FILES                        #
#                                             #
###############################################
###############################################

$validatorsSet = new \submission\ValidatorsSet();
if ($xmlName != "") {
    $modelFilesValidator = \submission\ModelFilesValidator::instanceWithXML($targetPath, $xmlName, $ac3dName, $_FILES["png_file"]["name"]);
} else {
    $modelFilesValidator = \submission\ModelFilesValidator::instanceWithAC3DOnly($targetPath, $ac3dName, $_FILES["png_file"]["name"]);
}
$thumbValidator = new \submission\ThumbValidator($thumbPath);
$filenamesValidator = new \submission\FilenamesValidator($ac3dName, $xmlName, $thumbName, $_FILES["png_file"]["name"]);
$validatorsSet->addValidator($modelFilesValidator);
$validatorsSet->addValidator($thumbValidator);
$validatorsSet->addValidator($filenamesValidator);

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
        $exceptions[] = new \Exception("Filename \"".$path_to_use."\" is already used by another model");
    }
}

####################################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS
####################################################

if (!empty($exceptions)) {
    foreach ($exceptions as $ex) {
        $errormsg .= "<li>".$ex->getMessage()."</li>";
    }
    
    echo "<h2>Oops, something went wrong</h2>" .
         "Error message(s)  : <br/>" .
         "<ul>".$errormsg."</ul><br/>" .
         "<a href='javascript:history.go(-1)'>Go back and correct your mistakes</a>.<br/><br/>" .
         "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> " .
         "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";

    include '../../view/footer.php';
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
    unlink($thumbPath);                                // Has to be deleted, because it's not put into the .tar.gz

    if (isset($xmlPath)) {
        $modelChecker->dos2Unix($xmlPath);
    }
    $modelChecker->dos2Unix($ac3dPath);
    $modelFile = $modelChecker->archiveModel($targetPath);

    clear_dir($targetPath);                                   // Delete temporary model directory
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
    $moGroupId   = $_POST["model_group_id"];
    $modelId     = $_POST["modelId"];
    
    if (!\FormChecker::isModelId($modelId)) {
        $fatalerror = true;
        $errormsg .= "<li>Please check the original model selected.</li>";
    }

    if (!\FormChecker::isModelName($name)) {
        $fatalerror = true;
        $errormsg .= "<li>Please check the model name.</li>";
    }
    
    if (!\FormChecker::isModelGroupId($moGroupId)) {
        $fatalerror = true;
        $errormsg .= "<li>Please check the model group.</li>";
    }
    
    if (!\FormChecker::isAuthorId($authorId)) {
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
if (\FormChecker::isComment($_POST['comment'])) {
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
    include '../../view/footer.php';
    clear_dir($targetPath);
    exit;
}
else {
    $modelFactory = new ModelFactory($modelDaoRO, $authorDaoRO);
    $newModel = new \model\Model();
    $newModelMD = $modelFactory->createModelMetadata($modelId, $authorId, $path_to_use,
            $name, $notes, $moGroupId);
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
    
    $request = new \model\RequestModelUpdate();
    $request->setNewModel($newModel);
    $request->setContributorEmail($contr_email);
    $request->setComment($sent_comment);
    
    try {
        $updatedReq = $requestDaoRW->saveRequest($request);
        
        echo "<p class=\"center\">Your model named ".$path_to_use.' ';
        echo "has been successfully queued into the FG scenery database model update requests!<br />";
        echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
        echo "The FG community would like to thank you for your contribution!<br />";
        echo "Want to submit another model or position?<br /> <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/\">Click here to go back to the submission page.</a></p>";

        // Sending mail if there is no false and SQL was correctly inserted.
        // Sets the time to UTC.
        date_default_timezone_set('UTC');
        $dtg = date('l jS \of F Y h:i:s A');
        // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
        $ipaddr = htmlentities(stripslashes($_SERVER["REMOTE_ADDR"]));
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
require '../../view/footer.php';
?>