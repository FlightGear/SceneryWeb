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


// Return true if the next TerraSync update is tomorrow
// ================================================================

function check_terrasync_update_passed() {
    $time = "12:30";
    if (strtotime(gmdate("H:i", time())) > strtotime($time)) {
        return $time."Z tomorrow";
    }
    return $time."Z today";
}

?>