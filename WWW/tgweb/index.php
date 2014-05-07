<?php
session_start();
$TG_BIN = '/home/martin/terragear/bin';
$WWW_ROOT = '/home/fgscenery/TGBuild';
$TMP_ROOT = '/home/fgscenery/TGBuild';
$URL = 'http://scenery.flightgear.org/TGBuild';

// Cleaning directories first
shell_exec('find '.$WWW_ROOT.' -name *.zip -type f -mmin +15 -delete');
shell_exec('find '.$WWW_ROOT.' -name \'aptgen_*\' -type d -mmin +15 -exec rm -r \"{}\" \\;');

if(isset($_GET['action'])) {
  $WORKSPACE = $_SESSION['workspace'];
  $LOGFILE = $WORKSPACE.'/log.txt';
  $action = $_GET['action'];

  if($action == "refresh-console"){
    if(is_readable($WORKSPACE."/log.txt"))
      echo file_get_contents($WORKSPACE."/log.txt");
  }

// Action = download the generated .btg.gz

  if($action == "dl-btggz"){
    $listOfAptFile = array();
    if( is_dir($WORKSPACE."/work/AirportObj") ){
      $Directory = new RecursiveDirectoryIterator($WORKSPACE."/work/AirportObj", RecursiveDirectoryIterator::SKIP_DOTS);
      $Iterator = new RecursiveIteratorIterator($Directory);
      $Regex = new RegexIterator($Iterator, '/^.+\.gz$/i', RecursiveRegexIterator::GET_MATCH);
      foreach($Regex as $dir){
        $listOfAptFile[] = $dir[0];
      }
    }else{
      echo "AirportObj not found !";
    }
    $zip = new ZipArchive();
    if ($zip->open($WORKSPACE."/".$_SESSION['token'].".zip", ZIPARCHIVE::CREATE) !== TRUE) {
      die ("Could not open archive");
    }
    foreach($listOfAptFile as $file){
      $zip->addFile($file, basename($file)) or die ("ERROR: Could not add file: $file");
    }
    $zip->close();
    shell_exec("find ".$WWW_ROOT." -name *.zip -type f -mmin +3 -delete");
    shell_exec('mv -f '.$WORKSPACE.'/'.$_SESSION['token'].'.zip '.$WWW_ROOT);
    header('Location: '.$URL.'/'.$_SESSION['token'].'.zip');
  }

// Action = upload the apt.dat

  if($action == "ul-apt"){
    $error =$_FILES["myfile"]["error"];
    move_uploaded_file($_FILES["myfile"]["tmp_name"],$WORKSPACE."/apt.dat");
    $aptFile = file($WORKSPACE."/apt.dat");
    if( preg_match("/^I/", $aptFile[0]) &&
        preg_match("/^1000 Version/", $aptFile[1]) && 
        $aptFile[count($aptFile)-1] == 99 ){

      foreach($aptFile as $line){
        if( preg_match("/^1\s+-?\d+\s+[01]\s+[01]\s+([A-Z0-9]+)\s+(.+)\s*$/", $line, $match)){
          $_SESSION['icao'] = $match[1];
        }
      }
      echo "success";
    }else{
      echo "error";
    }
  }

  if($action == "cpt-apt"){
    $cmd  = "cd ".$TG_BIN." && ./genapts850 ";
    $cmd .= "--input=".$WORKSPACE."/apt.dat"." ";
    $cmd .= "--work=".$WORKSPACE."/work ";
    $cmd .= "--airport=".$_SESSION['icao']." --threads";
    logMe($cmd, $LOGFILE);
    shell_exec($cmd.' 2>&1 | tee -a '.$LOGFILE);
    logMe("", $LOGFILE);
    echo "success";
  }
  exit();
}

// Generate the log content

function logMe($text, $file){
  file_put_contents($file, "<b><span style=\"color:#33CC33;\">guest@terragear</span> <span style=\"color:#0066FF;\">~ $</span></b> ".$text."\n", FILE_APPEND);
}

$_SESSION['token'] = uniqid('aptgen_');
$_SESSION['workspace'] = $TMP_ROOT."/".$_SESSION['token'];
shell_exec("mkdir -p ".$_SESSION['workspace']." && touch ".$_SESSION['workspace']."/log.txt");
chmod($_SESSION['workspace']."/log.txt", 0777);
shell_exec('echo "<b><span style=\"color:#33CC33;\">guest@terragear</span> <span style=\"color:#0066FF;\">~ $</span></b>" > '.$_SESSION['workspace'].'/log.txt');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="robots" content="index">
  <meta http-equiv="Content-Language" content="en">
  <meta http-equiv="Pragma" content="no-cache">
  <title>Airport Web Generator</title>
  <style>
    body{ margin: 0; padding: 0; background: #EEE; }
    hr{ margin: 0; padding: 0; }
    p{ margin-left: 16px; }
    button.next{ color: black; }
    button.next:disabled{ color: #AAA; }
    header{ width: 100%; min-width: 960px; height: 90px; margin: 0; padding: 0; text-align:center; }
    header h1{ color: #2184BE; margin: 12px 0 24px 0; padding: 0; font-size: 46px; line-height: 65px; }
    #nav{ width: 235px; margin: 0; padding: 0; position: absolute; }
    #nav li{ list-style-type: none; background: #2184BE; margin: 4px 6px; width: 220px; height: 52px; border-radius: 10px; }
    #nav li.active{ background: #193954; }
    #nav li span{ display: block; text-align: center; text-decoration: none; line-height: 52px; font-size: 18px; color: white; width: 100%; height: 100%; cursor: default; }
    div[step=start] input{ color: #444; }
    #wrapper{ min-width: 1000px; }
    button.next{ float: right; width: 140px; height: 50px; font-size: 32px; margin: 4px; }
    #content{ margin: 0 0 0 235px; padding: 0; }
    #content div.step{ display: none; margin: 6px; width: 98%; min-height: 440px; background: #CCC; overflow: hidden; float: left; border-radius: 10px; }
    #content div.step-content{ margin: 6px; }
    div.step-content div.custom, div.step-content div.mapserver, div.step-content div.compute{ border: 2px solid silver; border-radius: 18px; padding: 10px; margin: 12px 12px 12px 22px; }
    #content div.step h3{ color: #2184BE; text-decoration: underline; font-size: 22px; }
    a.dl-btggz{ display: block; font-size: 32px; text-align: center; margin: 60px; padding: 20px; border: 1px solid #EEE; border-radius: 20px; color: #2184BE; text-decoration: none; }
    a.dl-btggz:hover{ background: #EEE; }
    #console{ width: 99%; height: 480px; margin: 0 0 20px 0; clear: both; }
    #console h4{ background: #BBB; margin: 0 8px; padding: 0 0 0 6px; border-radius: 5px 5px 0 0; }
    #console h4 span{ font-size: 12px; font-weight: normal; float: right; margin-right: 12px; margin-top: 2px; cursor: pointer; }
    #console .console-wrapper{ background: black; width: 99%; height: 100%; color: white; margin: 0 8px; padding: 0; overflow-y: scroll; }
    #console pre{ white-space: pre-wrap; margin: 8px 12px; }
    footer{ height: 80px; width: 97%; min-width: 990px; margin: 40px auto 24px auto; padding: 0; border: 2px solid #2184BE; border-radius: 12px; }
    footer p{ text-align: center; margin: 12px; }
  </style>
  <link rel="shortcut icon" href="../include/favicon.ico" />
  <script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
  <script src="http://code.jquery.com/jquery-migrate-1.2.1.min.js"></script>

  <link href="http://hayageek.github.io/jQuery-Upload-File/uploadfile.min.css" rel="stylesheet">
  <script src="http://hayageek.github.io/jQuery-Upload-File/jquery.uploadfile.min.js"></script>

  <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css" />
  <script src="http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js"></script>
</head>
<body>

<header>
  <h1>. . : Airport Web Generator : . .</h1>
  <hr/>
</header>

<section id="wrapper">
<!-- ######################################  NAVIGATION  ####################################### -->
  <ul id="nav">
    <li step="dl-apt" class="active"><span>Download airport</span></li>
    <li step="cpt-apt"><span>Compute airport</span></li>
    <li step="finish"><span>Finish</span></li>
  </ul>

  <section id="content">
<!-- ###################################  DOWNLOAD AIRPORT  #################################### -->
    <div class="step" step="dl-apt" style="display:block;">
      <div class="step-content">
        <h3>Select a source for airport</h3>
        <p>
          <input type="radio" name="apt-form-select" checked="checked" value="custom">Use custom airport file</input>
        </p>
        <div class="custom">
          <p>
            You have to provide airport file in a .dat file.<br/>
            Your .dat file must start with "I", contain only one airport and end with "99".
          </p>
          <div id="ul-apt-box">Select a file</div>
        </div>
        <button class="next" value="dl-apt" disabled>Next ></button>
      </div>
    </div>
<!-- ###################################  COMPUTE AIRPORT  ##################################### -->
    <div class="step" step="cpt-apt">
      <div class="step-content">
        <h3>Compute airport</h3>
        <div class="compute">
          <p>
            Here you just need to click on the Process button in order to run genapts850.<br/><br/>
          </p>
          <input type="submit" value="Process"/>
        </div>
        <button class="next" value="cpt-apt" disabled>Next ></button>
      </div>
    </div>
<!-- #########################################  FINISH  ######################################## -->
    <div class="step" step="finish">
      <div class="step-content">
        <h3>Finish</h3>
        <div class="compute">
          <p>Congratulations! You have generated your airport file, you can now download it:</p>
          <a href="index.php?action=dl-btggz" class="dl-btggz">Download</a>
        </div>
      </div>
    </div>
<!-- ########################################  CONSOLE  ######################################## -->
    <section id="console">
      <h4>Console output <span class="autoscroll">Enable/disable autoscroll</span></h4>
      <div class="console-wrapper">
        <pre></pre>
      </div>
    </section>
  </section>
</section>
<footer>
  <p>Copyright 2014 - Clément de l'Hamaide<br/> Web interface for TerraGear tool chain</p>
</footer>

<script type="text/javascript">
var stopAutoScroll = false;

//#################### SUBMIT ###################
// DL Airport
$("div[step=dl-apt] div.custom :submit").click(function(){
  uploadApt.startUpload();
  $(this).attr("disabled", "disabled");
});

// CPT Airport
$("div[step=cpt-apt] div.compute :submit").click(function(){
  $(this).attr("disabled", "disabled");
  $.get("index.php", {action: "cpt-apt"},
    function(data){
      if(data == "success"){
        $("div[step=cpt-apt] button.next").removeAttr("disabled");
      }
    });
  return false;
});

//################# NEXT BUTTON #################
$("button.next").click(function(){
  var step = $(this).parents().next("div.step").attr("step");
  $("html").animate({scrollTop:0}, function(){
    $("div.step:visible").fadeOut("normal", function(){
      $("div.step[step="+step+"]").fadeIn("normal");
    });
  });

  $("#nav li").removeClass("active");
  $("#nav li[step="+step+"]").addClass("active");
});

//################## CONSOLE ####################
setInterval(function() {
  $.get('index.php?action=refresh-console', function(data) {
    $('#console pre').html(data);
    if(!stopAutoScroll)
      $('div.console-wrapper').animate({scrollTop:$("#console pre").height()});
  });
}, 1500);

$("#console .autoscroll").click(function(){
  stopAutoScroll = !stopAutoScroll;
});

var uploadApt = $("#ul-apt-box").uploadFile({
  url:"index.php?action=ul-apt",
  multiple:false,
  autoSubmit:true,
  showFileCounter:false,
  maxFileSize:1024*1024*25,
  allowedTypes:"dat",
  fileName:"myfile",
  maxFileCount:1,
  onSuccess:function(files,data,xhr){
    if(data == "success"){
      $("div[step=dl-apt] button.next").removeAttr("disabled");
      $("div[step=dl-apt] div.mapserver :submit").attr("disabled", "disabled");
    }else{
      alert("There is a problem with your file.\nPlease check that the top of your file starts with \"I\" then \"1000 Version\" and that the last line is \"99\"");
    }
  }
});
</script>
</body>
</html>
