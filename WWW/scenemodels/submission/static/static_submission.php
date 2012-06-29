<?php

// Inserting libs
require_once ('../../inc/functions.inc.php');
$page_title = "Automated Models Submission Form";
include '../../inc/header.php';
include '../../inc/geshi/geshi.php';

echo "testing Geshi";
// Geshi stuff
    $source = file_get_contents('test.xml');
    $language = 'xml';
    $geshi = new GeSHi($source, $language);

    geshi_highlight($source, 'xml', $path);
?>

<a href="ContainerCrane.png" rel="lightbox[submission]" title="1st texture">image #1</a>
<a href="Gasometer.png" rel="lightbox[submission]" title="2nd texture">image #2</a>
<a href="ladder.png" rel="lightbox[submission]" title="3rd texture">image #3</a>

<?php
include '../../inc/footer.php';
?>
