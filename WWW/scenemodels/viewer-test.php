<!doctype html>
<html>
  <head>

    <style>
        #canvas {
          width: 570px;
          cursor:move;
          z-index: 10;
        }

        #loading {
          position: absolute;
          top: 0px;
          left: 0px;
          width: 100%;
          height: 100%;
          overflow: hidden;
          display: none;
          background: url('loader.gif') no-repeat center center;
          z-index: 100;
        }

      .webgl-hidden {
          display: none;
      }

    </style>
<script type="text/javascript" src="inc/hangar/gl-matrix-min.js"></script>
<script type="text/javascript" src="inc/hangar/polyfill.js"></script>
<script type="text/javascript" src="inc/hangar/viewer.js"></script>

<?php
if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id']))) {
    $id = $_REQUEST['id'];
}
?>

<script type="text/javascript">
function $$(x) {
    return document.getElementById(x);
}

var Models = [
  { file: "get_ac3d_from_dir.php?id=<?php echo rawurlencode($id); ?>"}
];

var canvas, details, loading, viewer, current, gl;

function launchLogo() {
    details = document.getElementById("details");
    loading = document.getElementById("loading");
    viewer = new HG.Viewer(canvas);
    current = 0;

    resize();
    showModel(Models[current]);
}

function log(msg) {
    var d = document.createElement("div");
    d.appendChild(document.createTextNode(msg));
    document.body.appendChild(d);
}

function removeClass(element, clas) {
    // Does not work in IE var classes = element.getAttribute("class");
    var classes = element.className;
    if (classes) {
        var cs = classes.split(/\s+/);
        if (cs.indexOf(clas) != -1) {
            cs.splice(cs.indexOf(clas), 1);
        }
        // Does not work in IE element.setAttribute("class", cs.join(" "));
        element.className = cs.join(" ");
    }
}

function addClass(element, clas) {
    element.className = element.className + " " + clas;
}

function pageLoaded() {
    removeClass($$("have-javascript"), "webgl-hidden");
    addClass($$("no-javascript"), "webgl-hidden");
    canvas = document.getElementById("canvas");
    var experimental = false;
    try { gl = canvas.getContext("webgl"); }
    catch (x) { gl = null; }

    if (gl == null) {
        try { gl = canvas.getContext("experimental-webgl"); experimental = true; }
        catch (x) { gl = null; }
    }

    if (gl) {
        // WebGL is supported and available
        removeClass($$("webgl-yes"), "webgl-hidden");
        launchLogo();
    } else if ("WebGLRenderingContext" in window) {
        // not a foolproof way to check if the browser
        // might actually support WebGL, but better than nothing
        removeClass($$("webgl-disabled"), "webgl-hidden");
    } else {
        // Show the no webgl message.
        window.location = "http://get.webgl.org";
    }
}

function resize(){
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  window.addEventListener("resize",
    function(event){
      viewer.onResize(window.innerWidth, window.innerHeight);
    }, false);
};

function showModel(model){
  loading.style.display = "block";
  viewer.show(model.file, {callback:onLoaded, texturePath:"get_texture_by_filename.php?id=<?php echo rawurlencode($id); ?>&name="});
};

function onLoaded(){
  loading.style.display = "none";
};

// addEventListener does not work on IE7/8.
window.onload = pageLoaded;
</script>
  </head>
  <body>
    <div id="wrapper">
      <div id="support">

        <div class="webgl-hidden" id="have-javascript">
          <div class="webgl-hidden webgl-div" id="webgl-yes">
            <div id="logo-container">
				<canvas id="canvas"></canvas>
				<div id="loading"></div>
            </div>
          </div>

          <div class="webgl-hidden webgl-div" id="webgl-disabled">
            <p>Hmm.  While your browser seems to support WebGL, it is disabled or unavailable.  If possible, please ensure that you are running the latest drivers for your video card.</p>
            <p id="known-browser" class="webgl-hidden"><a id="troubleshooting-link" href="">For more help, please click this link</a>.</p>
            <p id="unknown-browser">For more help, please visit the support site for your browser.</p>
          </div>

          <div class="webgl-hidden webgl-div" id="webgl-no">
            <p>Oh no!  We are sorry, but your browser does not seem to support WebGL.</p>
            <div id="upgrade-browser">
            <p><a id="upgrade-link" href="">You can upgrade <span id="name"></span> by clicking this link.</a></p>
            </div>
            <div id="get-browser" class="webgl-hidden">
            <p>You may want to download one of the following browsers to view WebGL content.</p>

            <p>The following browsers support WebGL on <span id="platform"></span>:</p>

              <div id="webgl-browser-list">
              </div>
            </div>
          </div>

        </div>
        <div id="no-javascript">
          You must enable JavaScript to use WebGL.
        </div>

      </div>
      <hr />
      <div id="resources">

        <div>Check out some of the following links to learn
        more about WebGL and to find more web applications
        using WebGL.</div><br />

        <div><a href="http://www.khronos.org/webgl/wiki/Main_Page">WebGL Wiki</a></div>
 
      </div>
      <div id="moreinfo">
        <div>Want more information about WebGL?</div><br />

        <div><a href="http://khronos.org/webgl">khronos.org/webgl</a></div>
      </div>
    </div>
  </div>

  </body>
</html>
