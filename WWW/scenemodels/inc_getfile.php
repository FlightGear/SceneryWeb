<?php
/**
 * This file is used to retrieve a file from tgz models
 *
 * To use this script, first define;
 * $id: containing the id of the model
 * $extension : file extension OR $filename (if there can be more than 2 with same extension for instance)
 *
**/

// Inserting libs
require_once 'inc/functions.inc.php';
require_once 'inc/form_checks.php';

if (isset($filename) && !preg_match($regex['filename'], $filename))
    exit;
    
if (!isset($_REQUEST['id']) || !preg_match($regex['modelid'], $_REQUEST['id']))
    exit;
    
$resource_rw = connect_sphere_rw();

// If connection is not OK
if ($resource_rw == '0')
    exit;
    
$id = $_REQUEST['id'];
$result = @pg_query("SELECT mo_modelfile FROM fgs_models WHERE mo_id=$id;");
$model = pg_fetch_assoc($result);
$mo_modelfile = $model["mo_modelfile"];

// Prepare the tmp directory

// Managing possible concurrent accesses on the maintainer side.
$target_path = open_tgz($mo_modelfile);

// Looking for the file in the tmp directory
$dir = opendir($target_path);

while (false !== ($file = readdir($dir))) {
    // If we know the extension
    if (isset($extension) && show_file_extension($file) == $extension) {
        $fichier = $target_path."/".$file;
        readfile($fichier);
        break;
    }

    // If we know the name
    if (isset($filename) && $file == $filename) {
        $fichier = $target_path."/".$file;
        readfile($fichier);
        break;
    }
}

// Ok, now we can delete the stuff we used - at least I think so ;-)
// This should be done at the end of the script
close_tgz($target_path);
?>
