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

// Checks if models exists in DB from a model name sent in parameter.
// ==================================================================
// Model's name is composed of: OBJECT_SHARED Models/
// a mg_path from fgs_modelgroups;
// a mo_path from fgs_models;
// ie : Models/Power/windturbine.xml
// So we have to check that the couple Power/windturbine.xml exists: if both concatenated values are ok, then we're fine.

function model_exists($model_name) {
    // Starting by checking the existence of the object

    $mg_id = pg_escape_string($model_name);
    $tab_path = explode("/", $mg_id);               // Explodes the fields of the string separated by /
    $max_tab_path = count($tab_path);               // Counts the number of fields.
    $queried_mo_path = $tab_path[$max_tab_path-1];  // Returns the last field value.

    // Checking that the label "Model" is correct
    if (strcmp($tab_path[0],"Models")) {
        return 1;
    }

    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT mo_path, mo_shared FROM fgs_models WHERE mo_path = '".$queried_mo_path."';";
    $result = pg_query($headerlink_family, $query);

    // Checking the number of results. Should be 1.
    if (pg_num_rows($result) == 1) {               // If object is known, going to check the family next.
        // Now proceeding with the family
        // The family path is the string between Models and the object name. Can be multiple.
        $queried_family_path = "";
        for ($j=1; $j<($max_tab_path-1); $j++) {
            $queried_family_path.=$tab_path[$j]."/";
        }

        // Querying to check the existence of the family
        $query_family = "SELECT mg_path FROM fgs_modelgroups WHERE mg_path='".$queried_family_path."';";
        $result_family = pg_query($headerlink_family, $query_family);

        if (pg_num_rows($result_family) == 1) {   // If the family & model are known, return 0.
            return 0;
        } else {
            return 3;    // If the family is unknown, I say it and exit
        }
    } else {
        return 2;    // Il the object is unknown, I say it and exit
    }

    // Closing the connection.
    pg_close($headerlink_family);
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