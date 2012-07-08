<?php
  $lowerleftx = $_POST[lon] - 0.5;
  $lowerlefty = $_POST[lat] - 0.5;
  $upperrightx = $_POST[lon] + 0.5;
  $upperrighty = $_POST[lat] + 0.5;
  $location = "$lowerleftx+$lowerlefty+$upperrightx+$upperrighty";
  include 'include/defaultlayers.php';
  include 'include/URL.php';
  header("Location: $URL");
?>
