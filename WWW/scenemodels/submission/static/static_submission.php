<?php

// Inserting libs
require_once ('../../inc/functions.inc.php');
$page_title = "Automated Models Submission Form";
include '../../inc/header.php';
include '../../inc/geshi/geshi.php';

<<<<<<< HEAD
echo "<p class=\"center\"></p>Hi, this is the static submission form at http://scenemodels.flightgear.org/submission/static.";
echo "<p class=\"center\">The following model has passed all (numerous) verifications by the forementionned script. It should be fine to validate it. However, it's always sane to eye-check it.</p>";
=======
echo "<p class=\"center\"></p>Hi, this is the static submission. The following model has passed all (numerous) verifications by the forementionned script. It should be fine to validate it. However, it's always sane to eye-check it.</p>";
>>>>>>> c0dab9925282c89bb1c441e143b97a873e1d463f

// Geshi stuff
    $source = file_get_contents('test.xml');
    $language = 'xml';
    $geshi = new GeSHi($source, $language);
    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
    $geshi->set_line_style('background: #fcfcfc;');

    geshi_highlight($source, 'xml', $path);
?>

<a href="ContainerCrane.png" rel="lightbox[submission]" title="1st texture">image #1</a>
<a href="ATR42BR0.bmp" rel="lightbox[submission]" title="2nd texture">image #2</a>
<a href="ATR42BR2.bmp" rel="lightbox[submission]" title="3rd texture">image #3</a>

<?php
include '../../inc/footer.php';
?>
