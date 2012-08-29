<html>
<head>
<style type="text/css">

#canvas {
  width: 570px;
  cursor:move;
  z-index: 10;
}

#details {

  top: 0px;
  left: 0px;
  overflow: hidden;
  display: block;
  padding: 10px;
  color: #ffffff;
  font-family: monospace;
  z-index: 1;
}

#name {
  margin: 0px;
  font-size: 25px;
  font-weight:bold;
}

#text {
  position: absolute;
  bottom: 0px;
  right: 0px;
  margin-bottom: 90px;
  padding: 10px;
  overflow: hidden;
  display: block;
  color: #3f3f5f;
  font-family: monospace;
  font-weight:bold;
  z-index: 2;
}

#text a{
  color: #3f3f5f;
}

#loading {
  position: absolute;
  top: 0px;
  left: 0px;
  width: 100%;
  height: 100%;
  overflow: hidden;
  display: none;
  background: black;
  opacity: 0.50;
  z-index: 100;
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

     
        
var Models = [
  { file: "get_ac3d_from_dir.php?id=<?php echo rawurlencode($id); ?>", setup: {eye: [-28.07, 10, 25], poi: [6.86, 3.30, -0.02], up:[-0.70, 0.50, 0.50], fov: 45, texture_path: "get_texture_by_filename.php?id=<?php echo rawurlencode($id); ?>&name="} }
];

var canvas, details, loading, viewer, current;

function onLoad(){
  canvas = document.getElementById("canvas");
  
  // check if the browser support WebGL
  if (!window.WebGLRenderingContext) {
       window.location = "http://get.webgl.org";
  } else {
    details = document.getElementById("details");
    loading = document.getElementById("loading");
    viewer = new HG.Viewer(canvas);
    current = 0;
    
    resize();
    showModel(Models[current]);
  }
};

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
  viewer.show(model.file, model.setup, onLoaded);
};

function onLoaded(){
  loading.style.display = "none";
};
</script>

</head>

<body onload="onLoad();">
  <canvas id="canvas"></canvas>
  <div id="details"></div>
  <div id="loading"></div>
</body>

</html>
