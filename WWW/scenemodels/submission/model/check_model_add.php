<?php
require_once '../../classes/DAOFactory.php';
require_once '../../classes/ModelFactory.php';
require_once '../../classes/ObjectFactory.php';
$modelDaoRO = DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = DAOFactory::getInstance()->getObjectDaoRO();
$authorDaoRO = DAOFactory::getInstance()->getAuthorDaoRO();

# Inserting libs
require_once '../../inc/captcha/recaptchalib.php';
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
require_once '../../classes/EmailContentFactory.php';

$fatalerror = false;
$error      = 0;
$errormsg   = "";

// Private key is needed for the server-to-Google auth.
$privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";

if(isset($_POST['recaptcha_challenge_field']) && isset($_POST['recaptcha_response_field'])) {
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
    $thumbName = remove_file_extension ($_FILES["mo_thumbfile"]['name']);
    $ac3dName  = remove_file_extension ($_FILES["ac3d_file"]['name']);
    $xmlName   = remove_file_extension ($_FILES["xml_file"]['name']);
}
else {
    if ($_FILES["mo_thumbfile"]["name"] == "") {
        $fatalerror = true;
        $error++;
        $errormsg .= "<li>You must provide a thumbnail.</li>";
    }
    if (($_FILES["ac3d_file"]['name'] == "") && ($_FILES["xml_file"]['name'] == "")) {
        $fatalerror = true;
        $error++;
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

$tmp_dir = sys_get_temp_dir();

if (!preg_match($regex['ac3d_filename'], $ac3dName)
        || !preg_match($regex['xml_filename'], $xmlName)) {
    $fatalerror = true;
    $error++;
    $errormsg .= "<li>AC3D and XML name must used the following characters: 'a' to 'z', 'A' to 'Z', '0' to '9', '_', '.' or '_'</li>";
}

if ($thumbName == $ac3dName."_thumbnail" && !$fatalerror) {
    $targetPath   = $tmp_dir . "/static_".random_suffix()."/";
    while (file_exists($targetPath)) {
        usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
    }

    if (!mkdir($targetPath)) {
        $fatalerror = true;
        $error++;
        $errormsg .= "<li>Impossible to create temporary directory $targetPath</li>";
    }

    if ($ac3dName == $xmlName) {
        $xmlName    = $_FILES["xml_file"]['name'];
        $xmlPath    = $targetPath.$_FILES["xml_file"]['name'];
    }
    $thumbPath    = $targetPath.$_FILES["mo_thumbfile"]['name'];
    $ac3dPath     = $targetPath.$_FILES["ac3d_file"]['name'];
    $thumbName    = $_FILES["mo_thumbfile"]['name'];
    $ac3dName     = $_FILES["ac3d_file"]['name'];

    for ($i=0; $i<12; $i++) {
        if (isset($_FILES["png_file"]["name"][$i])) {
            if (!preg_match($regex['png_filename'], $_FILES["png_file"]["name"][$i])) {
                $fatalerror = true;
                $error++;
                $errormsg .= "<li>Textures' name must used the following characters: 'a' to 'z', 'A' to 'Z', '0' to '9', '_', '.' or '_'</li>";
            } else {
                $pngAllName[] = $_FILES["png_file"]["name"][$i];
            }
        }
    }
}
else if (!$fatalerror) {
    $fatalerror = true;
    $error++;
    $errormsg .= "<li>XML, AC and thumbnail file <u>must</u> share the same name. (i.e: tower.xml (if exists: currently ".$_FILES["xml_file"]['name']."), tower.ac (currently ".$ac3dName.".ac), tower_thumbnail.jpeg (currently ".$thumbName.".jpg/jpeg).</li>";
    if (substr($thumbName, -10) != "_thumbnail") {
        $errormsg .= "<li>The thumbnail file name must end with *_thumbnail.</li>";
    }
}


###############################################
###############################################
#                                             #
# STEP 3 : UPLOAD ALL FILES IN TMP DIRECTORY  #
#                                             #
###############################################
###############################################

# STEP 3.1 : UPLOAD THUMBNAIL FILE IN TMP DIRECTORY (Will be removed later on)
##############################################################################

if ($_FILES['mo_thumbfile']['size'] < 2000000 && !$fatalerror) { // check file size
    if ($_FILES['mo_thumbfile']['type'] == "image/jpeg" && (show_file_extension(basename($thumbName)) == "jpeg") || (show_file_extension(basename($thumbName)) == "JPEG") || (show_file_extension(basename($thumbName)) == "JPG") || (show_file_extension(basename($thumbName)) == "jpg")) { // check type & extension file
        if ($_FILES['mo_thumbfile']['error'] != 0) { // If an error is detected
            $error++;
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
                $error++;
                $errormsg .= "<li>There has been an error while moving the file \"".$thumbName."\" on the server.</li>";
            }
        }
    }
    else {
        $error++;
        $errormsg .= "<li>The file format or extention of your thumbnail file \"".$thumbName."\" seems to be wrong. Thumbnail needs to be a JPEG file.</li>";
    }
} else if (!$fatalerror) {
    $error++;
    $errormsg .= "<li>Sorry, but the size of your thumbnail file \"".$thumbName."\" exceeds 2Mb (current size: ".$_FILES['mo_thumbfile']['size']." bytes).</li>";
}

# STEP 3.2 : UPLOAD AC3D FILE IN TMP DIRECTORY
##############################################

if ($_FILES['ac3d_file']['size'] < 2000000 && !$fatalerror) { // check size file

    // check type & extension file
    if (($_FILES['ac3d_file']['type'] == "application/octet-stream" || $_FILES['ac3d_file']['type'] == "application/pkix-attr-cert")
            && strtolower(show_file_extension(basename($ac3dName))) == "ac") {

        if ($_FILES['ac3d_file']['error'] != 0) { // If error is detected
            $error++;
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
        else {
            if (!move_uploaded_file($_FILES['ac3d_file']['tmp_name'], $ac3dPath)) { // check upload file
                $fatalerror = true;
                $error++;
                $errormsg .= "<li>There has been an error while moving the file \"".$ac3dName."\" on the server.</li>";
            }
        }
    }
    else {
        $error++;
        $errormsg .= "<li>The format or the extention seems to be wrong for your AC3D file \"".$ac3dName."\". AC file needs to be a AC3D file.</li>";
    }
}
else if (!$fatalerror) {
    $error++;
    $errormsg .= "<li>Sorry, but the size of your AC3D file \"".$ac3dName."\" is over 2Mb (current size: ".$_FILES['ac3d_file']['size']." bytes).</li>";
}

# STEP 3.3 : UPLOAD XML FILE IN TMP DIRECTORY
#############################################

if ($_FILES['xml_file']['name'] != "") { // if file exists
    if ($_FILES['xml_file']['size'] < 2000000 && !$fatalerror) { // check size file
        if ($_FILES['xml_file']['type'] == "text/xml" && strtolower(show_file_extension(basename($xmlName))) == "xml") { // check type & extension file
            if ($_FILES['xml_file']['error'] != 0) { // If error is detected
                $error++;
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
            else {
                if(!move_uploaded_file($_FILES['xml_file']['tmp_name'], $xmlPath)) { // check uploaded file
                    $fatalerror = true;
                    $error++;
                    $errormsg .= "<li>There has been an error while moving the file \"".$xmlName."\" on the server.</li>";
                }
            }
        }
        else {
            $error++;
            $errormsg .= "<li>The format or extension of your XML file \"".$xmlName."\"seems to be wrong. XML file needs to be an XML file.</li>";
        }
    }
    else if (!$fatalerror) {
        $error++;
        $errormsg .= "<li>Sorry, but the size of your XML file \"".$xmlName."\" exceeds 2Mb (current size: ".$_FILES['xml_file']['size']." bytes).</li>";
    }
}

# STEP 3.4 : UPLOAD PNG FILE IN TMP DIRECTORY
#############################################

for ($i=0; $i<12; $i++) {
    if (isset($_FILES['png_file']['name'][$i]) && ($_FILES['png_file']['name'][$i] != '')) {
        $pngName  = $_FILES['png_file']['name'][$i];
        $pngType  = $_FILES['png_file']['type'][$i];
        $pngsize  = $_FILES['png_file']['size'][$i];
        $pngError = $_FILES['png_file']['error'][$i];
        $pngTmp   = $_FILES['png_file']['tmp_name'][$i];

        if ($pngsize < 2000000 && !$fatalerror) { // check size file

            if ($pngType == 'image/png' && strtolower(show_file_extension(basename($pngName))) == "png") { // check type & extension file

                if ($pngError != 0) { // If error is detected
                    $error++;
                    $errormsg .= "<li>There has been an error while uploading the file \"".$pngName."\".</li>";
                    switch ($_FILES['png_file']['error']) {
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
                    $error++;
                    $errormsg .= "<li>There has been an error while moving the file \"".$pngName."\" on the server.</li>";
                }
            }
            else {
                $error++;
                $errormsg .= "<li>The format or extension of your texture file \"".$pngName."\" seems to be wrong. Texture file needs to be a PNG file.</li>";
            }
        }
        else if(!$fatalerror) {
            $error++;
            $errormsg .= "<li>Sorry, but the size of your texture file \"".$pngName."\" exceeds 2Mb (current size: ".$pngsize." bytes).</li>";
        }
    }
}

######################################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS #
######################################################

if ($fatalerror || $error > 0) {
    echo "<h2>Oops, something went wrong</h2>" .
         "Number of error(s): ".$error."<br/>" .
         "FatalError        : ".($fatalerror ? "TRUE":"FALSE")."<br/>" .
         "Error message(s)  : <br/>" .
         "<ul>".$errormsg."</ul><br/>" .
         "<a href='javascript:history.go(-1)'>Go back and correct your mistakes</a>.<br/><br/>" .
         "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> " .
         "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";
    clear_dir($targetPath);
    include '../../inc/footer.php';
    exit;
}

###############################################
###############################################
#                                             #
# STEP 4 : CHECK XML FILE                     #
#                                             #
###############################################
###############################################

$path_to_use = $ac3dName;
if (isset($xmlPath) && file_exists($xmlPath)) {
    # If an XML file is used for the model, the mo_path has to point to it, or
    # FG will not render it correctly. Else the .ac file will be used as mo_path.
    $path_to_use = $xmlName;
    
    $depth = array();
    $xml_parser = xml_parser_create();

    function startElement($parser, $name, $attrs) {
        global $depth;
        $parserInt = intval($parser);
        if(!isset($depth[$parserInt])) {
            $depth[$parserInt] = 0;
        }
        $depth[$parserInt]++;
    }

    function endElement($parser, $name) {
        global $depth;
        $parserInt = intval($parser);
        if(!isset($depth[$parserInt])) {
            $depth[$parserInt] = 0;
        }
        $depth[$parserInt]--;
    }

    xml_set_element_handler($xml_parser, "startElement", "endElement");

    if (!($fp = fopen($xmlPath, "r"))) {
        $fatalerror = true;
        $error++;
        $errormsg .= "<li>Could not open XML \"".$xmlName."\"</li>";
    }
    else {
        while ($data = fread($fp, 4096)) {

        // check if tags are closed and if <PropertyList> is present
            if (!xml_parse($xml_parser, $data, feof($fp))) {
                $error++;
                $errormsg .= "<li>XML error : ".xml_error_string(xml_get_error_code($xml_parser))." at line ".xml_get_current_line_number($xml_parser)."</li>";
            }
        }
        xml_parser_free($xml_parser);
    }

    if ($error == 0) {
        // Check if <path> == $ac3dName
        $xmlcontent = simplexml_load_file($xmlPath);
        if ($ac3dName != $xmlcontent->path) {
            $error++;
            $errormsg .= "<li>The value of the &lt;path&gt; tag in your XML file doesn't match the AC file you provided!</li>";
        }

        // Check if the file begin with <?xml> tag
        $xmltag = str_replace(array("<", ">"), array("&lt;", "&gt;"), file_get_contents($xmlPath));
        if (!preg_match('#^&lt;\?xml version="1\.0" encoding="UTF-8" \?&gt;#i', $xmltag)) {
            $error++;
            $errormsg .= "<li>Your XML must start with &lt;?xml version=\"1.0\" encoding=\"UTF-8\" ?&gt;!</li>";
        }
    }
}

// Check if path is already used
if (path_exists($path_to_use)) {
    $error++;
    $errormsg .= "<li>Filename \"".$path_to_use."\" is already used</li>";
} else {
    echo "<p class=\"center\">Your model named ".$path_to_use."\n";
}

###############################################
###############################################
#                                             #
# STEP 5 : CHECK AC3D FILE                    #
#                                             #
###############################################
###############################################

if (file_exists($ac3dPath)) {
    $handle = fopen($ac3dPath, 'r');
    if ($handle) {
        $i = 0;
        while (!feof($handle)) {
            $line = fgets($handle);
            $line = rtrim($line, "\r\n") . PHP_EOL;

            // Check if the file begins with the string "AC3D"
            if ($i == 0 && substr($line,0,4) != "AC3D") {
                $error++;
                $errormsg .= "<li>The AC file does not seem to be a valid AC3D file. The first line must show \"AC3Dx\" with x = version</li>";
            }

            // Check if the texture reference matches $pngName
            if (preg_match('#^texture#', $line)) {
                $data = preg_replace('#texture "(.+)"$#', '$1', $line);
                $data = substr($data, 0, -1);
                if (!in_array($data, $pngAllName)) {
                    $error++;
                    $errormsg .= "<li>The texture reference (".$data.") in your AC file at line ".($i+1)." seems to have a different name than the PNG texture(s) file(s) name(s) you provided!</li>";
                }
            }
            $i++;
        }
        fclose($handle);
    }
}
else {
    $fatalerror = true;
    $error++;
    $errormsg .= "<li>The AC file does not exist on the server. Please try to upload it again!</li>";
}

###############################################
###############################################
#                                             #
# STEP 6 : CHECK TEXTURE FILE(S)              #
#                                             #
###############################################
###############################################

for ($i=0; $i<12; $i++) {
    if (isset($_FILES["png_file"]["name"][$i]) && ($_FILES['png_file']['name'][$i] != '')) {
        $pngPath  = $targetPath.$_FILES["png_file"]["name"][$i];
        $pngName  = $_FILES["png_file"]["name"][$i];

        if (file_exists($pngPath)) {
            $tmp    = getimagesize($pngPath);
            $width  = $tmp[0];
            $height = $tmp[1];
            $mime   = $tmp["mime"];
            $validDimension = array(1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024, 2048, 4096, 8192);

            // Check if PNG file is a valid PNG file (compare the type file)
            if ($mime != "image/png") {
                $error++;
                $errormsg .= "<li>Your texture file does not seem to be a PNG file. Please upload a valid PNG file.</li>";
            }

            // Check if PNG dimensions are a multiple of ^2
            if(!in_array($height, $validDimension) || !in_array($width, $validDimension)) {
                $error++;
                $errormsg .= "<li>The size in pixels of your texture file (".$pngName.") appears not to be a power of 2.</li>";
            }
        }
        else {
            $fatalerror = true;
            $error++;
            $errormsg .= "<li>The texture file does not exist on the server. Please try to upload it again.</li>";
        }
    }
}

###############################################
###############################################
#                                             #
# STEP 7 : CHECK THUMBNAIL FILE               #
#                                             #
###############################################
###############################################

if (file_exists($thumbPath)) {
    $tmp    = getimagesize($thumbPath);
    $width  = $tmp[0];
    $height = $tmp[1];
    $mime   = $tmp["mime"];

    // Check if JPEG file is a valid JPEG file (compare the type file)
    if ($mime != "image/jpeg") {
        $error++;
        $errormsg .= "<li>Your thumbnail file does not seem to be a JPEG file. Please upload a valid JPEG file.</li>";
    }

    // Check if PNG dimensions are a multiple of ^2
    if ($height != 240 || $width != 320) {
        $error++;
        $errormsg .= "<li>The dimension in pixels of your thumbnail file (".$width."x".$height.") does not seem to be 320x240.</li>";
    }
}
else {
    $fatalerror = true;
    $error++;
    $errormsg .= "<li>The thumbnail file does not exist on the server. Please try to upload it again.</li>";
}

####################################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS
####################################################

if ($fatalerror || $error > 0) {
    echo "<h2>Oops, something went wrong</h2>" .
         "Number of error(s): ".$error."<br/>" .
         "FatalError        : ".($fatalerror ? "TRUE":"FALSE")."<br/>" .
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
    unlink ($thumbPath);                               // Has to be deleted, because it's not put into the .tar.gz

    // Dos2unix on XML
    if (isset($xmlPath)) {
        $d2u_xml_command  = 'dos2unix '.$xmlPath;
        system ($d2u_xml_command);
    }
    
    // Dos2Unix on AC3D
    $d2u_ac3d_command = 'dos2unix '.$ac3dPath;
    system ($d2u_ac3d_command);

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
# STEP 9 : CHECK GEOGRAPHICAL INFORMATION     #
#                                             #
###############################################
###############################################

if ($_POST["longitude"] != "" && $_POST["latitude"] != "" && $_POST["offset"] != "" && $_POST["heading"] != "") {
    $longitude = strip_tags($_POST["longitude"]);
    $latitude  = strip_tags($_POST["latitude"]);
    $offset    = strip_tags($_POST["offset"]);
    $heading   = strip_tags($_POST["heading"]);

    if (!is_longitude($longitude)) {
        $error++;
        $errormsg .= "<li>Please check the longitude value (-180 < longitude < 180) and not null.</li>";
    }

    if (!is_latitude($latitude)) {
        $error++;
        $errormsg .= "<li>Please check the latitude value (-90 < latitude < 90) and not null.</li>";
    }

    if ($offset == '' || $offset == '0') {
        $offset = "NULL";
    } else if (!is_offset($offset)) {
        $error++;
        $errormsg .= "<li>Please check the offset value (-10000 < offset < 10000).</li>";
    }

    if (!is_heading($heading)) {
        $error++;
        $errormsg .= "<li>Please check the heading value (0 < heading < 359.999).</li>";
    }
}
else {
    $error++;
    $errormsg .= "<li>Please fill in all required fields.</li>";
}

###############################################
###############################################
#                                             #
# STEP 10 : CHECK GENERAL INFORMATION         #
#                                             #
###############################################
###############################################

if ($_POST["mo_shared"] != "" && $_POST["mo_author"] != ""
        && $_POST["ob_country"] != "" && $_POST["mo_name"] != ""
        && isset($_POST['notes'])) {

    $path        = remove_file_extension($ac3dName); //addslashes(htmlentities(strip_tags($_POST["mo_path"]), ENT_QUOTES));
    $name        = addslashes(htmlentities(strip_tags($_POST["mo_name"]), ENT_QUOTES));
    $notes       = addslashes(htmlentities(strip_tags($_POST["notes"]), ENT_QUOTES));
    $mo_shared   = $_POST["mo_shared"];
    $authorId    = $_POST["mo_author"];
    $country     = $_POST["ob_country"];
    $ipaddr      = $_SERVER["REMOTE_ADDR"];

    // This is only used for shared objects.
    // Reconstructing the parameters the model_exists function is waiting for, based on the path.
    if ($mo_shared != 0 && model_exists('Models/'.$modelDaoRO->getModelsGroup($mo_shared)->getPath().$path) != 2) {
        $error++;
        $errormsg .= "<li>It seems that your model already exists in our database. If you want to update it, please use our lovely update script for 3D models (to come).</li>";
    }

    if (!preg_match($regex['authorid'], $authorId)) {
        $error++;
        $errormsg .= "<li>Please check the author value.</li>";
    }

    if (!preg_match($regex['countryid'], $country)) {
        $error++;
        $errormsg .= "<li>Please check the country value.</li>";
    }

}
else {
    $error++;
    $errormsg .= "<li>Please fill in all required fields.</li>";
}

if (!isset($_POST["gpl"])) {
    $error++;
    $errormsg .= "<li>You did not accept the GNU GENERAL PUBLIC LICENSE Version 2, June 1991. As all the models shipped with FlightGear must wear this license, your contribution can't be accepted in our database. Please try to find GPLed textures and/or data.</li>";
}

###############################################
###############################################
#                                             #
# STEP 11 : RESUME AND SUBMIT VALIDATION      #
#                                             #
###############################################
###############################################

if ($fatalerror || $error > 0) {
    echo "<h2>Oops, something went wrong</h2>" .
         "Number of error(s): ".$error."<br/>" .
         "FatalError        : ".($fatalerror ? "TRUE":"FALSE")."<br/>" .
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
    # Connection to DB
    $resource_rw = connect_sphere_rw();
    
    $modelFactory = new ModelFactory($modelDaoRO, $authorDaoRO);
    $objectFactory = new ObjectFactory($objectDaoRO);
    $newModelMD = $modelFactory->createModelMetadata(-1, $authorId, $path_to_use, $name, $notes, $mo_shared);
    $newObject = $objectFactory->createObject(-1, -1, $longitude, $latitude, $country, 
            $offset, heading_stg_to_true($heading), 1, "");

    $mo_query  = "INSERT INTO fgs_models ";
    $mo_query .= "(mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared) ";
    $mo_query .= "VALUES (";
    $mo_query .= "DEFAULT, ";             // mo_id
    $mo_query .= "'".$path_to_use."', ";  // mo_path
    $mo_query .= $authorId.", ";          // mo_author
    $mo_query .= "'".$name."', ";         // mo_name
    $mo_query .= "'".$notes."', ";        // mo_notes
    $mo_query .= "'".$thumbFile."', ";    // mo_thumbfile
    $mo_query .= "'".$modelFile."', ";    // mo_modelfile
    $mo_query .= $mo_shared;              // mo_shared
    $mo_query .= ") ";
    $mo_query .= "RETURNING mo_id";
    
    # Inserts into fgs_models and returns current mo_id
    $ob_model = 'Thisisthevalueformo_id';

    if ($offset != '') {
        $ob_query  = "INSERT INTO fgs_objects ";
        $ob_query .= "(wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_country, ob_model, ob_group) ";
        $ob_query .= "VALUES (";
        $ob_query .= "ST_PointFromText('POINT(".$longitude." ".$latitude.")', 4326), ";        // wkb_geometry
        $ob_query .= "-9999, ";                                                                // ob_gndelev
        $ob_query .= $offset.", ";                                                             // ob_elevoffset
        $ob_query .= heading_stg_to_true($heading).", ";                                       // ob_heading
        $ob_query .= "'".$country."', ";                                                       // ob_country
        $ob_query .= $ob_model.", ";                                                           // ob_model
        $ob_query .= "1";                                                                       // ob_group
        $ob_query .= ")";
    }
    else {
        $ob_query  = "INSERT INTO fgs_objects ";
        $ob_query .= "(wkb_geometry, ob_gndelev, ob_heading, ob_country, ob_model, ob_group) ";
        $ob_query .= "VALUES (";
        $ob_query .= "ST_PointFromText('POINT(".$longitude." ".$latitude.")', 4326), ";        // wkb_geometry
        $ob_query .= "-9999, ";                                                                // ob_gndelev
        $ob_query .= heading_stg_to_true($heading).", ";                                       // ob_heading
        $ob_query .= "'".$country."', ";                                                       // ob_country
        $ob_query .= $ob_model." ,";                                                           // ob_model
        $ob_query .= "1";
        $ob_query .= ")";
    }
    $final_query = $mo_query.";".$ob_query;

    // Model and object stuff into pending requests table.
    $mo_sha_to_compute = "<".microtime()."><".$ipaddr."><".$final_query.">";
    $mo_sha_hash = hash('sha256', $mo_sha_to_compute);
    $mo_zipped_base64_rw_query = gzcompress($final_query, 8);                      // Zipping the Base64'd request.
    $mo_base64_rw_query = base64_encode($mo_zipped_base64_rw_query);               // Coding in Base64.
    $mo_query_rw_pending_request = "INSERT INTO fgs_position_requests (spr_hash, spr_base64_sqlz) VALUES ('".$mo_sha_hash."', '".$mo_base64_rw_query."');";
    $resultrw = pg_query($resource_rw, $mo_query_rw_pending_request);             // Sending the request...

    pg_close($resource_rw);                                                       // Closing the connection.

    if (!$resultrw) {
        echo "<p class=\"center\">Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.</p><br />";
    }
    else {
        $failed_mail = false;
        $au_email = $newModelMD->getAuthor()->getEmail();
        if ($au_email != '' && strlen($au_email) > 0) {
            $safe_au_email = htmlentities(stripslashes($au_email));
        }
        else {
            $failed_mail = true;
        }
        echo "has been successfully queued into the FG scenery database insertion requests!<br />";
        echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
        echo "The FG community would like to thank you for your contribution!<br />";
        echo "Want to submit another model or position?<br /> <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/\">Click here to go back to the submission page.</a></p>";

        // Sending mail if there is no false and SQL was correctly inserted.
        date_default_timezone_set('UTC');                                // Sets the time to UTC.
        $dtg = date('l jS \of F Y h:i:s A');
        $ipaddr = stripslashes($ipaddr);                                 // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
        $host = gethostbyaddr($ipaddr);
        
        $emailSubmit = EmailContentFactory::getAddModelRequestPendingEmailContent($dtg, $ipaddr, $host, $safe_au_email, $newObject, $newModelMD, $mo_sha_hash);
        $emailSubmit->sendEmail("", true);
        
        // Mailing the submitter to tell him that his submission has been sent for validation
        if (!$failed_mail) {
            $emailSubmit = EmailContentFactory::getAddModelRequestSentForValidationEmailContent($dtg, $ipaddr, $host, $mo_sha_hash, $newModelMD, $newObject);
            $emailSubmit->sendEmail($safe_au_email, false);
        }
    }
}
require '../../inc/footer.php';
?>