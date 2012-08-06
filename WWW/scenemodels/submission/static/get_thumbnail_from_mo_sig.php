<?php

// The goal of this small file is to display the thumnail of a pending model request in the fgs_position_requests table.
// There is no other (known ;-) possibility to include this in the rest of the static submission script.

    header("Content-type: image/jpg");
    require_once ('../../inc/functions.inc.php');

    if (isset($_GET["mo_sig"]) && (strlen($_GET["mo_sig"]) == 64) && preg_match("/[0-9a-z]/", $_GET["mo_sig"])) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if($resource_rw != '0') {

            // Checking the presence of sig into the database
            $result = @pg_query($resource_rw, "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_GET["mo_sig"] ."';");
            if (pg_num_rows($result) != 1) {
                @pg_close($resource_rw);
                exit;
            }

            while ($row = pg_fetch_row($result)) {
                $sqlzbase64 = $row[1];

                // Base64 decode the query
                $sqlz = base64_decode($sqlzbase64);

                // Gzuncompress the query
                $query_rw = gzuncompress($sqlz);
                $pattern = "/INSERT INTO fgsoj_models \(mo_id, mo_path, mo_author, mo_name, mo_notes, mo_thumbfile, mo_modelfile, mo_shared\) VALUES \(DEFAULT, '(?P<path>[a-zA-Z0-9_.-]+)', (?P<author>[0-9]+), '(?P<name>[a-zA-Z0-9 ,!_.-]+)', '(?P<notes>[a-zA-Z0-9 ,!_.-]+)', '(?P<thumbfile>[a-zA-Z0-9=+\/]+)', '(?P<modelfile>[a-zA-Z0-9=+\/]+)', (?P<shared>[0-9]+)\) RETURNING mo_id/";
                preg_match($pattern, $query_rw, $matches);

                echo base64_decode($matches['thumbfile']);
            }
        }
    }
?>
