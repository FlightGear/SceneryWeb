<html>
<head>
<style type="text/css">

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
  { file: "get_ac3d_from_dir.php?id=<?php echo rawurlencode($id); ?>"}
];

var canvas, details, loading, viewer, current;

function onLoad(){
  canvas = document.getElementById("canvas");

  // check if the browser support WebGL
  if (!window.WebGLRenderingContext or !canvas.getContext("webgl")) {
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
  viewer.show(model.file, {callback:onLoaded, texturePath:"get_texture_by_filename.php?id=<?php echo rawurlencode($id); ?>&name="});
};

function onLoaded(){
  loading.style.display = "none";
};
</script>

</head>

<body onload="onLoad();">
  <canvas id="canvas"></canvas>
  <div id="loading"></div>
</body>

</html>
