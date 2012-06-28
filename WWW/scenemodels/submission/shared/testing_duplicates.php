<? include '../../inc/functions.inc.php'; ?>

An existing object:

function detect_already_existing_object($lat, $lon, $ob_gndelev, $ob_elevoffset, $ob_heading, $ob_model)<br />

Result : <?php $result = detect_already_existing_object(47.869766, -3.408148, 26.28, 0, 208, 520); echo $result; ?><br />

A not existing object :</br>

Result : <?php $result2 = detect_already_existing_object(47.87412, -3.412254, 27.24, 0, 209, 403); echo $result2; ?><br />
