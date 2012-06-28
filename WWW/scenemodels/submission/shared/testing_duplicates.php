<? include '../../inc/functions.inc.php'; ?>

An existing object:

function detect_already_existing_object($lat, $lon, $ob_gndelev, $ob_elevoffset, $ob_heading, $ob_model)

Result : <?php $result = detect_already_existing_object(47.869766, -3.408148, 26.28, 208.00, 520); echo $result; ?>

A not existing object :

Result : <?php $result2 = detect_already_existing_object(47.87412, -3.412254, 27.24, 209, 403); echo $result2; ?>
