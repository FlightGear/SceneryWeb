<?php
  include("include/menu.php");
  include_once('include/geshi/geshi.php');
?>

<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Home</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">

    <h1>General informations</h1>
    <table>
      <tr>
        <td>Model ID: </td>
        <td>0</td>
      </tr>
      <tr>
        <td>Name: </td>
        <td>3D Model name</td>
      </tr>
      <tr>
        <td>Path: </td>
        <td>Model path (only for shared models)</td>
      </tr>
      <tr>
        <td>Author: </td>
        <td>Clément de l'Hamaide</td>
      </tr>
      <tr>
        <td>Notes: </td>
        <td>This is a test</td>
      </tr>
      <tr>
        <td>Family: </td>
        <td>Shared - Airport Architecture</td>
      </tr>
    </table>
    <br/>

    <h1>Thumbnail</h1>
    Click to zoom.<br/>
    <a href="#" class="lightbox"><img src="example/777-200ER_thumbnail.png" width="160px"/></a>
    <br/><br/>

    <h1>XML file</h1>
<?php
  $source = '
<?xml version="1.0"?>

<PropertyList>

  <electrical>
    <path></path>
  </electrical>

  <pitot>
    <name>pitot</name>
    <number>0</number>
  </pitot>

  <static>
    <name>static</name>
    <number>0</number>
    <tau>1</tau>
  </static>

  <vacuum>
    <name>vacuum</name>
    <number>0</number>
    <rpm>engines/engine[0]/n1</rpm>
    <scale>20.0</scale>
  </vacuum>

  <vacuum>
    <name>vacuum</name>
    <number>1</number>
    <rpm>engines/engine[1]/n1</rpm>
    <scale>20.0</scale>
  </vacuum>

</PropertyList>
';

  $geshi =& new GeSHi($source, "php");
  $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
  $geshi->set_line_style('background: #fcfcfc;');
  echo $geshi->parse_code();
?>
    <br/><br/>

    <h1>Texture file(s)</h1>
    Click to zoom.<br/>
    <ul class="gallery">
      <li><a href="#" class="lightbox"><img src="example/light_spot.png" width="160px"/><br/>light_spot.png</a></li>
      <li><a href="#" class="lightbox"><img src="example/paint1.png" width="160px"/><br/>paint1.png</a></li>
      <li><a href="#" class="lightbox"><img src="example/transparent.png" width="160px"/><br/>transparent.png</a></li>
    </ul>
    <br class="clear"/><br/>

    <h1>3D model</h1>
    <iframe src="example/index.php" width="720px" height="620px" scrolling="no" frameborder="0"></iframe> 
    <br/><br/>

    <center><a href="#">I valid this contribution</a> &nbsp;&nbsp;&nbsp; <a href="#">I refuse this contribution</a></center>

  </div>

</div>

<script type="text/javascript">
$(document).ready(function(){
  $("pre.xmlCode").snippet("xml");
}
</script>

<?php include("include/footer.php"); ?>
