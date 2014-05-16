<?php
// The goal of this small file is to give the possibility to download a model insertion request
// as a tar.gz file, containing model, textures, XML file.
// There is no other (known ;-) possibility to include this in the rest of the model submission script.

// Inserting libs
require_once '../../inc/functions.inc.php';
require_once '../../inc/form_checks.php';
header("Content-type: application/x-gtar");
header("Content-Disposition: inline; filename=get_targz_from_mo_sig.tgz");

if (is_sig($_GET["mo_sig"])) {
    $resource_rw = connect_sphere_rw();

     // If connection is OK
    if ($resource_rw != '0') {

        // Checking the presence of sig into the database
        $result = pg_query($resource_rw, "SELECT spr_base64_sqlz " .
                                         "FROM fgs_position_requests " .
                                         "WHERE spr_hash = '". $_GET["mo_sig"] ."';");
        if (pg_num_rows($result) != 1) {
            pg_close($resource_rw);
            exit;
        }

        $rows = pg_fetch_row($result);
        $sqlzbase64 = $rows[0];

        // Base64 decode the query
        $sqlz = base64_decode($sqlzbase64);

        // Gzuncompress the query
        $query_rw = gzuncompress($sqlz);
        $pattern = "/INSERT INTO fgs_models \(mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared\) VALUES \(DEFAULT, '(?P<path>[a-zA-Z0-9_.-]+)', (?P<author>[0-9]+), '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', '(?P<modelfile>[a-zA-Z0-9=+\/]+)', (?P<shared>[0-9]+)\) RETURNING mo_id/";
        $correspond = preg_match($pattern, $query_rw, $matches);
        
        if (!$correspond) {
            $pattern = "/UPDATE fgs_models SET " .
                       "mo_path \= '(?P<path>[a-zA-Z0-9_.-]+)', " .
                       "mo_author \= (?P<author>[0-9]+), " .
                       "mo_name \= '(?P<name>[a-zA-Z0-9,;:?@ !_.-]+)', " .
                       "mo_notes \= '(?P<notes>[a-zA-Z0-9 ,!_.-]*)', " .
                       "mo_thumbfile \= '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', " .
                       "mo_modelfile \= '(?P<modelfile>[a-zA-Z0-9=+\/]+)', " .
                       "mo_shared \= (?P<shared>[0-9]+) " .
                       "WHERE mo_id \= (?P<modelid>[0-9]+)/";
            preg_match($pattern, $query_rw, $matches);
        }
        

        echo base64_decode($matches['modelfile']);
    }
}

?>