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
    require_once ('inc/functions.inc.php');

    if (isset($filename) && !preg_match("/[0-9a-zA-Z_.-]/", $filename))
        exit;
        
    if (isset($_REQUEST['id']) && (preg_match('/^[0-9]+$/u',$_GET['id']))) {
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
        $target_path = sys_get_temp_dir() .'/submission_'.random_suffix();

        while (file_exists($target_path)) {
            usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
        }

        if (!mkdir($target_path)) {
            echo "Impossible to create ".$target_path." directory!";
        }

        if (file_exists($target_path) && is_dir($target_path)) {
            $archive = base64_decode ($mo_modelfile);           // DeBase64 file
            $file = $target_path.'/submitted_files.tar.gz';     // Defines the destination file
            file_put_contents ($file, $archive);                // Writes the content of $mo_modelfile into submitted_files.tar.gz
        }

        $detar_command = 'tar xvzf '.$target_path.'/submitted_files.tar.gz -C '.$target_path. '> /dev/null';
        system($detar_command);


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
        unlink($target_path.'/submitted_files.tar.gz');  // Deletes compressed file
        clear_dir($target_path);                          // Deletes temporary submission directory
    }
?>
