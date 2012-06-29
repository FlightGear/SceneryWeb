<?php

// Inserting libs
require_once ('../../inc/functions.inc.php');
$page_title = "Automated Models Submission Form";
include '../../inc/header.php';
include_once '../../inc/geshi/geshi.php';

echo "testing Geshi";
// Geshi stuff
    $source = $mavariable = file_get_contents('test.xml');
    include($mavariable);
    $language = 'xml';
    $geshi = new GeSHi($source, $language);

    geshi_highlight($source, 'xml', $path);

include '../../inc/footer.php';
?>
