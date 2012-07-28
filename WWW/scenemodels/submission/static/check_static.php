<?php

# Inserting libs
require_once('../../inc/functions.inc.php');
require_once('../../inc/captcha/recaptchalib.php');

$fatalerror = 0;
$error      = 0;
$errormsg   = "";

/*
    // Private key is needed for the server-to-Google auth.
    $privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
    $resp = recaptcha_check_answer ($privatekey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);

    // What happens when the CAPTCHA was entered incorrectly
    if (!$resp->is_valid) {
        $page_title = "Automated Shared Models Positions Update Form";
        $error_text = "<br/>Sorry but the reCAPTCHA wasn't entered correctly. <a href='http://".$_SERVER['SERVER_NAME']."/submission/static/index.php'>Go back and try it again</a>" .
             "<br />(reCAPTCHA complained: " . $resp->error . ")<br />" .
             "Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href=\"http://en.wikipedia.org/wiki/Captcha\">here</a>.";
        include '../../inc/error_page.php';
        exit;
    }
*/

$page_title = "Automated Models Submission Form";
include '../../inc/header.php';

################################################
################################################
#                                              #
# STEP 1 : CHECK IF ALL FILES WERE RECEIVED    #
#                                              #
################################################
################################################

if (($_FILES["mo_thumbfile"]['name'] != "") && ($_FILES["ac3d_file"]['name'] != "")) {
    $thumbName = remove_file_extension ($_FILES["mo_thumbfile"]['name']);
    $ac3dName  = remove_file_extension ($_FILES["ac3d_file"]['name']);
    $xmlName   = remove_file_extension ($_FILES["xml_file"]['name']);

}
else {
    $fatalerror = 1;
    $error += 1;
    $errormsg .= "You <u>must</u> provide at least 1 thumbnail and 1 AC file!<br/>";
}

###########################################################
###########################################################
#                                                         #
# STEP 2 : CHECK IF ALL FILES MATCH THE NAMING CONVENTION #
#                                                         #
###########################################################
###########################################################

$tmp_dir = sys_get_temp_dir;

if ($thumbName == $ac3dName."_thumbnail" && !$fatalerror) {
    $targetPath   = $tmp_dir . "/static_".random_suffix()."/";
    while (file_exists($targetPath)) {
        usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
    }

    if (!mkdir($targetPath)) {
        $fatalerror = 1;
        $error += 1;
        $errormsg .= "Impossible to create temporary directory!";
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
        if(isset($_FILES["png_file"]["name"][$i])) {
            $pngAllName[] = $_FILES["png_file"]["name"][$i];
        }
    }
}
else {
    if (!$fatalerror) {
        $fatalerror = 1;
        $error += 1;
        $errormsg .= "XML, AC and thumbnail file <u>must</u> share the same name. (i.e: tower.xml (currently ".$xmlName."), tower.ac (currently ".$ac3dName."), tower_thumbnail.jpeg (currently ".$thumbName.")!<br/>";
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
            $error += 1;
            $errormsg .= "There has been an error while uploading the file \"".$thumbName."\"!<br/>";
            switch ($_FILES['mo_thumbfile']['error']) {
                case 1:
                    $errormsg .= "The file \"".$thumbName."\" is bigger than this server installation allows!<br/>";
                    break;
                case 2:
                    $errormsg .= "The file \"".$thumbName."\" is bigger than this form allows!<br/>";
                    break;
                case 3:
                    $errormsg .= "Only part of the file \"".$thumbName."\" was uploaded!<br/>";
                    break;
                case 4:
                    $errormsg .= "No file \"".$thumbName."\" was uploaded!<br/>";
                    break;
            }
        }
        else {
            if (!move_uploaded_file($_FILES['mo_thumbfile']['tmp_name'], $thumbPath)) { // check uploaded file
                $fatalerror = 1;
                $error += 1;
                $errormsg .= "There has been an error while moving the file \"".$thumbName."\" on the server!<br/>";
            }
        }
    }
    else {
        $error += 1;
        $errormsg .= "The file format or extention of your thumbnail file \"".$thumbName."\" seems to be wrong. Thumbnail needs to be a JPEG file!<br/>";
    }
} else {
    if (!$fatalerror) {
        $error += 1;
        $errormsg .= "Sorry, but the size of your thumbnail file \"".$thumbName."\" exceeds 2Mb (current size: ".$_FILES['mo_thumbfile']['size']." bytes)!<br/>";
    }
}

# STEP 3.2 : UPLOAD AC3D FILE IN TMP DIRECTORY
##############################################

if ($_FILES['ac3d_file']['size'] < 2000000 && !$fatalerror) { // check size file

    if ($_FILES['ac3d_file']['type'] == "application/octet-stream" && (show_file_extension(basename($ac3dName)) == "ac" || show_file_extension(basename($ac3dName)) == "AC")) { // check type & extension file

        if ($_FILES['ac3d_file']['error'] != 0) { // If error is detected
            $error += 1;
            $errormsg .= "There has been an error while uploading the file \"".$ac3dName."\"!<br/>";
            switch ($_FILES['ac3d_file']['error']){
                case 1:
                    $errormsg .= "The file \"".$ac3dName."\" is bigger than this server installation allows!<br/>";
                    break;
                case 2:
                    $errormsg .= "The file \"".$ac3dName."\" is bigger than this form allows!<br/>";
                    break;
                case 3:
                    $errormsg .= "Only part of the file \"".$ac3dName."\" was uploaded!<br/>";
                    break;
                case 4:
                    $errormsg .= "No file \"".$ac3dName."\" was uploaded!<br/>";
                    break;
            }
        }
        else {
            if (!move_uploaded_file($_FILES['ac3d_file']['tmp_name'], $ac3dPath)) { // check upload file
                $fatalerror = 1;
                $error += 1;
                $errormsg .= "There has been an error while moving the file \"".$ac3dName."\" on the server!<br/>";
            }
        }
    }
    else {
        $error += 1;
        $errormsg .= "The format or the extention seems to be wrong for your AC3D file \"".$ac3dName."\". AC file needs to be a AC3D file<br/>";
    }
}
else {
    if (!$fatalerror) {
        $error += 1;
        $errormsg .= "Sorry, but size of your AC3D file \"".$ac3dName."\" is over 2Mb (current size: ".$_FILES['ac3d_file']['size']." bytes).<br/>";
    }
}

# STEP 3.3 : UPLOAD XML FILE IN TMP DIRECTORY
#############################################

if ($_FILES['xml_file']['name'] != "") { // if file exists
    if ($_FILES['xml_file']['size'] < 2000000 && !$fatalerror) { // check size file
        if ($_FILES['xml_file']['type'] == "text/xml" && (show_file_extension(basename($xmlName)) == "xml" || show_file_extension(basename($xmlName)) == "XML")) { // check type & extension file
            if ($_FILES['xml_file']['error'] != 0) { // If error is detected
                $error += 1;
                $errormsg .= "There has been an error while uploading the file \"".$xmlName."\"!<br/>";
                switch ($_FILES['xml_file']['error']) {
                    case 1:
                        $errormsg .= "The file \"".$xmlName."\" is bigger than this server installation allows!<br/>";
                        break;
                    case 2:
                        $errormsg .= "The file \"".$xmlName."\" is bigger than this form allows!<br/>";
                        break;
                    case 3:
                        $errormsg .= "Only part of the file \"".$xmlName."\" was uploaded!<br/>";
                        break;
                    case 4:
                        $errormsg .= "No file \"".$xmlName."\" was uploaded!<br/>";
                        break;
                }
            }
            else {
                if(!move_uploaded_file($_FILES['xml_file']['tmp_name'], $xmlPath)) { // check uploaded file
                    $fatalerror = 1;
                    $error += 1;
                    $errormsg .= "There has been an error while moving the file \"".$xmlName."\" on the server!<br/>";
                }
            }
        }
        else {
            $error += 1;
            $errormsg .= "The format or extension of your XML file \"".$xmlName."\"seems to be wrong. XML file needs to be an XML file!<br/>";
        }
    }
    else {
        if (!$fatalerror) {
            $error += 1;
            $errormsg .= "Sorry, but the size of your XML file \"".$xmlName."\" exceeds 2Mb (current size: ".$_FILES['xml_file']['size']." bytes)!<br/>";
        }
    }
}

# STEP 3.4 : UPLOAD PNG FILE IN TMP DIRECTORY
#############################################

for ($i=0; $i<12; $i++) {
    if (isset($_FILES["png_file"]["name"][$i])) {
        $pngName  = $_FILES["png_file"]["name"][$i];
        $pngType  = $_FILES["png_file"]["type"][$i];
        $pngsize  = $_FILES["png_file"]["size"][$i];
        $pngError = $_FILES["png_file"]["error"][$i];
        $pngTmp   = $_FILES["png_file"]["tmp_name"][$i];

        if ($pngsize < 2000000 && !$fatalerror) { // check size file

            if ($pngType == 'image/png' && (show_file_extension(basename($pngName)) == "png" || show_file_extension(basename($pngName)) == "PNG")) { // check type & extension file

                if ($pngError != 0) { // If error is detected
                    $error += 1;
                    $errormsg .= "There has been an error while uploading the file \"".$pngName."\"!<br/>";
                    switch ($_FILES['png_file']['error']) {
                        case 1:
                            $errormsg .= "The file \"".$pngName."\" is bigger than this server installation allows!<br/>";
                            break;
                        case 2:
                            $errormsg .= "The file \"".$pngName."\" is bigger than this form allows!<br/>";
                            break;
                        case 3:
                            $errormsg .= "Only part of the file \"".$pngName."\" was uploaded!<br/>";
                            break;
                        case 4:
                            $errormsg .= "No file \"".$pngName."\" was uploaded!<br/>";
                            break;
                    }
                }
                else {
                    if (!move_uploaded_file($pngTmp, $targetPath.$pngName)){ // check uploaded file
                        $fatalerror = 1;
                        $error += 1;
                        $errormsg .= "There has been an error while moving the file \"".$pngName."\" on the server!<br/>";
                    }
                }
            }
            else {
                $error += 1;
                $errormsg .= "The format or extension of your texture file \"".$pngName."\" seems to be wrong. Texture file needs to be a PNG file!<br/>";
            }
        }
        else {
            if(!$fatalerror) {
                $error += 1;
                $errormsg .= "Sorry, but the size of your texture file \"".$pngName."\" exceeds 2Mb (current size: ".$pngsize." bytes)!<br/>";
            }
        }
    }
}

######################################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS #
######################################################

if ($fatalerror || $error > 0) {
    echo "Number of error(s): ".$error."<br/>";
    echo "FatalError        : ".($fatalerror ? "TRUE":"FALSE")."<br/>";
    echo "Error message(s)  : <br/>".$errormsg."<br/><br/><br/>";
    echo "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> ";
    echo "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";
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

if (file_exists($xmlPath)) {
    $depth = array();
    $xml_parser = xml_parser_create();

    function startElement($parser, $name, $attrs) {
        global $depth;
        $parserInt = intval($parser);
        if(!isset($depth[$parserInt]))
            $depth[$parserInt]=0;
        $depth[$parserInt]++;
    }

    function endElement($parser, $name) {
        global $depth;
        $parserInt = intval($parser);
        if(!isset($depth[$parserInt]))
            $depth[$parserInt]=0;
        $depth[$parserInt]--;
    }

    xml_set_element_handler($xml_parser, "startElement", "endElement");

    if (!($fp = fopen($xmlPath, "r"))) {
        $fatalerror = 1;
        $error += 1;
        $errormsg .= "Could not open XML \"".$xmlName."\"";
    }
    else {
        while ($data = fread($fp, 4096)) {

        // check if tags are closed and if <PropertyList> is present
            if (!xml_parse($xml_parser, $data, feof($fp))) {
                $error += 1;
                $errormsg .= "XML error : ".xml_error_string(xml_get_error_code($xml_parser))." at line ".xml_get_current_line_number($xml_parser)."<br/>";
            }
        }
        xml_parser_free($xml_parser);
    }

    if(!$error > 0) {

        // Check if <path> == $ac3dName
        $xmlcontent = simplexml_load_file($xmlPath);
        if($ac3dName != $xmlcontent->path) {
            $error += 1;
            $errormsg .= "The value of the &lt;path&gt; tag in your XML file doesn't match the AC file you provided!<br/>";
        }

        // Check if the file begin with <?xml> tag
        $xmltag = str_replace(array("<", ">"), array("&lt;", "&gt;"), file_get_contents($xmlPath));
        if(!preg_match('#^&lt;\?xml version="1\.0" encoding="UTF-8" \?&gt;#i', $xmltag)) {
            $error += 1;
            $errormsg .= "Your XML must start with &lt;?xml version=\"1.0\" encoding=\"UTF-8\" ?&gt;!<br/>";
        }
    }
}
else {
/*  $fatalerror = 1;
  $error += 1;
  $errormsg .= "The XML file doesn't exist on the server. Please try to upload it again!<br/>";*/
}

###############################################
###############################################
#                                             #
# STEP 5 : CHECK AC3D FILE                    #
#                                             #
###############################################
###############################################

if (file_exists($ac3dPath)) {
    if ($handle = fopen($ac3dPath, 'r')) {
        $i = 0;
        while (!feof($handle)) {
            $line = fgets($handle);
            $line = rtrim($line, "\r\n") . PHP_EOL;

            // Check if the file begins with the string "AC3D"
            if ($i == 0) {
                if (substr($line,0,4) != "AC3D") {
                    $error += 1;
                    $errormsg .= "The AC file doesn't seem to be a valid AC3D file. The first line needs to show \"AC3Dx\" with x = version<br/>";
                }
            }

            // Check if the texture reference matches $pngName
            if (preg_match('#^texture#', $line)) {
                $data = preg_replace('#texture "(.+)"$#', '$1', $line);
                $data = substr($data, 0, -1);
                if (!in_array($data, $pngAllName)) {
                    $error += 1;
                    $errormsg .= "The texture reference (".$data.") in your AC file at line ".($i+1)." seems to have a different name of the PNG texture(s) file(s) name(s) your provided!<br/>";
                }
            }
            $i++;
        }
        fclose($handle);
    }
}
else {
    $fatalerror = 1;
    $error += 1;
    $errormsg .= "The AC file doesn't exist on the server. Please try to upload it again!<br/>";
}

###############################################
###############################################
#                                             #
# STEP 6 : CHECK TEXTURE FILE(S)              #
#                                             #
###############################################
###############################################

for ($i=0; $i<12; $i++) {
    if (isset($_FILES["png_file"]["name"][$i])) {
        $pngPath  = $targetPath.$_FILES["png_file"]["name"][$i];
        $pngName  = $_FILES["png_file"]["name"][$i];

        if (file_exists($pngPath)){
            $tmp    = getimagesize($pngPath);
            $width  = $tmp[0];
            $height = $tmp[1];
            $mime   = $tmp["mime"];
            $validDimension = array(1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024, 2048, 4096, 8192);

            // Check if PNG file is a valid PNG file (compare the type file)
            if ($mime != "image/png") {
                $error += 1;
                $errormsg .= "Your texture file doesn't seem to be a PNG file. Please upload a valid PNG file!<br/>";
            }

            // Check if PNG dimensions are a multiple of ^2
            if(!in_array($height, $validDimension) || !in_array($width, $validDimension)) {
                $error += 1;
                $errormsg .= "The size in pixels of your texture file (".$pngName.") appears not to be a power of 2!<br/>";
            }
        }
        else {
            $fatalerror = 1;
            $error += 1;
            $errormsg .= "The texture file doesn't exist on the server. Please try to upload it again!<br/>";
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
        $error += 1;
        $errormsg .= "Your thumbnail file doesn't seem to be a JPEG file. Please upload a valid JPEG file!<br/>";
    }

    // Check if PNG dimensions are a multiple of ^2
    if ($height != 240 || $width != 320) {
        $error += 1;
        $errormsg .= "The dimension in pixels of your thumbnail file (".$width."x".$height.") doesn't seem to be 320x240!<br/>";
    }
}
else {
    $fatalerror = 1;
    $error += 1;
    $errormsg .= "The thumbnail file doesn't exist on the server. Please try to upload it again!<br/>";
}

####################################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS
####################################################

if ($fatalerror || $error > 0) {
    echo "<p span=\"center\">";
    echo "Number of error(s): ".$error."<br/>";
    echo "FatalError        : ".($fatalerror ? "TRUE":"FALSE")."<br/>";
    echo "Error message(s)  : ".$errormsg."<br/>";
    echo "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> ";
    echo "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!</p>";
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
    unlink ($thumbPath);                                // Has to be deleted, because it's not put into the .tar.gz

    $d2u_xml_command  = 'dos2unix '.$xmlPath;          // Dos2unix on XML
    $d2u_ac3d_command = 'dos2unix '.$ac3dPath;         // Dos2Unix on AC3D
    system ($d2u_xml_command);
    system ($d2u_xml_command);

    $phar = new PharData($tmp_dir . '/static.tar');           // Create archive file
    $phar->buildFromDirectory($targetPath);            // Fills archive file
    $phar->compress(Phar::GZ);                         // Convert archive file to compress file
    unlink($tmp_dir . '/static.tar');                         // Delete archive file
    rename($tmp_dir . '/static.tar.gz', $tmp_dir.'/static.tgz');   // Rename compress file

    $handle    = fopen($tmp_dir."/static.tgz", "r");
    $contents  = fread($handle, filesize($tmp_dir."/static.tgz"));
    fclose($handle);
    $modelFile = base64_encode($contents);             // Dump & encode the file

    unlink($tmp_dir . '/static.tgz');                         // Delete compress file
    clear_dir($targetPath);                             // Delete temporary static directory
}

###############################################
###############################################
#                                             #
# STEP 9 : CHECK GEOGRAPHICAL INFORMATION     #
#                                             #
###############################################
###############################################

if (($_POST["longitude"] != "") && ($_POST["latitude"] != "") && ($_POST["gndelev"] != "") && ($_POST["offset"] != "") && ($_POST["heading"] != "")) {
    $longitude = strip_tags($_POST["longitude"]);
    $latitude  = strip_tags($_POST["latitude"]);
    $gndelev   = strip_tags($_POST["gndelev"]);
    $offset    = strip_tags($_POST["offset"]);
    $heading   = strip_tags($_POST["heading"]);

    if (preg_match('#[a-zA-Z ]#', $longitude) || ($longitude < -180 || $longitude > 180)) {
        $error += 1;
        $errormsg .= "Please check the longitude value (-180 < longitude < 180)!<br/>";
    }

    if (preg_match('#[a-zA-Z ]#', $latitude) || ($latitude < -90 || $latitude > 90)) {
        $error += 1;
        $errormsg .= "Please check the latitude value (-90 < latitude < 90)!<br/>";
    }

    if (preg_match('#[a-zA-Z ]#', $gndelev) || ($gndelev < -10000 || $gndelev > 10000)) {
        $error += 1;
        $errormsg .= "Please check the ground elevation value (-10000 < ground elevation < 10000)!<br/>";
    }

    if (preg_match('#[a-zA-Z ]#', $offset) || ($offset < -10000 || $offset > 10000)) {
        $error += 1;
        $errormsg .= "Please check the offset value (-10000 < offset < 10000)!<br/>";
    }

    if (preg_match('#[a-zA-Z ]#', $heading) || ($heading < 0 || $heading > 359.999)) {
        $error += 1;
        $errormsg .= "Please check the heading value (0 < heading < 359.999)!<br/>";
    }
}
else {
    $error += 1;
    $errormsg .= "Please fill in all required fields!<br/>";
}

###############################################
###############################################
#                                             #
# STEP 10 : CHECK GENERAL INFORMATION         #
#                                             #
###############################################
###############################################

if (($_POST["mo_shared"] != "") && ($_POST["mo_author"] != "")
    && ($_POST["ob_country"] != "") && ($_POST["mo_name"] != "") && ($_POST["IPAddr"] != "")
    && isset($_POST['comment']) && isset($_POST['contributor'])) {

        $path        = remove_file_extension ($ac3dName); //addslashes(htmlentities(strip_tags($_POST["mo_path"]), ENT_QUOTES));
        $name        = addslashes(htmlentities(strip_tags($_POST["mo_name"]), ENT_QUOTES));
        $comment     = addslashes(htmlentities(strip_tags($_POST["comment"]), ENT_QUOTES));
        $contributor = addslashes(htmlentities(strip_tags($_POST["contributor"]), ENT_QUOTES));
        $mo_shared   = $_POST["mo_shared"];
        $author      = $_POST["mo_author"];
        $country     = $_POST["ob_country"];
        $ipaddr      = $_POST["IPAddr"];

    if ($mo_shared != 0) { // This is only used for shared objects.
        if (model_exists('Models/'.family_name($mo_shared).'/'.$path) != 2) { // Reconstructing the parameters the model_exists function is waiting for, based on the path.
            $error += 1;
            $errormsg .= "It seems that your model already exists in our database! If you want to update it, please use our lovely update script for 3D models (to come).<br/>";
        }
    }

    if (!preg_match('#^[0-9]{1,3}$#', $author)) {
        $error += 1;
        $errormsg .= "Please check the author value!<br/>";
    }

    if (!preg_match('#^[a-zA-Z]{1,3}$#', $country)) {
        $error += 1;
        $errormsg .= "Please check the country value!<br/>";
    }

}
else {
    $error += 1;
    $errormsg .= "Please fill in all required fields!<br/>";
}

if (!isset($_POST["gpl"])) {
    $error += 1;
    $errormsg .= "You did not accept the GNU GENERAL PUBLIC LICENSE Version 2, June 1991. As all the models shipped with FG must wear this license, your contribution can't be accepted in our database. Please try to find GPLed textures and/or data.<br/>";
}

###############################################
###############################################
#                                             #
# STEP 11 : RESUME AND SUBMIT VALIDATION      #
#                                             #
###############################################
###############################################

if ($fatalerror || $error > 0) {
    echo "<p span=\"center\">";
    echo "Number of error(s): ".$error."<br/>";
    echo "FatalError        : ".($fatalerror ? "TRUE":"FALSE")."<br/>";
    echo "Error message(s)  : <br/>".$errormsg."<br/><br/><br/>";
    echo "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> ";
    echo "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!</p>";
    include '../../inc/footer.php';
    clear_dir($targetPath);
    exit;
}
else {
    # Connection to DB
    $resource_rw = connect_sphere_rw();
    $mo_query  = "INSERT INTO fgsoj_models ";
    $mo_query .= "(mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared) ";
    $mo_query .= "VALUES (";
    $mo_query .= "DEFAULT, ";             // mo_id
    $mo_query .= "'".$path."', ";         // mo_path
    $mo_query .= "'".$author."', ";       // mo_author
    $mo_query .= "'".$name."', ";         // mo_name
    $mo_query .= "'".$comment."', ";      // mo_notes
    $mo_query .= "'".$thumbFile."', ";    // mo_thumbfile
    $mo_query .= "'".$modelFile."', ";    // mo_modelfile
    $mo_query .= "'".$mo_shared."'";      // mo_shared
    $mo_query .= ") ";
    $mo_query .= "RETURNING mo_id";

    # Inserts into fgsoj_models and returns current mo_id
    $ob_model = 'Thisisthevalueformo_id';

    $ob_query  = "INSERT INTO fgsoj_objects ";
//  $ob_query .= "(ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_country, ob_model, ob_group, ob_submitter) ";
    $ob_query .= "(ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_group) ";
    $ob_query .= "VALUES (";
    $ob_query .= "'', ";                                                                   // ob_text - to be inserted later to ease deserialization
    $ob_query .= "ST_PointFromText('POINT(".$longitude." ".$latitude.")', 4326), ";        // wkb_geometry
    $ob_query .= "'".$gndelev."', ";                                                       // ob_gndelev
    $ob_query .= "'".$offset."', ";                                                        // ob_elevoffset
    $ob_query .= "'".heading_stg_to_true($heading)."', ";                                  // ob_heading
//    $ob_query .= "'".$country."', ";                                                     // ob_country (Only if static)
    $ob_query .= "'".$ob_model."', ";                                                      // ob_model
    $ob_query .= "'1'";                                                                    // ob_group
//    $ob_query .= "'".$contributor."'";                                                   // ob_submitter
    $ob_query .= ")";

    // Object Stuff into pending requests table.
    $ob_sha_to_compute = "<".microtime()."><".$ipaddr."><".$ob_query.">";
    $ob_sha_hash = hash('sha256', $ob_sha_to_compute);
    $ob_zipped_base64_rw_query = gzcompress($ob_query, 8);                       // Zipping the Base64'd request.
    $ob_base64_rw_query = base64_encode($ob_zipped_base64_rw_query);               // Coding in Base64.
    $ob_query_rw_pending_request = "INSERT INTO fgs_position_requests (spr_hash, spr_base64_sqlz) VALUES ('".$ob_sha_hash."', '".$ob_base64_rw_query."');";
    $resultrw = @pg_query($resource_rw, $ob_query_rw_pending_request);          // Sending the request...

    // Model stuff into pending requests table.
    $mo_sha_to_compute = "<".microtime()."><".$ipaddr."><".$mo_query.">";
    $mo_sha_hash = hash('sha256', $mo_sha_to_compute);
    $mo_zipped_base64_rw_query = gzcompress($mo_query, 8);                       // Zipping the Base64'd request.
    $mo_base64_rw_query = base64_encode($mo_zipped_base64_rw_query);               // Coding in Base64.
    $mo_query_rw_pending_request = "INSERT INTO fgs_position_requests (spr_hash, spr_base64_sqlz) VALUES ('".$mo_sha_hash."', '".$mo_base64_rw_query."');";
    $resultrw = @pg_query($resource_rw, $mo_query_rw_pending_request);          // Sending the request...

    @pg_close($resource_rw);                                                 // Closing the connection.

    if (!$resultrw) {
        echo "<p class=\"center\">Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.</p><br />";
    }
    else {
        $failed_mail = 0;
        $au_email = get_authors_email_from_authors_id($author);
        if (($au_email != '') && (strlen($au_email) > 0)) {
            $safe_au_email = pg_escape_string(stripslashes($au_email));
            echo "<p class=\"center ok\">Email: ".$safe_au_email."</p><br />";
        }
        else {
            echo "<p class=\"center warning\">No email was given (not mandatory) or email mismatch!</p><br />";
            $failed_mail = 1;
        }
        echo "<p class=\"center\">Your 3D model insertion request has been successfully queued into the FG scenery database update requests!<br />";
        echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
        echo "The FG community would like to thank you for your contribution!<br />";
        echo "Want to submit another model or position?<br /> <a href=\"http://".$_SERVER['SERVER_NAME']."/submission/\">Click here to go back to the submission page.</a></p>";

        // Sending mail if there is no false and SQL was correctly inserted.
        date_default_timezone_set('UTC');                                // Sets the time to UTC.
        $dtg = date('l jS \of F Y h:i:s A');
        $ipaddr = pg_escape_string(stripslashes($ipaddr));               // Retrieving the IP address of the submitter (takes some time to resolve the IP address though).
        $host = gethostbyaddr($ipaddr);

        // Who will receive it ?
        $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
        $to .= "\"Julien NGUYEN\" <jnguyen@etu.emse.fr>, ";
        $to .= "\"Martin SPOTT\" <martin.spott@mgras.net> ";

        // What is the subject ?
        $subject = "[FG Scenery Submission forms] Automatic 3D model import request: needs validation.";

        // Correctly set the object URL.
        $family_url = "http://".$_SERVER['SERVER_NAME']."/modelbrowser.php?shared=".$mo_shared;
        $html_family_url = htmlspecialchars($family_url);

        // Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
        if($failed_mail != 1) {
            $message0 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'] . "\r\n" .
                        "I just wanted to let you know that a new 3D model import request is pending." . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") and with email address ".$safe_au_email."\r\n" .
                        "issued the following request:" . "\r\n";
        }
        else {
            $message0 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'] . "\r\n" .
                        "I just wanted to let you know that a new 3D model import request is pending." . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") issued the following request:" . "\r\n";
        }
        $message077 = wordwrap($message0, 77, "\r\n");

        // There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.
        $message1 = "Family: ".family_name($mo_shared)."\r\n" .
        "[ ".$html_family_url." ]" . "\r\n" .
        "Path: ". $xmlName . "\r\n" .
        "Author: ". get_authors_name_from_authors_id($author) ."\r\n" .
        "Description: ". $name ."\r\n" .
        "Comment: ". strip_tags($comment) ."\r\n" .
        "Latitude: ". $latitude . "\r\n" .
        "Longitude: ". $longitude . "\r\n" .
        "Ground elevation: ". $gndelev . "\r\n" .
        "Elevation offset: ". $offset . "\r\n" .
        "True (DB) orientation: ". heading_stg_to_true($heading) . "\r\n" .
        "Please click:" . "\r\n" .
        "http://mapserver.flightgear.org/submap/?lon=". $longitude ."&amp;lat=". $latitude ."&amp;zoom=14" . "\r\n" .
        "to locate the object on the map." ;

        $message2 = "\r\n".
        "Now please click:" . "\r\n" .
        "http://".$_SERVER['SERVER_NAME']."/submission/static/static_submission.php?ob_sig=". $ob_sha_hash ."&amp;mo_sig=". $mo_sha_hash ."&amp;email=". $safe_au_email."\r\n" .
        "to view and confirm/reject the submission." . "\r\n" .
        "Thanks!" ;

        // Preparing the headers.
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "From: \"FG Scenery Submission forms\" <martin.spott@mgras.net>" . "\r\n";
        $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

        // Let's send it ! No management of mail() errors to avoid being too talkative...
        $message = $message077.$message1.$message2;
        @mail($to, $subject, $message, $headers);

                // Mailing the submitter
        if($failed_mail != 1) {

            // Tell the submitter that its submission has been sent for validation.
            $to = $safe_au_email;

            // What is the subject ?
            $subject = "[FG Scenery Submission forms] Automatic 3D model import request: needs validation.";

            // Correctly set the object URL.
            //$family_url = "http://scenemodels.flightgear.org/modelbrowser.php?shared=".$family_id;
            //$object_url = "http://scenemodels.flightgear.org/modeledit.php?id=".$model_id;
            //$html_family_url = htmlspecialchars($family_url);
            //$html_object_url = htmlspecialchars($object_url);

            // Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
            $message3 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'] . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host."), which is thought to be you, issued the following request." . "\r\n" .
                        "Just to let you know that this 3D model import request has been sent for validation." . "\r\n" .
                        "The first part of the unique of this request is ".substr($ob_sha_hash,0,10). "... (object)" . "\r\n" .
                        "and ".substr($mo_sha_hash,0,10). "... (model)" . "\r\n" .
                        "If you have not asked for anything, or think this is a spam, please read the last part of this email." ."\r\n";
            $message077 = wordwrap($message3, 77, "\r\n");

            // There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.
            $message4 = "Family: ".family_name($mo_shared)."\r\n" .
                        "[ ".$html_family_url." ]" . "\r\n" .
                        "Path: ". $xmlName . "\r\n" .
                        "Author: ". get_authors_name_from_authors_id($author) ."\r\n" .
                        "Description: ". $name ."\r\n" .
                        "Comment: ". strip_tags($comment) ."\r\n" .
                        "Latitude: ". $latitude . "\r\n" .
                        "Longitude: ". $longitude . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True (DB) orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Please click:" . "\r\n" .
                        "http://mapserver.flightgear.org/submap/?lon=". $longitude ."&lat=". $latitude ."&zoom=14" . "\r\n" .
                        "to locate the object on the map." . "\r\n" .
                        "This process has been going through antispam measures. However, if this email is not sollicited, please excuse-us and report at http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671";

            // Preparing the headers.
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "From: \"FG Scenery Submission forms\" <martin.spott@mgras.net>" . "\r\n";
            $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

            // Let's send it ! No management of mail() errors to avoid being too talkative...
            $message = $message077.$message4;
            @mail($to, $subject, $message, $headers);
        }
    }
}
include '../../inc/footer.php';
?>
