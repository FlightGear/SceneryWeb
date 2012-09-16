<?php

// Connects Read Only to the database
// ==================================

function connect_sphere_r()
{
    // Inserting dependencies and defining settings
    include ("/home/ojacq/.scenemodels");
    $dbrname = $database;
    $dbrhost = $host;
    $dbruser = $ro_user;
    $dbrpass = $ro_pass;

    // Connecting silently
    $resource_r = @pg_connect('dbname='.$dbrname.' host='.$dbrhost.' user='.$dbruser.' password='.$dbrpass.' sslmode=disable');

    // If could not connect to the database
    if ($resource_r == '0') {
        $error_text = "We're sorry, but an error has occurred while connecting to the database.";
        include "error_page.php";
        exit;
    }
    else {
        return ($resource_r); // Returning resource_r
    }
}

// Connects Read-Write to the database
// ===================================

function connect_sphere_rw()
{
    // Inserting dependencies and defining settings
    include ("/home/ojacq/.scenemodels");
    $dbrwname = $database;
    $dbrwhost = $host;
    $dbrwuser = $rw_user;
    $dbrwpass = $rw_pass;

    // Connecting silently
    $resource_rw = @pg_connect('dbname='.$dbrwname.' host='.$dbrwhost.' user='.$dbrwuser.' password='.$dbrwpass.' sslmode=disable');

    // If could not connect to the database
    if ($resource_rw == '0') {
        $error_text = "We're sorry, but an error has occurred while connecting to the database.";
        include "error_page.php";
        exit;
    }
    else {
        return ($resource_rw); // Returning resource_rw
    }
}

// Returns the name of the family sent as parameter
// ================================================

function family_name($id_family)
{
    $mg_id = pg_escape_string($id_family);

    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT mg_id, mg_name FROM fgs_modelgroups WHERE mg_id = ".$mg_id.";";
    $result = @pg_query($headerlink_family, $query);

    while ($row = @pg_fetch_assoc($result))    {
        $name_family = $row["mg_name"];
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
    return ($name_family);
}


// Returns the name of the object sent as parameter
// ================================================

function object_name($id_object)
{
    $mg_id = pg_escape_string($id_object);

    // Connecting to the databse.
    $headerlink_object = connect_sphere_r();

    // Querying...
    $query = "SELECT mo_id, mo_name FROM fgs_models WHERE mo_id = ".$mg_id.";";
    $result = @pg_query($headerlink_object, $query);

    // Showing the results.
    while ($row = @pg_fetch_assoc($result)) {
        $name_object = $row["mo_name"];
    }

    // Closing the connection.
    @pg_close ($headerlink_object);
    return ($name_object);
}

// Returns the name of the family of an ob_id sent as parameter
// ============================================================

function get_object_family_from_id($ob_id)
{
    $mg_id = pg_escape_string($ob_id);

    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query1 = "SELECT ob_model FROM fgs_objects WHERE ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query1);

    while ($row = @pg_fetch_assoc($result)) {
        $mo_id = $row["ob_model"];
        $query2 = "SELECT mo_shared FROM fgs_models WHERE mo_id = ".$mo_id.";";
        $result2 = @pg_query($headerlink_family, $query2);

        while ($row2 = @pg_fetch_assoc($result2)) {
            $mg_family = $row2["mo_shared"];
            return (family_name($mg_family));
        }
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
}

// Computes the country id of an ob_id sent as parameter
// (ie, this is not the data in the database)
// =====================================================

function compute_object_country_from_id($ob_id)
{
    $mg_id = pg_escape_string($ob_id);

    // Connecting to the database.
    $headerlink_country = connect_sphere_r();

    // Querying...
    $query = "SELECT co_code, fgs_objects.wkb_geometry FROM gadm2, fgs_countries, fgs_objects WHERE fgs_objects.ob_id = ".$mg_id." AND ST_Within(fgs_objects.wkb_geometry, gadm2.wkb_geometry) AND gadm2.iso ILIKE fgs_countries.co_three;";
    $result = pg_query($headerlink_country, $query);

    while ($row = pg_fetch_assoc($result)) {
        if ($row["co_code"] == '') return 0;
        else return $row["co_code"];
    }

    // Closing the connection.
    pg_close ($headerlink_country);
}

// Update the object's country using its location
// ==============================================

function update_object_country_from_id($ob_id)
{
    $mg_id = pg_escape_string($ob_id);

    $country_code = compute_object_country_from_id($mg_id);
    
    $headerlink_country = connect_sphere_rw();
    $query = "UPDATE fgs_objects SET co_code='$country_code' WHERE ob_id = ".$mg_id.";";
    $result = pg_query($headerlink_country, $query);
    
    // Closing the connection.
    pg_close ($headerlink_country);
}

// Computes the country id of position specified by longitude and latitude
// =======================================================================

function compute_country_code_from_position($long, $lat)
{
    // Connecting to the database.
    $headerlink_country = connect_sphere_r();
    
    // Querying...
    $query = "SELECT co_code FROM gadm2, fgs_countries WHERE ST_Within(ST_PointFromText('POINT($long $lat)', 4326), gadm2.wkb_geometry) AND gadm2.iso ILIKE fgs_countries.co_three;";
    $result = pg_query($headerlink_country, $query);
    
    while ($row = pg_fetch_assoc($result)) {
        if ($row["co_code"] == '') return '';
        else return $row["co_code"];
    }

    // Closing the connection.
    pg_close ($headerlink_country);
}

// Returns the country of an ob_id sent as parameter
// (ie, this is what's in the database)
// =================================================

function get_object_country_from_id($ob_id)
{
    $ob_id = pg_escape_string($ob_id);

    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT ob_id, ob_country FROM fgs_objects WHERE ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query);

    while ($row = @pg_fetch_assoc($result)) {
        $ob_country = $row["ob_country"];
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
    return ($ob_country);
}

// Returns the group name (LANDMARK, NAVAID...) from an ob_group sent as parameter
// ===============================================================================

function get_group_name_from_id($ob_group)
{
    $group_id = pg_escape_string($ob_group);

    // Connecting to the database.
    $headerlink = connect_sphere_r();

    // Querying...
    $query = "SELECT * FROM fgs_groups WHERE gp_id = ".$group_id.";";
    $result = @pg_query($headerlink, $query);

    while ($row = @pg_fetch_assoc($result)) {
            return ($row["gp_name"]);
    }

    // Closing the connection.
    @pg_close ($headerlink);
}

// Returns the object model id from an ob_id sent as parameter
// ===========================================================

function get_object_model_from_id($ob_id)
{
    $mg_id = pg_escape_string($ob_id);

    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT ob_model FROM fgs_objects WHERE ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query);

    while($row = @pg_fetch_assoc($result)) {
        $mo_id = $row["ob_model"];
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
    return ($mo_id);
}

// Returns the object latitude from an ob_id sent as parameter
// ===========================================================

function get_object_latitude_from_id($ob_id)
{
    $mg_id = pg_escape_string($ob_id);

    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT ST_Y(wkb_geometry) AS ob_lat FROM fgs_objects WHERE ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query);

    while ($row = @pg_fetch_assoc($result)) {
        $ob_lat = $row["ob_lat"];
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
    return ($ob_lat);
}

// Returns the object longitude from an ob_id sent as parameter
// ============================================================

function get_object_longitude_from_id($ob_id)
{
    $mg_id = pg_escape_string($ob_id);

    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT ST_X(wkb_geometry) AS ob_lon FROM fgs_objects where ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query);

    while ($row = @pg_fetch_assoc($result)) {
        $ob_long = $row["ob_lon"];
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
    return ($ob_long);
}

// Get the object elevation from an ob_id sent as parameter
// ========================================================

function get_object_elevation_from_id($ob_id)
{
    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT ob_gndelev FROM fgs_objects WHERE ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query);

    while ($row = @pg_fetch_assoc($result)) {
        return ($row["ob_gndelev"]);
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
}

// Get the object offset from an ob_id sent as parameter
// =====================================================

function get_object_offset_from_id($ob_id)
{
    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT ob_elevoffset FROM fgs_objects WHERE ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query);

    while ($row = @pg_fetch_assoc($result)) {
        if ($row["ob_elevoffset"] == "") {
            return (0);
        }
        else return ($row["ob_elevoffset"]);
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
}

// Get the true object orientation from an ob_id sent as parameter
// ===============================================================

function get_object_true_orientation_from_id($ob_id)
{
    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT ob_heading FROM fgs_objects WHERE ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query);

    while ($row = @pg_fetch_assoc($result))    {
        return ($row["ob_heading"]);
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
}

// Get the object text (description) from an ob_id sent as parameter.
// This may be important when deleting objects: some of them may seem
// unappropriate, but do have important metadata shipped with them
// (obstructions, landaid, etc.). So this will show this data on the
// deletion and update emails so the submitter is careful or copy/pastes
// the relevant data.
// ===============================================================

function get_object_text_from_id($ob_id)
{
    // Connecting to the database.
    $headerlink_family = connect_sphere_r();

    // Querying...
    $query = "SELECT ob_text FROM fgs_objects WHERE ob_id = ".$ob_id.";";
    $result = @pg_query($headerlink_family, $query);

    while ($row = @pg_fetch_assoc($result))    {
        return ($row["ob_text"]);
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
}

// Returns the author's name from an author's id sent as parameter
// ===============================================================

function get_authors_name_from_authors_id($au_id)
{
    $au_id = pg_escape_string($au_id);

    // Connecting to the database.
    $headerlink = connect_sphere_r();

    // Querying...
    $query = "SELECT au_name FROM fgs_authors WHERE au_id = ".$au_id.";";
    $result = @pg_query($headerlink, $query);

    while ($row = @pg_fetch_assoc($result)) {
        return ($row["au_name"]);
    }

    // Closing the connection.
    @pg_close ($headerlink);
}

// Returns the author's email from an author's id sent as parameter
// ================================================================

function get_authors_email_from_authors_id($au_id)
{
    $au_id = pg_escape_string($au_id);

    // Connecting to the database.
    $headerlink = connect_sphere_r();

    // Querying...
    $query = "SELECT au_email FROM fgs_authors WHERE au_id = ".$au_id.";";
    $result = @pg_query($headerlink, $query);

    while ($row = @pg_fetch_assoc($result)) {
        if ($row["au_email"] != '') {
            return ($row["au_email"]);
        }
    }

    // Closing the connection.
    @pg_close ($headerlink);
}

// Returns the number of objects in the database.
// ==============================================

function count_objects()
{
    // Connecting to the database.
    $resource = connect_sphere_r();

    // Count the number of objects in the database
    $counter = @pg_query($resource, "SELECT count(*) AS rows FROM fgs_objects;");

    while ($line = @pg_fetch_assoc($counter)) {
        // So the stats are happy
        // return number_format($line['rows'], '0', '', ' ');
        return $line['rows'];
    }

    // Close the database resource
    @pg_close ($resource);
}

// Returns the number of models in the database.
// =============================================

function count_models()
{
    // Connecting to the databse.
    $resource = connect_sphere_r();

    // Count the number of objects in the database
    $counter = @pg_query($resource,"SELECT count(*) AS rows FROM fgs_models;");

    while ($line = @pg_fetch_assoc($counter)) {
        // So the stats are happy
        // return number_format($line['rows'], '0', '', ' ');
        return $line['rows'];
    }

    // Close the database resource
    @pg_close ($resource);
}

// Checks the availability of the database.
// ========================================

function check_availability()
{
    // Connecting to the database.
    $resource = connect_sphere_r();

    if ($resource != '0') {
        @pg_close ($resource);      // Close the database resource
        return (1);                 // Say everything is OK
    }
    else {
        @pg_close ($resource);      // Close the database resource
        return (0);                 // Apologies
    }
}

// Computes the STG heading into a true heading before submission to the database.
// ===============================================================================

function heading_stg_to_true($stg_heading)
{
    if ($stg_heading > 180) {
        $true_heading = 540 - $stg_heading;
    }
    else {
        $true_heading = 180 - $stg_heading;
    }
    return ($true_heading);
}

// Computes the true heading into a STG heading (for edition purposes).
//=====================================================================

function heading_true_to_stg($true_heading)
{
    if ($true_heading > 180) {
        $stg_heading = 540 - $true_heading;
    }
    else {
        $stg_heading = 180 - $true_heading;
    }
    return ($stg_heading);
}

// Checks if models exists in DB from a model name sent in parameter.
// ==================================================================
// Model's name is composed of: OBJECT_SHARED Models/
// a mg_path from fgs_modelgroups;
// a mo_path from fgs_objects;
// ie : Models/Power/windturbine.xml
// So we have to check that the couple Power/windturbine.xml exists: if both concatenated values are ok, then we're fine.

function model_exists($model_name)
{
    // Starting by checking the existence of the object

    $mg_id = pg_escape_string($model_name);
    $tab_path = explode("/", $mg_id);               // Explodes the fields of the string separated by /
    $max_tab_path = count($tab_path);               // Counts the number of fields.
    $queried_mo_path = $tab_path[$max_tab_path-1];  // Returns the last field value.

    // Checking that the label "Model" is correct
    if (strcmp($tab_path[0],"Models")) { return(1); exit; }        // If ever dumb people try to put something else here.

    // Connecting to the database.
    $headerlink_family = connect_sphere_rw();

    // Querying...
    $query = "SELECT mo_path, mo_shared FROM fgs_models WHERE mo_path = '".$queried_mo_path."';";
    $result = @pg_query($headerlink_family, $query);

    // Checking the number of results. Should be 1.
    if (@pg_num_rows($result) == 1)                 // If object is known, going to check the family next.
    {
        // Now proceeding with the family
        // The family path is the string between Models and the object name. Can be multiple.
        $queried_family_path = "";
        for ($j=1; $j<($max_tab_path-1); $j++) {
            $queried_family_path.=$tab_path[$j]."/";
        }

        // Querying to check the existence of the family
        $query_family = "SELECT mg_path FROM fgs_modelgroups WHERE mg_path='".$queried_family_path."';";
        $result_family = pg_query($headerlink_family, $query_family);

        if (@pg_num_rows($result_family) == 1) {   // If the family & model are known, return 0.
            return(0);
        }
        else {
            return(3);    // If the family is unknown, I say it and exit
            exit;
        }
    }
    else {
        return(2);    // Il the object is unknown, I say it and exit
        exit;
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
}

// Returns an ob_model id from a model name sent in parameter.
// ===========================================================

function ob_model_from_name($model_name)
{
    $mg_id = pg_escape_string($model_name);
    $tab_path = explode("/",$mg_id);                         // Explodes the fields of the string separated by /
    $max_tab_path = count($tab_path);                        // Counts the number of fields.
    $queried_mo_path = $tab_path[$max_tab_path-1];           // Returns the last field value.

    // Connecting to the database.
    $headerlink = connect_sphere_r();

    // Querying...
    $query = "SELECT mo_id, mo_path FROM fgs_models WHERE mo_path = '".$queried_mo_path."';";
    $result = @pg_query($headerlink, $query);

    // Checking the number of results. Should be 1.
    if (@pg_num_rows($result) == 1) { // If object is known, returning the mo_id.
        while ($row = pg_fetch_row($result)) {
            return($row[0]);
        }
    }

    // Closing the connection.
    @pg_close ($headerlink);
}

// Lists the authors of models in FlightGear.
// ==========================================

function list_authors()
{
    // Connecting to the database.
    $headerlink_authors = connect_sphere_r();

    // Querying...
    $query = "SELECT au_id, au_name FROM fgs_authors ORDER BY 2 ASC;";
    $result = @pg_query($headerlink_authors, $query);

    while ($row = @pg_fetch_assoc($result)) {
        if ($row["au_id"] == 1) echo "<option value=\"".$row["au_id"]."\" selected=\"selected\">".$row["au_name"]."</option>\n";
        else echo "<option value=\"".$row["au_id"]."\">".$row["au_name"]."</option>\n";
    }

    // Closing the connection.
    @pg_close ($headerlink_family);
}

// Lists the countries in FlightGear.
// ==================================

function list_countries()
{
    // Connecting to the database.
    $headerlink_countries = connect_sphere_r();

    // Querying...
    $query = "SELECT * FROM fgs_countries ORDER BY 2 ASC;";
    $result = @pg_query($headerlink_countries, $query);

    while($row = @pg_fetch_assoc($result)) {
        echo "<option value=\"".$row["co_code"]."\">".rtrim($row["co_name"])."</option>\n";
    }

    // Closing the connection.
    @pg_close ($headerlink_countries);
}

// Returning the full name of the country depending on the country code submitted
// ==============================================================================

function get_country_name_from_country_code($country_code)
{
    // Connecting to the database.
    $headerlink_countries = connect_sphere_r();

    // Querying...
    if($country_code == "") {
        return("Unknown!");
    }
    else {
        $query = "SELECT * FROM fgs_countries WHERE co_code = '".$country_code."';";
        $result = @pg_query($headerlink_countries, $query);

        while ($row = @pg_fetch_assoc($result)) {
            return rtrim(($row["co_name"]));
        }
    }

    // Closing the connection.
    @pg_close ($headerlink_countries);
}

// Returns the extension of a file sent in parameter
// =================================================

function show_file_extension($filepath)
{
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

function clear_dir($folder)
{
    $opened_dir = @opendir($folder);
    if (!$opened_dir) return;
    while ($file = readdir($opened_dir)) {
        if ($file == '.' || $file == '..') continue;
        if (is_dir($folder."/".$file)) {
            $r = clear_dir($folder."/".$file);
            if (!$r) return false;
        } else {
            $r = @unlink($folder."/".$file);
            if (!$r) return false;
        }
    }

    closedir($opened_dir);
    $r = @rmdir($folder);
    if (!$r) return false;
    return true;
}

// Detects if a submitted object already exists in the database f(lat, lon, ob_gndelev, ob_heading, ob_model).
// ===========================================================================================================

function detect_already_existing_object($lat, $lon, $ob_gndelev, $ob_elevoffset, $ob_heading, $ob_model)
{
    // Connecting to the database.
    $resource_r = connect_sphere_r();

    // Querying...
    $query = "SELECT ob_id FROM fgs_objects WHERE wkb_geometry = ST_PointFromText('POINT(".$lon." ".$lat.")', 4326) AND ob_gndelev = ".$ob_gndelev." AND ob_heading = ".heading_stg_to_true($ob_heading)." AND ob_model = ".$ob_model.";";
    $result = @pg_query($resource_r, $query);
    $returned_rows = pg_num_rows($result);

    if ($returned_rows > 0) {
        return true;
    }
    else return false;

    // Closing the connection.
    @pg_close ($resource_r);
}

// This function returns the core filename of a file, ie without its native extension.
// ===================================================================================

function remove_file_extension($file)
{
    if (strrpos ($file, ".") == false)
        return $file;
    else
        return substr($file, 0, strrpos($file, "."));
}

// This function returns 'shared' if an object is shared, or 'static' if an object is static, based on its id.
// ===========================================================================================================

function is_shared_or_static($ob_id)
{
    // Connecting to the database.
    $resource_r = connect_sphere_r();

    // Querying...
    $query = "SELECT mo_id, mo_shared FROM fgs_models WHERE mo_id =(SELECT ob_model FROM fgs_objects WHERE ob_id = ".$ob_id.");";
    $result = @pg_query($resource_r, $query);

    while ($row = pg_fetch_row($result)) {
        if ($row[1] == 0) return ('static');
        else return ('shared');
    }

    // Closing the connection.
    @pg_close ($resource_r);
}

// This function returns a random string which is used to be suffixed to a directory name to (try) to make it unique.
// ==================================================================================================================

function random_suffix()
{
    // Feeding the beast
    $ipaddr = $_SERVER['REMOTE_ADDR'];
    $suffix_data = microtime().$ipaddr;

    // Generating 16 random values from a hash. Should be enough as we also have a concurrent access management on dirs.
    $dir_random_suffix = substr(hash('sha256', $suffix_data), 0, 16);
    return $dir_random_suffix;
}

// This function extracts a tgz file into a temporary directory and returns its path.
// ==================================================================================

function open_tgz($file)
{
    // Managing possible concurrent accesses on the maintainer side.
    $target_path = sys_get_temp_dir() .'/submission_'.random_suffix();

    while (file_exists($target_path)) {
        usleep(500);    // Makes concurrent access impossible: the script has to wait if this directory already exists.
    }

    if (!mkdir($target_path)) {
        echo "Impossible to create ".$target_path." directory!";
    }

    if (file_exists($target_path) && is_dir($target_path)) {
        $archive = base64_decode ($file);           // DeBase64 file
        $file = $target_path.'/submitted_files.tar.gz';     // Defines the destination file
        file_put_contents ($file, $archive);                // Writes the content of $file into submitted_files.tar.gz
    }

    $detar_command = 'tar xvzf '.$target_path.'/submitted_files.tar.gz -C '.$target_path. '> /dev/null';
    system($detar_command);
    
    return $target_path;
}


// This function close a temporary directory opened for a tgz file.
// ================================================================

function close_tgz($target_path)
{
    unlink($target_path.'/submitted_files.tar.gz');  // Deletes compressed file
    clear_dir($target_path);                          // Deletes temporary submission directory
}
?>
