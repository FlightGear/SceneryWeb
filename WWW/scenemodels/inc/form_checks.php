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

?>
