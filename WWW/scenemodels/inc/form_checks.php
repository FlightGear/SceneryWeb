<?php
$regex = array(
 'comment' => "/^[0-9a-z-A-Z\';:!?@-_\. ]+$/u",
 'stg' => '/^[a-zA-Z0-9\_\.\-\,\/]+$/u',
 'model_filepath' => '/^[a-z0-9_\/.-]$/i',
 'modelid' => '/^[0-9]+$/u',
 'familyid' => '/^[0-9]+$/',
 'filename' => '/^[a-zA-Z0-9_.-]*$/u',
 'png_filename' => '/^[a-zA-Z0-9_.-]*$/u',
 'ac3d_filename' => '/^[a-zA-Z0-9_.-]*$/u',
 'xml_filename' => '/^[a-zA-Z0-9_.-]*$/u',
 'authorid' => '#^[0-9]{1,3}$#',
 'email' => '/^[0-9a-zA-Z_\-.]+@[0-9a-z_\-]+\.[0-9a-zA-Z_\-.]+$/u',
 'objectid' => '/^[0-9]+$/u',
 'countryid' => '#^[a-zA-Z]{1,3}$#',
 'long_lat' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
 'offset' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
 'heading' => '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/u',
 'obtext' => '/^[0-9a-zA-Z_\-.\[\]]+$/u',
 'sig' => '/[0-9a-z]/'
);

// Checks if the id is a family id
// ================================================
function is_family_id($id_to_check) {
    global $regex;
    return isset($id_to_check)
           && preg_match($regex['familyid'], $id_to_check)
           && $id_to_check > 0;
}

// Checks if the id is a model id
// ================================================
function is_model_id($id_to_check) {
    global $regex;
    return isset($id_to_check)
           && preg_match($regex['modelid'], $id_to_check)
           && $id_to_check > 0;
}

// Checks if the id is an object id
// ================================================
function is_object_id($id_to_check) {
    global $regex;
    return isset($id_to_check)
           && $id_to_check > 0
           && preg_match($regex['objectid'], $id_to_check);
}
   
// Checks if the given variable is a latitude
// ================================================
function is_latitude($value) {
    global $regex;
    return isset($value)
           && strlen($value) <= 20
           && $value <= 90
           && $value >= -90
           && preg_match($regex['long_lat'], $value);
}

// Checks if the given variable is a longitude
// ================================================
function is_longitude($value) {
    global $regex;
    return isset($value)
           && strlen($value) <= 20
           && $value <= 180
           && $value >= -180
           && preg_match($regex['long_lat'], $value);
}

// Checks if the given variable is a country id
// ================================================
function is_country_id($value) {
    global $regex;
    return isset($value)
           && $value != ""
           && preg_match($regex['countryid'], $value);
}

// Checks if the given variable is an offset
// ================================================
function is_offset($value) {
    global $regex;
    return isset($value)
           && strlen($value) <= 20
           && preg_match($regex['offset'], $value)
           && $value < 1000
           && $value > -1000;
}

// Checks if the given variable is a heading
// ================================================
function is_heading($value) {
    global $regex;
    return isset($value)
           && strlen($value) <= 20
           && preg_match($regex['heading'], $value)
           && $value < 360
           && $value >= 0;
}

// Checks if the given variable is a comment
// ================================================
function is_comment($value) {
    global $regex;
    return isset($value)
           && strlen($value) <= 100
           && preg_match($regex['comment'], $value);
}

// Checks if the given variable is an email
// ================================================
function is_email($value) {
    global $regex;
    return isset($value)
           && strlen($value) <= 50
           && preg_match($regex['email'], $value);
}

function is_sig($value) {
    global $regex;
    return isset($value)
           && strlen($value) == 64
           && preg_match($regex['sig'], $value);
}

?>
