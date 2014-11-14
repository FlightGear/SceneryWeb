<?php
// Deprecated. Use instead classes/FormChecker.php

$regex = array(
 'comment' => "/^[0-9a-z-A-Z\';:!?@\-_\. ]+$/u",
 'stg' => '/^[a-zA-Z0-9\_\.\-\,\/]+$/u',
 'model_filepath' => '/^[a-z0-9_\/.-]$/i',
 'modelid' => '/^[0-9]+$/u',
 'modelgroupid' => '/^[0-9]+$/',
 'modelname' => '/^[0-9a-zA-Z;!?@\-_\.\(\)\[\]+ ]+$/',
 'filename' => '/^[a-zA-Z0-9_.-]*$/u',
 'png_filename' => '/^[a-zA-Z0-9_.-]*$/u',
 'ac3d_filename' => '/^[a-zA-Z0-9_.-]*$/u',
 'xml_filename' => '/^[a-zA-Z0-9_.-]*$/u',
 'authorid' => '#^[0-9]{1,3}$#',
 'email' => '/^[0-9a-zA-Z_\-.]+@[0-9a-z_\-]+\.[0-9a-zA-Z_\-.]+$/u',
 'objectid' => '/^[0-9]+$/u',
 'countryid' => '#^[a-zA-Z]{1,3}$#',
 'long_lat' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
 'gndelevation' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
 'offset' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
 'heading' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
 'obtext' => '/^[0-9a-zA-Z_\-. \[\]()]+$/u',
 'sig' => '/[0-9a-z]/'
);

// Checks if the given variable is an sig id
// ================================================
function is_sig($value) {
    global $regex;
    return strlen($value) == 64
           && preg_match($regex['sig'], $value);
}

// Checks if the given variable is an obtext
// ================================================
function is_obtext($value) {
    global $regex;
    return strlen($value) > 0
            && strlen($value) <= 100
            && preg_match($regex['obtext'], $value);
}

?>