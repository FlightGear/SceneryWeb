<?php

// Connects Read Only to the database
// ==================================
function connect_sphere_r() {
    global $dbhost;
    global $dbname;
    global $dbuser;
    global $dbpass;
    
    // Connecting to database
    $resource_r = pg_connect('dbname='.$dbname.' host='.$dbhost.' user='.$dbuser.' password='.$dbpass.' sslmode=disable');

    // If could not connect to the database
    if ($resource_r == '0') {
        $error_text = "We're sorry, but an error has occurred while connecting to the database.";
        include "error_page.php";
        exit;
    } else {
        // Returning connection resource
        return $resource_r;
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