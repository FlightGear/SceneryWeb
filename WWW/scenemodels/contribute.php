<?php include 'header.php';?>
<h1 align=center>How To Contribute</h1>

<b>Foreword:</b> The instructions on this page are being presented in a
pretty elaborate, detailed way which might look a bit complicated at first
glance.

Please don't get this wrong - contributing to the repository is pretty
simple. We experienced that almost every individual in such a large crew of
contributors has, needless to say, a different background. So we just try to
give detailed recommendations in order to avoid misunderstandings.
<p>

<table>
<tr><th><font color="black">Contents</font></th></tr>
<tr><td><table id="toc" class="toc" summary="Contents">
<ul>
<li class="toclevel-1"><a href="#positions"><span class="toctext">Contributing Positions</span></a></li>
<li class="toclevel-1"><a href="#models"><span class="toctext">Contributing Models</span></a></li>
<li class="toclevel-1"><a href="#tips"><span class="toctext"><font color="red">Models Best Practice</font></span></a></li>
<li class="toclevel-1"><a href="#contact"><span class="toctext"><font color="blue">Upload facilities</font></span></a></li>
<li class="toclevel-1"><a href="#thumbnails"><span class="toctext">Contributing Thumbnails</span></a></li>
</ul>
</td></tr></table><script type="text/javascript"> if (window.showTocToggle) { var tocShowText = "show"; var tocHideText = "hide"; showTocToggle(); } </script>
</td></tr>

<tr><th><a name="positions">Contributing Positions</a></th></tr>
<tr><td>
<p>If you wish to contribute positions for the many shared models that are 
already available then these are best submitted:
<ul>
<li>directly through <a href="http://scenemodels.flightgear.org/submission">our friendly web forms</a> if you don't have too many of them (mass import will come soon). Those scripts give you the possibility to add, edit and delete positions of shared objects. <b>Please use them in priority as they make the work of maintainers much easier.</b>
<li>either the <a href="http://wiki.flightgear.org/File_Formats#.2A.stg">STG format used in the scenery</a>, or the XML files produced by the <a href="http://wiki.flightgear.org/Howto:_Place_3D_objects_with_the_UFO">UFO scenery editor</a> and emailed to fgfsdb at stockill dot net.
If you have data available in other formats please contact me at the same address as I may be able to help with converting and importing it for you.
</ul>

<h4>Extra options for .stg submissions</h4>
<p>You can help speed the import process by including all the details about your scenery in an stg file. This can be processed automatically and is by far the quickest way to get your model locations into the database.</p>
<p>There's an example file here: <a href="example.stg">example.stg</a>
<p>Currently supported comments are:<p>
<ul>
  <li>#country: - defines the country in which the objects reside.
  <li>#submitter: - your name
  <li>#desc: - description to be used for the following objects
</ul>
</td></tr>

<tr><th><a name="models">Contributing Models</a></th></tr>
<tr><td>
<p>If you wish to help populate the world with interesting static objects (yes, we really are aiming for total world domination here :-) then we'll need the following details:

<h4>Mandantory submission items</h4>
<ul>
	<li>A package (preferably a TAR- or a ZIP-file) containing all !! files wich belong to the 3D model itself, in a format supported by FlightGear - or a reference to a model already present in the database;<br>
	<ul>
	  <li>Multiple models may well be packaged together into a single file - as long as it is obvious to us which files belong to which model (for example by separating models into different directories);
	  <li>Always choose reasonable (meaningful, descriptive) filenames
	      for your models. At urban areas having a geometry 'tower.ac'
	      or a texture just named 'red.rgb' or 'concrete.png' might
	      prove not to be unique ;-)
	  <li>As a rule of thumb, try to let even a detailed, single model not exceed 1/2 MByte in total size, otherwise the simulation will face hard times when approaching densely packed areas. A typical, single office building usually can be done at (far!) less than 100 kByte;
	  <li>Avoid spaces in file- and/or directory names.
        </ul>
        <li>Model placement:
	<ul>
	  <li>Position (if appropriate; either lon/lat, or Ordnance Survey grid - other grids can be added on request);
	  <li>Heading (if appropriate);
	  <li>Ground elevation (if known to the author) - in any !! case, report if the model has to be sunken into the ground in order to display properly !!;
	  <li><b> -> </b>....  or just simply submit the respective .stg-line with your model.
        </ul>
	<li>Full name of author (if not already known);
	<li> EMail of author (if not already known, will not be published, just as a reference);
	<li>A notice which tells us that your submission is covered by the GPL (if not already known);
	<ul>
	  <li>The nature of the FlightGear project does not allow us to accept submissions that don't comply with the GPL;
	</ul>
	<li>Always tell us how to name the model (like 'Tour Eiffel - Paris - France').
</ul>

<h4>Recommended submission items</h4>
<ul>
	<li>A 320x240 thumbnail containing an advantageous view on the model/object as (JPEG) image - this is preferred for a nice representation of your artwork;
</ul>

<h4>Nice-to-have submission items</h4>
<ul>
	<li>Country in which the model is located (if known to the author);
	<li>Additional short comment on the author;
</ul>
<b> -> </b>After positive experience a simplified procedure is avaliable/recommended upon negotiation.

<h4><a name="tips"><font color="red">NOTICE:</font> To save you and us from avoidable and unnecessary extra work:</a></h4>
<ul>
	<li>Never group different, detached buildings into a single geometry file.
	<li>Never put surface materials (tarmac, grass, roads, parkings, ...) into Scenery Model geometries.
</ul>

<b> -> </b> The reason is simple: Depending on the angle of view, the
            operating system, the graphics card and driver, the underlying
            terrain slope, various people might be seeing rendering
            artifacts.  Therefore: Please don't !<br>
<ul>
	<li>For groupings of individual models choose a distinct, corresponding position for each of them, never mount multiple models into a single position.
        <li>Textures should be in PNG format - older models which used rgb textures have been updated. <b>The textures dimensions have to be a power of two: eg 128x256.</b>
<!--	<li>Apron, taxiway, runway or other airport pavements are being maintained at <A HREF="http://www.x-plane.org/home/robinp/index.htm#Updates">Robin Peel's airport database</A>. -->
	<li>As a general rule, do <b>not</b> try to (mis)use 3D models as a substitute for unfinished airfield layout or land cover. Do <b>not</b> place your models at incorrect positions just because the current land cover shapes do not match.
	<li>Feel invited to send us an early version of your model even if it still has unfinished details. It's always possible to update the respective metadata entry with a refined model - especially when the placement of the model doesn't change any more.
</ul>
<b> -> </b> The better your submission complies with these recommendations, the quicker we'll have it imported into the repository.


<h4><a name="contact"><font color="blue">Upload facilities</a></font></h4>
<!-- Facilities to handle the uploading of your own model data are not yet complete, but the data can currently be submitted in 2 ways: -->
<ul>
	<li> By Email
		<br>Send a message containing the info above, to (sorry for the anti 
		spam measures, I'm sure you understand):
		<br>fgfsdb at stockill dot net
<!--		<br>or
		<br>Martin at flightgear dot org
	<li> By anonymous FTP
    <br>Put all the info described above into an archive (.zip or .tar.gz 
    format) and upload it to:
    <br>ftp://ftp.ihg.uni-duisburg.de/FlightGear/incoming/  -->
<ul>
</td></tr>
<tr><th><a name="thumbnails">Contributing Thumbnails</a></th></tr>
<tr><td>
<p>A noticeable amount of model submissions are missing a thumbnail. If you
like to take some snapshots for us, go ahead, look at the Model Browser
pages, pick those models which lack a thumbnail and create a nice view on
the respective model. JPEG's of 320x240 make our overview.<br>
Models are easily identified by their numeric id when you click on the
thumbnail in the Browser view.
</td></tr>
</table>
</body>
</html>
