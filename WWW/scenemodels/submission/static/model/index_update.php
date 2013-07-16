<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<style type="text/css">

#canvas {
  width: 720px;
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
  background: black;
  opacity: 0.50;
  z-index: 100;
}
</style>
<script type="text/javascript" src="../../../inc/hangar/gl-matrix-min.js"></script>
<script type="text/javascript" src="../../../inc/hangar/polyfill.js"></script>
<script type="text/javascript" src="../../../inc/hangar/viewer.js"></script>

<script type="text/javascript">
var Models = [
  { file: "get_ac3d_from_dir_update.php?mo_sig=<?php echo rawurlencode($_GET['mo_sig']); ?>"}
];

var canvas, details, loading, viewer, current;

function onLoad(){
  canvas = document.getElementById("canvas");
  details = document.getElementById("details");
  loading = document.getElementById("loading");
  viewer = new HG.Viewer(canvas);
  current = 0;

  resize();
  showModel(Models[current]);
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
  viewer.show(model.file,
              {callback:onLoaded,
               texturePath:"get_texture_by_filename_update.php?mo_sig=<?php echo rawurlencode($_GET['mo_sig']); ?>&name="
              });
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
