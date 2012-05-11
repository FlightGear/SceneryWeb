<?php
require_once('../inc/functions.inc.php');
require_once('../captcha/recaptchalib.php');

$fatalerror = 0;
$error      = 0;
$errormsg   = "";
ini_set("auto_detect_line_endings", true);

function clearDir($dossier) {
  $ouverture=@opendir($dossier);
  if (!$ouverture) return;
  while($fichier=readdir($ouverture)) {
    if ($fichier == '.' || $fichier == '..') continue;
    if (is_dir($dossier."/".$fichier)) {
      $r=clearDir($dossier."/".$fichier);
      if (!$r) return false;
    }else{
      $r=@unlink($dossier."/".$fichier);
      if (!$r) return false;
    }
  }
  closedir($ouverture);
  $r=@rmdir($dossier);
  if (!$r) return false;
  return true;
}

/*
$privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
$resp = recaptcha_check_answer ($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
if(!$resp->is_valid){
  echo "Wrong captcha ! <a href=\"javascript:history.back()\">Go back and try it again</a>";
  exit();
}
*/

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title>Automated Static Models Submission Form</title>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <link rel="stylesheet" href="../../style.css" type="text/css"></link>
</head>

<body>
<?php
include '/home/jstockill/scenemodels/header.php';

###############################################
###############################################
#
# STEP 1 : CHECK IF ALL FILES ARE RECEIVED
#
###############################################
###############################################

function removeExt($fichier){ // This function return the filename without extension
  if(strrpos($fichier, ".")===false) return $fichier;
  else return substr($fichier, 0, strrpos($fichier, "."));
}

if($_FILES["mo_thumbfile"]['name'] != "" && $_FILES["ac3d_file"]['name'] != ""){

  $thumbName = removeExt($_FILES["mo_thumbfile"]['name']);
  $ac3dName  = removeExt($_FILES["ac3d_file"]['name']);
  $xmlName   = removeExt($_FILES["xml_file"]['name']);

}else{
  $fatalerror = 1;
  $error += 1;
  $errormsg .= "You <u>must</u> provide at least 1 thumbnail file, 1 AC file, 1 PNG file file<br/>";
}

###############################################
###############################################
#
# STEP 2 : CHECK IF ALL FILES MATCH THE NAME RULE
#
###############################################
###############################################

if($thumbName == $ac3dName."_thumbnail" && !$fatalerror){

  if(!mkdir('/tmp/static/')){
    $fatalerror = 1;
    $error += 1;
    $errormsg .= "Impossible to create '/tmp/static/' directory !";
  }

  $targetPath   = "/tmp/static/";
  if($ac3dName == $xmlName){
    $xmlName    = $_FILES["xml_file"]['name'];
    $xmlPath    = $targetPath.$_FILES["xml_file"]['name'];
  }
  $thumbPath    = $targetPath.$_FILES["mo_thumbfile"]['name'];
  $ac3dPath     = $targetPath.$_FILES["ac3d_file"]['name'];
  $thumbName    = $_FILES["mo_thumbfile"]['name'];
  $ac3dName     = $_FILES["ac3d_file"]['name'];

  for($i=0; $i<12; $i++){
    if($_FILES["png_file"]["name"][$i] != ""){
      $pngAllName[] = $_FILES["png_file"]["name"][$i];
    }
  }

}else{
  if(!$fatalerror){
    $fatalerror = 1;
    $error += 1;
    $errormsg .= "XML file, AC file and thumbnail file <u>must</u> have the same name. (i.e: tower.xml, tower.ac, tower_thumbnail.jpeg)<br/>";
  }
}

###############################################
###############################################
#
# STEP 3 : UPLOAD ALL FILES IN TMP DIRECTORY
#
###############################################
###############################################

###
# STEP 3.1 : UPLOAD THUMBNAIL FILE IN TMP DIRECTORY
###

if($_FILES['mo_thumbfile']['size'] < 2000000 && !$fatalerror){ // check size file

  if($_FILES['mo_thumbfile']['type'] == "image/jpeg" && (ShowFileExtension(basename($thumbName))=="jpeg" || ShowFileExtension(basename($thumbName))=="JPEG" || ShowFileExtension(basename($thumbName))=="JPG" || ShowFileExtension(basename($thumbName))=="jpg")){ // check type & extension file

    if($_FILES['mo_thumbfile']['error'] != 0){ // If error is detected
      $error += 1;
      $errormsg .= "There is an error uploading the file \"".$thumbName."\"<br/>";
      switch ($_FILES['mo_thumbfile']['error']){
        case 1:
          $errormsg .= "The file \"".$thumbName."\" is bigger than this server installation allows.<br/>";
          break;
        case 2:
          $errormsg .= "The file \"".$thumbName."\" is bigger than this form allows.<br/>";
          break;
        case 3:
          $errormsg .= "Only part of the file \"".$thumbName."\" was uploaded.<br/>";
          break;
        case 4:
          $errormsg .= "No file \"".$thumbName."\" was uploaded.<br/>";
          break;
      }
    }else{
      if(!move_uploaded_file($_FILES['mo_thumbfile']['tmp_name'], $thumbPath)){ // check upload file
        $fatalerror = 1;
        $error += 1;
        $errormsg .= "There has been an error while moving the file \"".$thumbName."\" on the server.<br/>";
      }
    }
  }else{
    $error += 1;
    $errormsg .= "The format or the extention seems to be wrong for your thumbnail file \"".$thumbName."\". Thumbnail need to be a JPEG file<br/>";
  }
}else{
  if(!$fatalerror){
    $error += 1;
    $errormsg .= "Sorry, but size of your thumbnail file \"".$thumbName."\" is over 2Mb (current size: ".$_FILES['mo_thumbfile']['size']." bytes).<br/>";
  }
}

###
# STEP 3.2 : UPLOAD AC3D FILE IN TMP DIRECTORY
###

if($_FILES['ac3d_file']['size'] < 2000000 && !$fatalerror){ // check size file

  if($_FILES['ac3d_file']['type']=="application/octet-stream" && (ShowFileExtension(basename($ac3dName))=="ac" || ShowFileExtension(basename($ac3dName))=="AC")){ // check type & extension file

    if(($_FILES['ac3d_file']['error'])!=0){ // If error is detected
      $error += 1;
      $errormsg .= "There is an error uploading the file \"".$ac3dName."\"<br/>";
      switch ($_FILES['ac3d_file']['error']){
        case 1:
          $errormsg .= "The file \"".$ac3dName."\" is bigger than this server installation allows.<br/>";
          break;
        case 2:
          $errormsg .= "The file \"".$ac3dName."\" is bigger than this form allows.<br/>";
          break;
        case 3:
          $errormsg .= "Only part of the file \"".$ac3dName."\" was uploaded.<br/>";
          break;
        case 4:
          $errormsg .= "No file \"".$ac3dName."\" was uploaded.<br/>";
          break;
      }
    }else{
      if(!move_uploaded_file($_FILES['ac3d_file']['tmp_name'], $ac3dPath)){ // check upload file
        $fatalerror = 1;
        $error += 1;
        $errormsg .= "There has been an error while moving the file \"".$ac3dName."\" on the server.<br/>";
      }
    }
  }else{
    $error += 1;
    $errormsg .= "The format or the extention seems to be wrong for your AC3D file \"".$ac3dName."\". AC file need to be a AC3D file<br/>";
  }
}else{
  if(!$fatalerror){
    $error += 1;
    $errormsg .= "Sorry, but size of your AC3D file \"".$ac3dName."\" is over 2Mb (current size: ".$_FILES['ac3d_file']['size']." bytes).<br/>";
  }
}

###
# STEP 3.3 : UPLOAD XML FILE IN TMP DIRECTORY
###

if($_FILES['xml_file']['name'] != ""){ // if file exist
  if($_FILES['xml_file']['size'] < 2000000 && !$fatalerror){ // check size file

    if($_FILES['xml_file']['type'] == "text/xml" && (ShowFileExtension(basename($xmlName))=="xml" || ShowFileExtension(basename($xmlName))=="XML")){ // check type & extension file

      if(($_FILES['xml_file']['error'])!=0){ // If error is detected
        $error += 1;
        $errormsg .= "There was an error uploading the file \"".$xmlName."\"<br/>";
        switch ($_FILES['xml_file']['error']){
          case 1:
            $errormsg .= "The file \"".$xmlName."\" is bigger than this server installation allows.<br/>";
            break;
          case 2:
            $errormsg .= "The file \"".$xmlName."\" is bigger than this form allows.<br/>";
            break;
          case 3:
            $errormsg .= "Only part of the file \"".$xmlName."\" was uploaded.<br/>";
            break;
          case 4:
            $errormsg .= "No file \"".$xmlName."\" was uploaded.<br/>";
            break;
        }
      }else{
        if(!move_uploaded_file($_FILES['xml_file']['tmp_name'], $xmlPath)){ // check upload file
          $fatalerror = 1;
          $error += 1;
          $errormsg .= "There has been an error while moving the file \"".$xmlName."\" on the server.<br/>";
        }
      }
    }else{

      $error += 1;
      $errormsg .= "The format or the extention seems to be wrong for you XML file \"".$xmlName."\". XML file need to be an XML file<br/>";
    }
  }else{
    if(!$fatalerror){
      $error += 1;
      $errormsg .= "Sorry, but size of your XML file \"".$xmlName."\" is over 2Mb (current size: ".$_FILES['xml_file']['size']." bytes).<br/>";
    }
  }
}

###
# STEP 3.4 : UPLOAD PNG FILE IN TMP DIRECTORY
###

for($i=0; $i<12; $i++){
  if($_FILES["png_file"]["name"][$i] != ""){

    $pngName  = $_FILES["png_file"]["name"][$i];
    $pngType  = $_FILES["png_file"]["type"][$i];
    $pngsize  = $_FILES["png_file"]["size"][$i];
    $pngError = $_FILES["png_file"]["error"][$i];
    $pngTmp   = $_FILES["png_file"]["tmp_name"][$i];

    if($pngsize < 2000000 && !$fatalerror){ // check size file

      if($pngType == 'image/png' && (ShowFileExtension(basename($pngName))=="png" || ShowFileExtension(basename($pngName))=="PNG")){ // check type & extension file
	
        if(($pngError)!=0){ // If error is detected
          $error += 1;
          $errormsg .= "There was an error uploading the file \"".$pngName."\"<br/>";
          switch ($_FILES['png_file']['error']){
            case 1:
              $errormsg .= "The file \"".$pngName."\" is bigger than this server installation allows.<br/>";
              break;
            case 2:
              $errormsg .= "The file \"".$pngName."\" is bigger than this form allows.<br/>";
              break;
            case 3:
              $errormsg .= "Only part of the file \"".$pngName."\" was uploaded.<br/>";
              break;
            case 4:
              $errormsg .= "No file \"".$pngName."\" was uploaded.<br/>";
              break;
          }
        }else{
          if(!move_uploaded_file($pngTmp, $targetPath.$pngName)){ // check upload file
            $fatalerror = 1;
            $error += 1;
            $errormsg .= "There has been an error while moving the file \"".$pngName."\" on the server.<br/>";
          }
        }
      }else{
        $error += 1;
        $errormsg .= "The format or the extention seems to be wrong for your texture file \"".$pngName."\". Texture file need to be a PNG file<br/>";
      }
    }else{
      if(!$fatalerror){
        $error += 1;
        $errormsg .= "Sorry, but size of your texture file \"".$pngName."\" is over 2Mb (current size: ".$pngsize." bytes).<br/>";
      }
    }
  }
}

###############################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS
###############################################

if($fatalerror || $error > 0){
  echo "Number of error : ".$error."<br/>";
  echo "FatalError : ".($fatalerror ? "TRUE":"FALSE")."<br/>";
  echo "Error message(s) : <br/>".$errormsg."<br/><br/><br/>";
  echo "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> ";
  echo "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";
  clearDir('/tmp/static');
  exit();
}

###############################################
###############################################
#
# STEP 4 : CHECK XML FILE
#
###############################################
###############################################

if(file_exists($xmlPath)){

  $depth = array();
  $xml_parser = xml_parser_create();

  function startElement($parser, $name, $attrs){
    global $depth;
    $depth[$parser]++;
  }

  function endElement($parser, $name) {
    global $depth;
    $depth[$parser]--;
  }

  xml_set_element_handler($xml_parser, "startElement", "endElement");

  if (!($fp = fopen($xmlPath, "r"))) {
    $fatalerror = 1;
    $error += 1;
    $errormsg .= "could not open XML \"".$xmlName."\"";
  }else{
    while ($data = fread($fp, 4096)) {
      ###
      # check if tags are closed and if <PropertyList> is present
      ###
      if (!xml_parse($xml_parser, $data, feof($fp))) {
        $error += 1;
        $errormsg .= "XML error : ".xml_error_string(xml_get_error_code($xml_parser))." at line ".xml_get_current_line_number($xml_parser)."<br/>";
      }
    }
    xml_parser_free($xml_parser);

  }

  if(!$error > 0){
    ###
    # check if <path> == $ac3dName
    ###
    $xmlcontent = simplexml_load_file($xmlPath);
    if($ac3dName != $xmlcontent->path){
      $error += 1;
      $errormsg .= "The value of your &lt;path&gt; tag doesn't match the name of your AC file<br/>";
    }

    ###
    # check if the file begin with <?xml> tag
    ###
    $xmltag = str_replace(array("<", ">"), array("&lt;", "&gt;"), file_get_contents($xmlPath));
    if(!preg_match('#^&lt;\?xml version="1\.0" encoding="UTF-8"\?&gt;#i', $xmltag)){
      $error += 1;
      $errormsg .= "Your XML must start with &lt;?xml version=\"1.0\" encoding=\"UTF-8\"?&gt;<br/>";
    }
  }


}else{
/*  $fatalerror = 1;
  $error += 1;
  $errormsg .= "The XML file doesn't exist on the server. Please retry to upload it<br/>";*/
}

###############################################
###############################################
#
# STEP 5 : CHECK AC3D FILE
#
###############################################
###############################################

if(file_exists($ac3dPath)){

  if($handle = fopen($ac3dPath, 'r')){
    $i = 0;
    while (!feof($handle)){
      $line = fgets($handle);

      ###
      # check if the file begin with AC3D string
      ###
      if($i == 0){
        if(substr($line,0,4) != "AC3D"){
          $error += 1;
          $errormsg .= "The AC file seems to be not a valid AC3D file. The first line need to be \"AC3Dx\" with x = version<br/>";
        }
      }

      ###
      # check if texture reference matches $pngName
      ###
      if(preg_match('#^texture#', $line)){
        $data = preg_replace('#texture "(.+)"$#', '$1', $line);
        $data = substr($data, 0, -1);
        if(!in_array($data, $pngAllName)){
          $error += 1;
          $errormsg .= "The texture(s) reference (".$data.") at line ".($i+1)." seems to have a different name of yours textures file name<br/>";
        }
      }

      $i++;
    }
  fclose($handle);
  }

}else{
  $fatalerror = 1;
  $error += 1;
  $errormsg .= "The AC file doesn't exist on the server. Please retry to upload it<br/>";
}

###############################################
###############################################
#
# STEP 6 : CHECK IMAGE FILE
#
###############################################
###############################################

for($i=0; $i<12; $i++){
  if($_FILES["png_file"]["name"][$i] != ""){

    $pngPath  = $targetPath.$_FILES["png_file"]["name"][$i];
    $pngName  = $_FILES["png_file"]["name"][$i];

    if(file_exists($pngPath)){

      $tmp    = getimagesize($pngPath);
      $width  = $tmp[0];
      $height = $tmp[1];
      $mime   = $tmp["mime"];
      $validDimension = array(1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024, 2048, 4096, 8192);

      ###
      # check if PNG file is a valid PNG file (compare the type file)
      ###
      if($mime != "image/png"){
        $error += 1;
        $errormsg .= "Your texture file seems to be not a PNG file. Please upload a valid PNG file<br/>";
      }

      ###
      # check if PNG dimensions are a multiple of ^2
      ###
      if(!in_array($height, $validDimension) || !in_array($width, $validDimension)){
        $error += 1;
        $errormsg .= "The size in pixels of your texture file (".$pngName.") appears not to be a power of 2<br/>";
      }

    }else{
      $fatalerror = 1;
      $error += 1;
      $errormsg .= "The texture file doesn't exist on the server. Please retry to upload it<br/>";
    }
  }
}

###############################################
###############################################
#
# STEP 7 : CHECK THUMBNAIL FILE
#
###############################################
###############################################

if(file_exists($thumbPath)){


  $tmp    = getimagesize($thumbPath);
  $width  = $tmp[0];
  $height = $tmp[1];
  $mime   = $tmp["mime"];

  ###
  # check if JPEG file is a valid JPEG file (compare the type file)
  ###
  if($mime != "image/jpeg"){
    $error += 1;
    $errormsg .= "Your thumbnail file seems to be not a JPEG file. Please upload a valid JPEG file<br/>";
  }

  ###
  # check if PNG dimensions are a multiple of ^2
  ###
  if($height != 240 || $width != 320){
    $error += 1;
    $errormsg .= "The pixel dimension of your thumbnail file (".$width."x".$height.") seems not to be 320x240.<br/>";
  }

}else{
  $fatalerror = 1;
  $error += 1;
  $errormsg .= "The thumbnail file doesn't exist on the server. Please retry to upload it<br/>";
}

###############################################
# IF ERRORS ARE DETECTED : STOP NOW AND PRINT ERRORS
###############################################

if($fatalerror || $error > 0){
  echo "Number of error : ".$error."<br/>";
  echo "FatalError : ".($fatalerror ? "TRUE":"FALSE")."<br/>";
  echo "Error message(s) : <br/>".$errormsg."<br/><br/><br/>";
  echo "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> ";
  echo "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";
  clearDir('/tmp/static');
  exit();
}

###############################################
###############################################
#
# STEP 8 : ARCHIVE AND COMPRESS FILES
#
###############################################
###############################################

if(file_exists($targetPath) && is_dir($targetPath)){
  $phar = new PharData('/tmp/static.tar');           // Create archive file
  $phar->buildFromDirectory('/tmp/static');          // Fills archive file
  $phar->compress(Phar::GZ);                         // Convert archive file to compress file
  unlink('/tmp/static.tar');                         // Delete archive file
  rename('/tmp/static.tar.gz', '/tmp/static.tgz');   // Rename compress file

  $handle = fopen("/tmp/static.tgz", "r");
  $contents = fread($handle, filesize("/tmp/static.tgz"));
  fclose($handle);
  $modelFile=base64_encode($contents);               // Dump & encode the file

  unlink('/tmp/static.tgz');                         // Delete compress file
  clearDir('/tmp/static');                           // Delete temporary static directory
}

###############################################
###############################################
#
# STEP 9 : CHECK GEOGRAPHIC INFORMATION
#
###############################################
###############################################

if($_POST["longitude"] != "" && $_POST["latitude"] != "" && $_POST["gndelev"] != "" && $_POST["offset"] != "" && $_POST["heading"] != ""){

  $longitude = strip_tags($_POST["longitude"]);
  $latitude  = strip_tags($_POST["latitude"]);
  $gndelev   = strip_tags($_POST["gndelev"]);
  $offset    = strip_tags($_POST["offset"]);
  $heading   = strip_tags($_POST["heading"]); // need to use compute_heading() before DB insertion

  if(preg_match('#[a-zA-Z ]#', $longitude) || ($longitude < -180 || $longitude > 180)){
    $error += 1;
    $errormsg .= "Please check the longitude value (-180 < longitude < 180)<br/>";
  }

  if(preg_match('#[a-zA-Z ]#', $latitude) || ($latitude < -90 || $latitude > 90)){
    $error += 1;
    $errormsg .= "Please check the latitude value (-90 < latitude < 90)<br/>";
  }

  if(preg_match('#[a-zA-Z ]#', $gndelev) || ($gndelev < -10000 || $gndelev > 10000)){
    $error += 1;
    $errormsg .= "Please check the ground elevation value (-10000 < ground elevation < 10000)<br/>";
  }

  if(preg_match('#[a-zA-Z ]#', $offset) || ($offset < -10000 || $offset > 10000)){
    $error += 1;
    $errormsg .= "Please check the offset value (-10000 < offset < 10000)<br/>";
  }

  if(preg_match('#[a-zA-Z ]#', $heading) || ($heading < 0 || $heading > 359.999)){
    $error += 1;
    $errormsg .= "Please check the heading value (0 < heading < 359.999)<br/>";
  }

}else{
  $error += 1;
  $errormsg .= "Please fill all required fields<br/>";
}

###############################################
###############################################
#
# STEP 10 : CHECK GENERAL INFORMATION
#
###############################################
###############################################

if($_POST["mo_path"] != "" && $_POST["mo_author"] != "" && $_POST["ob_country"] != "" && $_POST["mo_name"] != "" && $_POST["IPAddr"] != "" && isset($_POST['comment'])){

  $path    = addslashes(htmlentities(strip_tags($_POST["mo_path"]), ENT_QUOTES)); // need to use model_exists() before DB insertion
  $name    = addslashes(htmlentities(strip_tags($_POST["mo_name"]), ENT_QUOTES));
  $comment = addslashes(htmlentities(strip_tags($_POST["comment"]), ENT_QUOTES));
  $author  = $_POST["mo_author"];
  $country = $_POST["ob_country"];
  $ipaddr  = $_POST["IPAddr"];

  if(!preg_match('#^[0-9]{1,3}$#', $author)){
    $error += 1;
    $errormsg .= "Please check the author value<br/>";
  }

  if(!preg_match('#^[a-zA-Z]{1,3}$#', $country)){
    $error += 1;
    $errormsg .= "Please check the country value<br/>";
  }

}else{
  $error += 1;
  $errormsg .= "Please fill all required fields<br/>";
}

###############################################
###############################################
#
# STEP 11 : RESUME AND SUBMIT VALIDATION
#
###############################################
###############################################

if($fatalerror || $error > 0){
  echo "Number of error : ".$error."<br/>";
  echo "FatalError : ".($fatalerror ? "TRUE":"FALSE")."<br/>";
  echo "Error message(s) : <br/>".$errormsg."<br/><br/><br/>";
  echo "You can also ask the <a href=\"http://sourceforge.net/mailarchive/forum.php?forum_name=flightgear-devel\">mailing list</a> ";
  echo "or the <a href=\"http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671\">forum</a> for help!";
  clearDir('/tmp/static');
  exit();

}else{
  echo "<font color=\"green\"> Congratulation ! All your files seem to be correct and ready.</font><br/>";
  echo "Following values are available for database insertion : <br/><br/>";
  echo "mo_id        => ?????</br>";
  echo "mo_path      => ".stripslashes(html_entity_decode($path))."<br/>";
  echo "mo_modified  => ?????<br/>";
  echo "mo_author    => ".$author."<br/>";
  echo "mo_name      => ".stripslashes(html_entity_decode($name))."<br/>";
  echo "mo_notes     => ".stripslashes(html_entity_decode($comment))."<br/>";
  echo "mo_thumbfile => ".$thumbName."<br/>";
  echo "mo_modelfile => ".$modelFile."<br/>";
  echo "mo_shared    => ?????<br/>";

}
?>
</body>
</html>
