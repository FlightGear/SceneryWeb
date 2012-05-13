<?php include("include/menu.php"); ?>
<div id="main">

  <div class="postHeaderCompact">
    <div class="inner">
      <a title="Permalink to Home" href="http://www.flightgear.org/"><h1 class="postTitle">Download shapefiles</h1></a>
      <div class="bottom">
        <span></span>
      </div>
    </div>
  </div>

  <div class="postContent">



  <table style="border-style: solid; border-width: 1px;" cellpadding="1" cellspacing="1" rules="rows">
    <tr>
      <td>
        <iframe
          src="http://mapserver.flightgear.org/lightmap/?lon=-117.12099&amp;lat=32.73356&amp;zoom=12"
          width="600" height="450"
          scrolling="no"
          marginwidth="2" marginheight="2"
          frameborder="0">
        </iframe>
      </td>
      <td bgcolor="#DDDDDD"><center>
        <p style="border:1px solid; padding: 5px; background-color: white; border-color:grey;"><a href="/shpdl/">Download Shapefiles</a></p>
      </td>
    </tr>
  </table>  

  <br/>
  <table style="border-style: solid; border-width: 1px;" cellpadding="1" cellspacing="1" rules="rows">
    <form method="post" action="http://mapserver.flightgear.org/icaolayers.php">
      <tr>
        <td>Airport code - OpenLayers:</td>
        <td></td>
        <td>  ICAO:</td><td> <input type="text" size="4" maxlength="4" name="icao"></td>
        <td>  <input type="submit" name="senden" value="ICAO"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/lonlatlayers.php">
      <tr>
        <td>  OpenLayers - Longitude:</td><td> <input type="text" size="8" maxlength="8" name="lon"></td>
        <td>  Latitude:</td><td> <input type="text" size="8" maxlength="8" name="lat"></td>
        <td>  <input type="submit" name="senden" value="LON/LAT"></td>
      </tr>
    </form>

    <tr><td></td></tr>
    <tr><td></td></tr>

    <form method="post" action="http://mapserver.flightgear.org/icaomap.php">
      <tr>
        <td>Airport code - MS template view:</td>
        <td></td>
        <td>  ICAO:</td><td> <input type="text" size="4" maxlength="4" name="icao"></td>
        <td>  <input type="submit" name="senden" value="ICAO"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/lonlatmap.php">
      <tr>
        <td>  MS template view - Longitude:</td><td> <input type="text" size="8" maxlength="8" name="lon"></td>
        <td>  Latitude:</td><td> <input type="text" size="8" maxlength="8" name="lat"></td>
        <td>  <input type="submit" name="senden" value="LON/LAT"></td>
      </tr>
    </form>

    <tr><td></td></tr>
    <tr><td></td></tr>

    <form method="post" action="http://mapserver.flightgear.org/map/?lon=9.20438&amp;lat=47.63982&amp;zoom=11">
      <tr>
        <td>Bodensee <a href="http://www.custom-scenery.org/Satellite-Image.304.0.html">Custom Scenery</a> OpenLayers Demo</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="OpenLayers EDNY"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/map/?lon=-62.87648&amp;lat=17.69865&amp;zoom=10">
      <tr>
        <td>Caribbean <a href="http://www.custom-scenery.org/Satellite-Image.304.0.html">Custom Scenery</a> OpenLayers Demo</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="OpenLayers TKPK"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/map/?lon=-156.34932&amp;lat=20.76679&amp;zoom=11">
      <tr>
        <td>Kahului <a href="http://www.custom-scenery.org/Satellite-Image.304.0.html">Custom Scenery</a> OpenLayers Demo</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="OpenLayers PHOG"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/map/?lon=-0.35721&amp;lat=51.44879&amp;zoom=11&amp">
      <tr>
        <td>London partially <a href="http://www.custom-scenery.org/Satellite-Image.304.0.html">Custom Scenery</a> OpenLayers Demo</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="OpenLayers EGLL"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/map/?lon=-122.25243&amp;lat=37.63058&amp;zoom=11&amp">
      <tr>
        <td>San Francisco Bay partially <a href="http://www.custom-scenery.org/Satellite-Image.304.0.html">Custom Scenery</a> OpenLayers Demo</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="OpenLayers SFO Bay"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/map/?lon=-122.37563&amp;lat=37.61927&amp;zoom=15&amp">
      <tr>
        <td>Detailed KSFO <a href="http://www.mapability.com/info/vmap0_intro.html">VMap0</a> OpenLayers Demo</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="OpenLayers KSFO"></td>
      </tr>
    </form>

    <tr><td></td></tr>
    <tr><td></td></tr>

    <form method="post" action="http://mapserver.flightgear.org/berlin.php">
      <tr>
        <td>Berlin - <a href="http://www.custom-scenery.org/Satellite-Image.304.0.html">Custom Scenery</a></td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="Berlin"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/sandiego.php">
      <tr>
        <td>San Diego</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="San Diego"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/neworleans.php">
      <tr>
        <td>New Orleans / Mississippi Delta</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="New Orleans"></td>
      </tr>
    </form>

    <form method="post" action="http://mapserver.flightgear.org/defaultmap.php">
      <tr>
        <td>Bodensee</td>
        <td></td>
        <td></td>
        <td></td>
        <td>  <input type="submit" name="senden" value="Default"></td>
      </tr>
    </form>

    <tr><td></td></tr>
    <tr><td></td></tr>

    <form method="get" action="http://mapserver.flightgear.org/dist.php">
    <tr>
      <td>  ICAO:</td><td> <input type="text" size="4" maxlength="4" name="icao1"></td>
      <td>  ICAO:</td><td> <input type="text" size="4" maxlength="4" name="icao2"></td>
      <td>  <input type="submit" value="Distance">  between two airfields</td>
    </tr>
    </form>
  </table>

  </div>

</div>
<?php include("include/footer.php"); ?>
