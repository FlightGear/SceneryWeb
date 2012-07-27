<?php include 'inc/header.php';?>
<h1>How To Contribute</h1>

<p>
<b>Foreword:</b> The instructions on this page are being presented in a
pretty elaborate, detailed way which might look a bit complicated at first
glance.

Please don't get this wrong - contributing to the repository is pretty
simple, especially through the use of our web forms. We experienced that almost
every individual in such a large crew of contributors has, needless to say,
a different background. So we just try to give detailed recommendations in order
to avoid misunderstandings.
</p>

<div class="paragraph_bloc">
    <h2>Contents</h2>
    <ul class="body">
    <li class="toclevel-1"><a href="#positions"><span class="toctext">Contributing Positions</span></a></li>
    <li class="toclevel-1"><a href="#models"><span class="toctext">Contributing Models</span></a>
        <ul class="detail">
            <li class="toclevel-1"><a href="#items"><span class="toctext">Submission items</span></a></li>
            <li class="toclevel-1"><a href="#tips"><span class="toctext"><font color="red">Models Best Practice</font></span></a></li>
            <li class="toclevel-1"><a href="#contact"><span class="toctext"><font color="blue">Upload facilities</font></span></a></li>
        </ul>
    </li>
    <li class="toclevel-1"><a href="#thumbnails"><span class="toctext">Contributing Thumbnails</span></a></li>
    </ul>
</div>


<!--</table>
<script type="text/javascript">
if (window.showTocToggle) { var tocShowText = "show"; var tocHideText = "hide"; showTocToggle(); }
</script>
</td></tr>-->

<div class="paragraph_bloc">
    <h2><a name="positions">Contributing Positions</a></h2>

    <div class="body">
        If you wish to contribute positions for the many shared models that are
        already available then these are best submitted:
        <ul>
        <li>directly through <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/submission">our friendly web forms</a> for unitary addition, edition, deletion. <strong>Please use them in priority as they make the work of maintainers much easier.</strong></li>
        <li>either the <a href="http://wiki.flightgear.org/File_Formats#.2A.stg">STG format used in the scenery</a> and by the <a href="http://wiki.flightgear.org/Howto:_Place_3D_objects_with_the_UFO">UFO scenery editor</a> and directly copy/pasted (new objects positions only) in <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/submission">our mass import webform</a>.
        If you have data available in other formats please try to convert them into the STG format first. You can find help on the <a href="http://www.flightgear.org/forums">forums</a> to do so.</li>
        </ul>

        <h3>Extra options for .stg submissions (currently unsupported by our webform)</h3>
        <p>You can help speed the import process by including all the details about your scenery in an stg file. This can be processed automatically and is by far the quickest way to get your model locations into the database.</p>
        <p>There's an example file here: <a href="example.stg">example.stg</a></p>
        <p>Currently supported comments are:</p>
        <ul>
          <li>#country: - defines the country in which the objects reside.</li>
          <li>#submitter: - your name</li>
          <li>#desc: - description to be used for the following objects</li>
        </ul>
        </div>
        </div>

        <div class="paragraph_bloc">
        <h2><a name="models">Contributing Models</a></h2>
        <div class="body">
        <p>If you wish to help populate the world with interesting static objects (yes, we really are aiming for total world domination here :-) then we'll need the following details:</p>

        <h3><a name="items">Submission items</a></h3>
        <h4>Mandantory submission items</h4>
        <ul>
            <li>A <strong>package</strong> (preferably a TAR- or a ZIP-file) containing <strong>all files</strong> which belong to the 3D model itself, in a format supported by FlightGear - or a reference to a model already present in the database;
                <ul class="detail">
                  <li><strong>Multiple models may well be packaged together into a single file</strong> - as long as it is obvious to us which files belong to which model (for example by separating models into different directories);</li>
                  <li><strong>Always choose reasonable (meaningful, descriptive) filenames
                      for your models</strong>. At urban areas having a geometry 'tower.ac'
                      or a texture just named 'red.rgb' or 'concrete.png' might
                      prove not to be unique ;-)</li>
                  <li>As a rule of thumb, try to let even a detailed, <strong>single model not exceed 1/2 MByte in total size</strong>, otherwise the simulation will face hard times when approaching densely packed areas. A typical, single office building usually can be done at (far!) less than 100 kByte;</li>
                  <li><strong>Avoid spaces in file- and/or directory names.</strong></li>
                </ul>
            </li>
            <li><strong>Model placement</strong>:
                <ul class="detail">
                  <li><strong>Position</strong> (if appropriate; either lon/lat, or Ordnance Survey grid - other grids can be added on request);</li>
                  <li><strong>Heading</strong> (if appropriate);</li>
                  <li><strong>Ground elevation</strong> (if known to the author) - in any !! case, report if the model has to be sunken into the ground in order to display properly !!;</li>
                  <li><b> -> </b>....  or just simply submit the respective .stg-line with your model.</li>
                </ul>
            </li>
            <li><strong>Full name of author</strong> (if not already known);</li>
            <li><strong>EMail of author</strong> (if not already known, will not be published, just as a reference);</li>
            <li>A notice which tells us that your submission is covered by the <strong>GPL</strong> (if not already known);
                <ul class="detail">
                  <li>The nature of the FlightGear project does not allow us to accept submissions that don't comply with the GPL;</li>
                </ul>
            </li>
            <li>Always tell us how to <strong>name the model</strong> (like 'Tour Eiffel - Paris - France').</li>
        </ul>

        <h4>Recommended submission items</h4>
        <ul>
            <li><strong>A 320x240 thumbnail</strong> containing an advantageous view on the model/object as (JPEG) image - this is preferred for a nice representation of your artwork;</li>
        </ul>

        <h4>Nice-to-have submission items</h4>
        <ul>
            <li><strong>Country</strong> in which the model is located (if known to the author);</li>
            <li>Additional <strong>short comment on the author</strong>;</li>
        </ul>
        <b> -> </b>After positive experience a simplified procedure is available/recommended upon negotiation.

        <h3><a name="tips"><font color="red">Models Best Practice:</font> To save you and us from avoidable and unnecessary extra work:</a></h3>
        <ul>
            <li>Never group different, detached buildings into a single geometry file.</li>
            <li>Never put surface materials (tarmac, grass, roads, parkings, ...) into Scenery Model geometries.</li>
        </ul>

        <div class="conclusion"><b> -> </b> The reason is simple: Depending on the angle of view, the
                    operating system, the graphics card and driver, the underlying
                    terrain slope, various people might be seeing rendering
                    artifacts.  Therefore: Please don't !</div>
        <ul>
            <li>For groupings of individual models choose a distinct, corresponding position for each of them, never mount multiple models into a single position.</li>
            <li>Textures should be in PNG format - older models which used rgb textures have been updated. <strong>The textures dimensions have to be a power of two: eg 128x256.</strong></li>
        <!--        <li>Apron, taxiway, runway or other airport pavements are being maintained at <A HREF="http://www.x-plane.org/home/robinp/index.htm#Updates">Robin Peel's airport database</A>. -->
            <li>As a general rule, do <strong>not</strong> try to (mis)use 3D models as a substitute for unfinished airfield layout or land cover. Do <strong>not</strong> place your models at incorrect positions just because the current land cover shapes do not match.</li>
            <li>Feel invited to send us an early version of your model even if it still has unfinished details. It's always possible to update the respective metadata entry with a refined model - especially when the placement of the model doesn't change any more.</li>
        </ul>
        <b> -> </b> The better your submission complies with these recommendations, the quicker we'll have it imported into the repository.


        <h3><a name="contact"><font color="blue">Upload facilities</font></a></h3>
        <!-- Facilities to handle the uploading of your own model data are not yet complete, but the data can currently be submitted in 2 ways: -->
        <ul>
            <li> By Email
                <br/>Send a message containing the info above, to (sorry for the anti
                spam measures, I'm sure you understand):
                <br/>fgfsdb at stockill dot net
        <!--                <br/>or
                        <br/>Martin at flightgear dot org
                <li> By anonymous FTP
            <br/>Put all the info described above into an archive (.zip or .tar.gz
            format) and upload it to:
            <br/>ftp://ftp.ihg.uni-duisburg.de/FlightGear/incoming/  -->
            </li>
        </ul>
    </div>
</div>

<div class="paragraph_bloc">
    <h2><a name="thumbnails">Contributing Thumbnails</a></h2>

    <p class="body">
    A noticeable amount of model submissions are missing a thumbnail. If you
    like to take some snapshots for us, go ahead, look at the Model Browser
    pages, pick those models which lack a thumbnail and create a nice view on
    the respective model. JPEG's of 320x240 make our overview.<br/>
    Models are easily identified by their numeric id when you click on the
    thumbnail in the Browser view.
    </p>
</div>
<?php include 'inc/footer.php';?>
