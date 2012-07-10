<html>
<head>
<style>
#canvas {
  width: 720px;
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

#author {
  margin: 0px;
  font-size: 12px;
}

#gallery {
  position: absolute;
  bottom: 0px;
  left: 0px;
  width: 100%;
  height: 125px;
  overflow: hidden;
  display: block;
  background-color: #ffffff;
  opacity: 0.25;
  z-index: 1;
}

.thumbnail {
  position: absolute;
  bottom: 20px;
  width: 90px;
  height: 90px;
  margin: 5px;
  overflow: hidden;
  display: block;
  border: 1px solid #3f3f5f;
  cursor: pointer;
  z-index: 2;
}

.thumbnail.selected {
  margin-bottom: 25px;
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
<script type="text/javascript" src="../../inc/hangar/libs/gl-matrix-min.js"></script>
<script type="text/javascript" src="../../inc/hangar/libs/polyfill.js"></script>
<script type="text/javascript" src="../../inc/hangar/viewer.js"></script>

<script type="text/javascript">
var Models = [
  { name: "Boeing 777-200ER", author: "Justin Smithies / Syd Adams",
    file: "/home/fgscenery/WWW/scenemodels/submission/static/SailBoatUnderPower11m.ac", thumbnail: "/home/fgscenery/WWW/scenemodels/submission/static/SailBoatUnderPower11m.png",
    setup: {eye: [-28.07, -48.23, 23.66], poi: [6.86, 3.30, -0.02], up:[-0.70, 0.50, 0.50], fov: 45} }
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

  details.innerHTML =
    "<p id='name'>" + model.name + "</p>" +
    "<p id='author'>" + model.author + "</p>";

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
<body>

</html>
