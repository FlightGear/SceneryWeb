<?php

// Connects Read Only to the database
// ==================================

function connect_sphere_r() {
    // Inserting dependencies and defining settings
    include "/home/ojacq/.scenemodels";

    // Connecting silently
    $resource_r = pg_connect('dbname='.$database.' host='.$host.' user='.$ro_user.' password='.$ro_pass.' sslmode=disable');

    // If could not connect to the database
    if ($resource_r == '0') {
        $error_text = "We're sorry, but an error has occurred while connecting to the database.";
        include "error_page.php";
        exit;
    } else {
        return $resource_r; // Returning resource_r
    }
}


// Computes the STG heading into a true heading before submission to the database.
// ===============================================================================

function heading_stg_to_true($stg_heading) {
    if ($stg_heading > 180) {
        $true_heading = 540 - $stg_heading;
    }
    else {
        $true_heading = 180 - $stg_heading;
    }
    return $true_heading;
}

// Computes the true heading into a STG heading (for edition purposes).
//=====================================================================

function heading_true_to_stg($true_heading) {
    if ($true_heading > 180) {
        $stg_heading = 540 - $true_heading;
    }
    else {
        $stg_heading = 180 - $true_heading;
    }
    return $stg_heading;
}


// Returns the extension of a file sent in parameter
// =================================================

function show_file_extension($filepath) {
    preg_match('/[^?]*/', $filepath, $matches);
    $string = $matches[0];
    $pattern = preg_split('/\./', $string, -1, PREG_SPLIT_OFFSET_CAPTURE);

    if (count($pattern) > 1) {
        $filenamepart = $pattern[count($pattern)-1][0];
        preg_match('/[^?]*/', $filenamepart, $matches);
        return($matches[0]);
    }
}

// Deletes a directory sent in parameter
// =====================================

function clear_dir($folder) {
    $opened_dir = opendir($folder);
    if (!$opened_dir) {
        return;
    }
    
    while ($file = readdir($opened_dir)) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        if (is_dir($folder."/".$file)) {
            $r = clear_dir($folder."/".$file);
        } else {
            $r = @unlink($folder."/".$file);
        }
        
        if (!$r) {
            return false;
        }
    }

    closedir($opened_dir);
    return rmdir($folder);
}


// This function returns the core filename of a file, ie without its native extension.
// ===================================================================================

function remove_file_extension($file) {
    if (!strrpos ($file, ".")) {
        return $file;
    } else {
        return substr($file, 0, strrpos($file, "."));
    }
}

// This function returns a random string which is used to be suffixed to a directory name to (try) to make it unique.
// ==================================================================================================================

function random_suffix() {
    // Feeding the beast
    $ipaddr = $_SERVER['REMOTE_ADDR'];
    $suffix_data = microtime().$ipaddr;

    // Generating 16 random values from a hash. Should be enough as we also have a concurrent access management on dirs.
    return substr(hash('sha256', $suffix_data), 0, 16);
}

// This function extracts a tgz file into a temporary directory and returns its path.
// ==================================================================================

function open_tgz($archive) {
    // Managing possible concurrent accesses on the maintainer side.
    $target_path = sys_get_temp_dir() .'/submission_'.random_suffix();

    while (file_exists($target_path)) {
        usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
    }

    if (mkdir($target_path)) {
        if (file_exists($target_path) && is_dir($target_path)) {
            $file = $target_path.'/submitted_files.tar.gz';     // Defines the destination file
            file_put_contents ($file, $archive);                // Writes the content of $file into submitted_files.tar.gz

            $detar_command = 'tar xvzf '.$target_path.'/submitted_files.tar.gz -C '.$target_path. '> /dev/null';
            system($detar_command);
        }
    } else {
        error_log("Impossible to create ".$target_path." directory!");
    }

    return $target_path;
}


// This function close a temporary directory opened for a tgz file.
// ================================================================

function close_tgz($target_path) {
    unlink($target_path.'/submitted_files.tar.gz');  // Deletes compressed file
    clear_dir($target_path);                         // Deletes temporary submission directory
}

// Return true if the next TerraSync update is tomorrow
// ================================================================

function check_terrasync_update_passed() {
    $time = "12:30";
    if (strtotime(gmdate("H:i", time())) > strtotime($time)) {
        return $time."Z tomorrow";
    }
    return $time."Z today";
}

// Checks if the model path already exists in DB.
// ==============================================

function path_exists($proposedPath) {
    // Connecting to the databse.
    $resource = connect_sphere_r();

    // Count the number of objects in the database
    $path = pg_query($resource,"SELECT COUNT(*) as count FROM fgs_models WHERE mo_path='".pg_escape_string($proposedPath)."';");
    $line = pg_fetch_assoc($path);
    
    // Close the database resource
    pg_close($resource);
    
    return $line['count']>0;
}

?>